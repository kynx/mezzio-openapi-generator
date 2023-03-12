<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation\Generator;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequestFactory;
use Kynx\Mezzio\OpenApi\Hydrator\HydratorUtil;
use Kynx\Mezzio\OpenApi\Operation\ContentTypeNegotiator;
use Kynx\Mezzio\OpenApi\Operation\Exception\InvalidContentTypeException;
use Kynx\Mezzio\OpenApi\Operation\OperationUtil;
use Kynx\Mezzio\OpenApi\Operation\RequestFactoryInterface;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\DiscriminatorUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Psr\Http\Message\ServerRequestInterface;
use Rize\UriTemplate;

use function array_keys;
use function array_map;
use function assert;
use function current;
use function preg_match;
use function ucfirst;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\Generator\OperationFactoryGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RequestFactoryGenerator
{
    /**
     * @param array<string, string> $overrideHydrators
     */
    public function __construct(
        private readonly array $overrideHydrators,
        private readonly Dumper $dumper = new Dumper()
    ) {
        $this->dumper->indentation = '    ';
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    public function generate(OperationModel $operation, array $hydratorMap): PhpFile
    {
        $file = new PhpFile();
        $file->setStrictTypes();

        $requestClass = $operation->getRequestClassName();
        $factoryClass = $operation->getRequestFactoryClassName();
        $class        = $file->addClass($factoryClass)
            ->addImplement(RequestFactoryInterface::class)
            ->setFinal();

        $namespace = current($file->getNamespaces());
        $namespace->addUse(OpenApiRequestFactory::class)
            ->addUse(RequestFactoryInterface::class)
            ->addUse(ServerRequestInterface::class);

        $class->addAttribute(OpenApiRequestFactory::class, [$operation->getJsonPointer()]);

        $this->addConstructor($namespace, $class, $operation);

        $getOperation = $class->addMethod('getOperation')
            ->setPublic()
            ->setReturnType($requestClass);
        $getOperation->addParameter('request')
            ->setType(ServerRequestInterface::class);

        $arguments = [];
        if ($operation->getPathParams() !== null) {
            $arguments[] = new Literal(
                $this->getParamParser($namespace, $class, $operation->getPathParams(), 'path', $hydratorMap)
            );
        }
        if ($operation->getQueryParams() !== null) {
            $arguments[] = new Literal(
                $this->getParamParser($namespace, $class, $operation->getQueryParams(), 'query', $hydratorMap)
            );
        }
        if ($operation->getHeaderParams() !== null) {
            $arguments[] = new Literal(
                $this->getParamParser($namespace, $class, $operation->getHeaderParams(), 'header', $hydratorMap)
            );
        }
        if ($operation->getCookieParams() !== null) {
            $arguments[] = new Literal(
                $this->getParamParser($namespace, $class, $operation->getCookieParams(), 'cookie', $hydratorMap)
            );
        }
        if ($operation->getRequestBodies() !== []) {
            $arguments[] = new Literal(
                $this->addRequestBodyParser($namespace, $class, $operation, $hydratorMap)
            );
        }

        $className = GeneratorUtil::getClassName($requestClass);
        $arguments = GeneratorUtil::formatAsList($this->dumper, $arguments);
        $getOperation->addBody("return new $className($arguments);");

        return $file;
    }

    /**
     * @param list<RequestBodyModel> $requestBodies
     */
    private function addConstructor(PhpNamespace $namespace, ClassType $class, OperationModel $operation): void
    {
        $requestBodies = $operation->getRequestBodies();
        if ($requestBodies === [] && $operation->getModels() === []) {
            return;
        }

        $constructor = $class->addMethod('__construct')
            ->setPublic();

        if ($operation->getModels() !== []) {
            $namespace->addUse(UriTemplate::class);
            $class->addProperty('uriTemplate')
                ->setPrivate()
                ->setType(UriTemplate::class);
            $constructor->addBody('$this->uriTemplate = new UriTemplate();');
        }

        if ($requestBodies !== []) {
            $namespace->addUse(ContentTypeNegotiator::class);
            $class->addProperty('negotiator')
                ->setPrivate()
                ->setType(ContentTypeNegotiator::class);

            $dumper             = clone $this->dumper;
            $dumper->wrapLength = 60;
            $mimeTypes          = $dumper->dump(
                array_map(fn (RequestBodyModel $body): string => $body->getMimeType(), $requestBodies)
            );

            $constructor->addBody("\$this->negotiator = new ContentTypeNegotiator($mimeTypes);");
        }
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getParamParser(
        PhpNamespace $namespace,
        ClassType $class,
        CookieOrHeaderParams|PathOrQueryParams $params,
        string $type,
        array $hydratorMap
    ): string {
        $namespace->addUse(OperationUtil::class);

        $template  = $this->getTemplate($params);
        $model     = $params->getModel();
        $className = $model->getClassName();

        $hydrator      = $hydratorMap[$model->getClassName()];
        $hydratorClass = GeneratorUtil::getClassName($hydrator);
        $namespace->addUse($className);
        $namespace->addUse($hydrator);

        $var        = '$' . $type;
        $utilMethod = 'get' . ucfirst($type) . 'Variables';
        $getMethod  = 'get' . ucfirst($type) . 'Params';

        $method = $class->addMethod($getMethod)
            ->setPrivate()
            ->setReturnType($className);
        $method->addParameter('request')
            ->setType(ServerRequestInterface::class);

        $method->addBody("$var = OperationUtil::$utilMethod(\$this->uriTemplate, $template, \$request);");
        foreach ($model->getProperties() as $property) {
            if ($this->isUnexplodedObject($property, $template)) {
                $name = $property->getOriginalName();
                $method->addBody("{$var}['$name'] = OperationUtil::listToAssociativeArray({$var}['$name']);");
            }
        }

        $method->addBody("return $hydratorClass::hydrate($var);");

        return "\$this->$getMethod(\$request)";
    }

    /**
     * Returns true if parameter is in unexploded object notation - for example `R,100,G,200,B,150`
     *
     * We do not currently handle `allOf` / `anyOf` / `oneOf` objects in unexploded parameters. These would be difficult
     * to handle. Use the exploded style instead, which is the default for query and cookie parameters.
     *
     * There is also an edge case if a user has an unexploded object parameter and overrides the hydrator. Hydrator
     * overrides are primarily intended for converting specific string formats to objects - like `date-time`. These will
     * not be a problem. But there is nothing to stop them overriding a complex object, which won't be converted here.
     * If they really want such a thing they will have to design their hydrator to detect and convert the list.
     *
     * Unexploded parameter notation is an abomination - users should be encouraged to avoid it.
     *
     * @see https://spec.openapis.org/oas/v3.1.0#style-examples
     */
    private function isUnexplodedObject(PropertyInterface $property, string $template): bool
    {
        if (! $property instanceof SimpleProperty) {
            return false;
        }
        if (! $property->getType() instanceof ClassString) {
            return false;
        }
        if (isset($this->overrideHydrators[$property->getType()->getClassString()])) {
            return false;
        }

        $name = $property->getOriginalName();
        return (bool) preg_match('/{[?&]?' . $name . '}/', $template);
    }

    /**
     * @param list<RequestBodyModel> $requestBodies
     * @param array<string, string> $hydratorMap
     */
    private function addRequestBodyParser(
        PhpNamespace $namespace,
        ClassType $class,
        OperationModel $operation,
        array $hydratorMap
    ): string {
        $namespace->addUse(InvalidContentTypeException::class);

        foreach ($operation->getRequestBodyUses() as $use) {
            $namespace->addUse($use);
        }

        $method = $class->addMethod('getRequestBody')
            ->setPrivate()
            ->setReturnType($operation->getRequestBodyType());
        $method->addParameter('request')
            ->setType(ServerRequestInterface::class);

        $method->addBody('$body     = $request->getParsedBody() ?? (string) $request->getBody();');
        $method->addBody('$mimeType = $this->negotiator->negotiate($request);' . "\n");

        $literals = [];
        foreach ($operation->getRequestBodies() as $requestBody) {
            $return     = $this->getRequestBodyReturn($namespace, $requestBody, $hydratorMap);
            $literals[] = new Literal("? => $return", [$requestBody->getMimeType()]);
        }
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $literals[] = new Literal('default => throw InvalidContentTypeException::fromExpected($mimeType, $this->negotiator->getMimeTypes())');

        $dumper     = clone $this->dumper;
        $conditions = GeneratorUtil::formatAsList($dumper, $literals);
        $method->addBody('return match ($mimeType) {' . $conditions . '};');

        return '$this->getRequestBody($request)';
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getRequestBodyReturn(
        PhpNamespace $namespace,
        RequestBodyModel $requestBody,
        array $hydratorMap
    ): string {
        $property = $requestBody->getType();
        if ($property instanceof ArrayProperty) {
            return $this->getArrayRequestBodyReturn($namespace, $property, $hydratorMap);
        }
        if ($property instanceof SimpleProperty) {
            return $this->getSimpleRequestBodyReturn($namespace, $property, $hydratorMap);
        }

        assert($property instanceof UnionProperty);
        return $this->getUnionRequestBodyReturn($namespace, $property, $hydratorMap);
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getArrayRequestBodyReturn(
        PhpNamespace $namespace,
        ArrayProperty $property,
        array $hydratorMap
    ): string {
        $type = $property->getType();
        if ($type instanceof ClassString) {
            $classString   = $type->getClassString();
            $hydrator      = $this->overrideHydrators[$classString] ?? $hydratorMap[$classString];
            $hydratorClass = GeneratorUtil::getClassName($hydrator);

            $namespace->addUse(HydratorUtil::class);
            $namespace->addUse($classString);
            $namespace->addUse($hydrator);

            return "HydratorUtil::hydrateArray('body', \$body, $hydratorClass::class)";
        }

        return '(array) $body';
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getSimpleRequestBodyReturn(
        PhpNamespace $namespace,
        SimpleProperty $property,
        array $hydratorMap
    ): string {
        $type = $property->getType();
        if ($type instanceof ClassString) {
            $classString   = $type->getClassString();
            $hydrator      = $this->overrideHydrators[$classString] ?? $hydratorMap[$classString];
            $hydratorClass = GeneratorUtil::getClassName($hydrator);

            $namespace->addUse($classString);
            $namespace->addUse($hydrator);

            return "$hydratorClass::hydrate(\$body)";
        }

        $phpType = $type->toPhpType();
        return "($phpType) \$body";
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getUnionRequestBodyReturn(
        PhpNamespace $namespace,
        UnionProperty $property,
        array $hydratorMap
    ): string {
        $discriminator = $property->getDiscriminator();
        if ($discriminator instanceof PropertyValue) {
            return $this->getPropertyValueRequestBodyReturn($namespace, $property, $hydratorMap);
        }

        return $this->getPropertyListRequestBodyReturn($namespace, $property, $hydratorMap);
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getPropertyValueRequestBodyReturn(
        PhpNamespace $namespace,
        UnionProperty $property,
        array $hydratorMap
    ): string {
        $discriminator = $property->getDiscriminator();
        assert($discriminator instanceof PropertyValue);

        foreach ($discriminator->getValueMap() as $className) {
            $namespace->addUse($className);
        }

        $values = DiscriminatorUtil::getValueDiscriminator($property, $hydratorMap);
        foreach ($values['map'] as $key => $className) {
            $namespace->addUse($className);
            $values['map'][$key] = new Literal(GeneratorUtil::getClassName($className) . "::class");
        }

        $dumper             = clone $this->dumper;
        $dumper->wrapLength = 58;

        return $dumper->format("HydratorUtil::hydrateDiscriminatorValue('requestBody', \$body, ?)", $values);
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getPropertyListRequestBodyReturn(
        PhpNamespace $namespace,
        UnionProperty $property,
        array $hydratorMap
    ): string {
        $discriminator = $property->getDiscriminator();
        assert($discriminator instanceof PropertyList);

        foreach (array_keys($discriminator->getClassMap()) as $className) {
            $namespace->addUse($className);
        }

        $values   = DiscriminatorUtil::getListDiscriminator($property, $hydratorMap);
        $literals = [];
        foreach ($values as $hydratorName => $properties) {
            $namespace->addUse($hydratorName);
            $hydrator   = GeneratorUtil::getClassName($hydratorName);
            $literals[] = new Literal($hydrator . '::class => ?', [$properties]);
        }

        $dumper             = clone $this->dumper;
        $dumper->wrapLength = 58;

        return $dumper->format("HydratorUtil::hydrateDiscriminatorList('requestBody', \$body, ?)", $literals);
    }

    private function getTemplate(CookieOrHeaderParams|PathOrQueryParams $params): string
    {
        $template = $params instanceof PathOrQueryParams ? $params->getTemplate() : $params->getTemplates();
        return $this->dumper->dump($template);
    }
}

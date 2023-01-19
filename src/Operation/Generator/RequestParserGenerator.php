<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation\Generator;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequestParser;
use Kynx\Mezzio\OpenApi\Operation\OperationUtil;
use Kynx\Mezzio\OpenApi\Operation\RequestBody\MediaTypeMatcher;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\DiscriminatorUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Closure;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PsrPrinter;
use Psr\Http\Message\ServerRequestInterface;
use Rize\UriTemplate;

use function array_keys;
use function assert;
use function implode;
use function str_contains;
use function ucfirst;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\Generator\RequestParserGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RequestParserGenerator
{
    /**
     * @param array<string, string> $overrideHydrators
     */
    public function __construct(
        private readonly array $overrideHydrators,
        private readonly Dumper $dumper = new Dumper(),
        private readonly Printer $printer = new PsrPrinter()
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

        $namespace = $file->addNamespace(GeneratorUtil::getNamespace($operation->getClassName()));
        $namespace->addUse(OpenApiRequestParser::class)
            ->addUse(OperationUtil::class)
            ->addUse(ServerRequestInterface::class)
            ->addUse($operation->getClassName());

        $operationClass = $operation->getClassName();
        $parserClass    = GeneratorUtil::getNamespace($operationClass) . '\\RequestParser';
        $class          = $namespace->addClass(GeneratorUtil::getClassName($parserClass))
            ->setFinal();

        $class->addAttribute(OpenApiRequestParser::class, [$operation->getJsonPointer()]);

        $this->addConstructor($namespace, $class, $operation->getRequestBodies(), $hydratorMap);

        $parse = $class->addMethod('parse')
            ->setPublic()
            ->setReturnType($operationClass);
        $parse->addParameter('request')
            ->setType(ServerRequestInterface::class);

        if ($operation->getModels() !== []) {
            $namespace->addUse(UriTemplate::class);
            $parse->addBody('$uriTemplate = new UriTemplate();');
        }
        $parse->addBody("\$params = [];\n");

        if ($operation->getPathParams() !== null) {
            $this->addParamParser($namespace, $parse, $operation->getPathParams(), 'path', $hydratorMap);
        }
        if ($operation->getQueryParams() !== null) {
            $this->addParamParser($namespace, $parse, $operation->getQueryParams(), 'query', $hydratorMap);
        }
        if ($operation->getHeaderParams() !== null) {
            $this->addParamParser($namespace, $parse, $operation->getHeaderParams(), 'header', $hydratorMap);
        }
        if ($operation->getCookieParams() !== null) {
            $this->addParamParser($namespace, $parse, $operation->getCookieParams(), 'cookie', $hydratorMap);
        }
        if ($operation->getRequestBodies() !== []) {
            $this->addRequestBodyParser($parse);
        }

        $className = GeneratorUtil::getClassName($operationClass);
        $parse->addBody("return new $className(...\$params);");

        return $file;
    }

    /**
     * @param list<RequestBodyModel> $requestBodies
     * @param array<string, string> $hydratorMap
     */
    private function addConstructor(
        PhpNamespace $namespace,
        ClassType $class,
        array $requestBodies,
        array $hydratorMap
    ): void {
        if ($requestBodies === []) {
            return;
        }

        $namespace->addUse(MediaTypeMatcher::class);
        $namespace->addUseFunction('assert');

        $constructor = $class->addMethod('__construct')
            ->setPublic();
        $constructor->addPromotedParameter('requestBodyMatcher')
            ->setPrivate()
            ->setReadOnly()
            ->setType(MediaTypeMatcher::class);
        $class->addProperty('bodyParsers')
            ->setPrivate()
            ->setType('array');

        $callbacks = [];
        foreach ($requestBodies as $requestBody) {
            $mimeType             = $requestBody->getMimeType();
            $closure              = $this->getRequestBodyCallback($namespace, $requestBody, $hydratorMap);
            $callbacks[$mimeType] = new Literal($this->printer->printClosure($closure));
        }
        $constructor->addBody('$this->bodyParsers = ' . $this->dumper->dump($callbacks) . ";");
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function addParamParser(
        PhpNamespace $namespace,
        Method $method,
        CookieOrHeaderParams|PathOrQueryParams $params,
        string $type,
        array $hydratorMap
    ): void {
        $template = $this->getTemplate($params);
        $model    = $params->getModel();

        $var       = '$' . $type;
        $getMethod = 'get' . ucfirst($type) . 'Variables';
        $key       = $type . 'Params';

        $method->addBody("$var = OperationUtil::$getMethod(\$uriTemplate, $template, \$request);");
        foreach ($model->getProperties() as $property) {
            if (! ($property instanceof SimpleProperty && $property->getType() instanceof ClassString)) {
                continue;
            }
            $name = $property->getOriginalName();
            if (str_contains($template, "{$name}")) {
                $method->addBody("{$var}['$name'] = OperationUtil::listToAssociativeArray({$var}['$name']);");
            }
        }

        $hydrator      = $hydratorMap[$model->getClassName()];
        $hydratorClass = GeneratorUtil::getClassName($hydrator);
        $namespace->addUse($hydrator);

        $method->addBody("\$params['$key'] = $hydratorClass::hydrate($var);\n");
    }

    private function addRequestBodyParser(Method $method): void
    {
        $method->addBody('$parser   = $this->requestBodyMatcher->getParser($request);');
        $method->addBody('$body     = $parser->parse($request);');
        $method->addBody('$callback = $this->bodyParsers[$parser->getMimeType()] ?? null;');
        $method->addBody('assert(is_callable($callback));');
        $method->addBody("\$params['requestBody'] = \$callback(\$body);\n");
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getRequestBodyCallback(
        PhpNamespace $namespace,
        RequestBodyModel $requestBody,
        array $hydratorMap
    ): Closure {
        $property = $requestBody->getType();
        if ($property instanceof ArrayProperty) {
            return $this->getArrayRequestBodyCallback();
        }
        if ($property instanceof SimpleProperty) {
            return $this->getSimpleRequestBodyCallback($namespace, $property, $hydratorMap);
        }

        assert($property instanceof UnionProperty);
        return $this->getUnionRequestBodyCallback($namespace, $property, $hydratorMap);
    }

    private function getArrayRequestBodyCallback(): Closure
    {
        $closure = new Closure();
        $closure->setReturnType('array');
        $closure->addParameter('body')
            ->setType('mixed');
        $closure->setBody('return (array) $body;');

        return $closure;
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getSimpleRequestBodyCallback(
        PhpNamespace $namespace,
        SimpleProperty $property,
        array $hydratorMap
    ): Closure {
        $type = $property->getType();
        if ($type instanceof ClassString) {
            $classString   = $type->getClassString();
            $hydrator      = $this->overrideHydrators[$classString] ?? $hydratorMap[$classString];
            $hydratorClass = GeneratorUtil::getClassName($hydrator);

            $namespace->addUse($classString);
            $namespace->addUse($hydrator);

            $closure = new Closure();
            $closure->setReturnType(GeneratorUtil::getClassName($classString));
            $closure->addParameter('body')
                ->setType('array');
            $closure->setBody("return $hydratorClass::hydrate(\$body);");

            return $closure;
        }

        $phpType = $type->toPhpType();
        if ($type->isClassType()) {
            $hydrator      = $this->overrideHydrators[$phpType];
            $hydratorClass = GeneratorUtil::getClassName($hydrator);

            $namespace->addUse($phpType);
            $namespace->addUse($hydrator);

            $closure = new Closure();
            $closure->setReturnType(GeneratorUtil::getClassName($phpType));
            $closure->addParameter('body')
                ->setType('string');
            $closure->setBody("return $hydratorClass::hydrate(\$body);");

            return $closure;
        }

        $closure = new Closure();
        $closure->setReturnType(GeneratorUtil::getClassName($phpType));
        $closure->addParameter('body')
            ->setType('string');
        $closure->setBody("return ($phpType) \$body;");

        return $closure;
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getUnionRequestBodyCallback(
        PhpNamespace $namespace,
        UnionProperty $property,
        array $hydratorMap
    ): Closure {
        $discriminator = $property->getDiscriminator();
        if ($discriminator instanceof PropertyValue) {
            return $this->getPropertyValueRequestBodyCallback($namespace, $property, $hydratorMap);
        }

        return $this->getPropertyListRequestBodyCallback($namespace, $property, $hydratorMap);
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getPropertyValueRequestBodyCallback(
        PhpNamespace $namespace,
        UnionProperty $property,
        array $hydratorMap
    ): Closure {
        $discriminator = $property->getDiscriminator();
        assert($discriminator instanceof PropertyValue);

        $types = [];
        foreach ($discriminator->getValueMap() as $className) {
            $namespace->addUse($className);
            $types[] = GeneratorUtil::getClassName($className);
        }

        $values = DiscriminatorUtil::getValueDiscriminator($property, $hydratorMap);
        foreach ($values['map'] as $key => $className) {
            $namespace->addUse($className);
            $values['map'][$key] = new Literal(GeneratorUtil::getClassName($className) . "::class");
        }

        $dumper             = clone $this->dumper;
        $dumper->wrapLength = 58;
        $valueArray         = $dumper->dump($values);

        $closure = new Closure();
        $closure->setReturnType(implode('|', $types));
        $closure->addParameter('body')
            ->setType('array');
        $closure->addBody("return HydratorUtil::hydrateDiscriminatorValue('requestBody', \$body, $valueArray);");

        return $closure;
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    private function getPropertyListRequestBodyCallback(
        PhpNamespace $namespace,
        UnionProperty $property,
        array $hydratorMap
    ): Closure {
        $discriminator = $property->getDiscriminator();
        assert($discriminator instanceof PropertyList);

        $types = [];
        foreach (array_keys($discriminator->getClassMap()) as $className) {
            $namespace->addUse($className);
            $types[] = GeneratorUtil::getClassName($className);
        }

        $values   = DiscriminatorUtil::getListDiscriminator($property, $hydratorMap);
        $literals = [];
        foreach ($values as $hydratorName => $properties) {
            $namespace->addUse($hydratorName);
            $hydrator   = GeneratorUtil::getClassName($hydratorName);
            $literals[] = new Literal($hydrator . '::class => ' . $this->dumper->dump($properties));
        }

        $dumper             = clone $this->dumper;
        $dumper->wrapLength = 58;
        $valueArray         = $dumper->dump($literals);

        $closure = new Closure();
        $closure->setReturnType(implode('|', $types));
        $closure->addParameter('body')
            ->setType('array');
        $closure->addBody("return HydratorUtil::hydrateDiscriminatorList('requestBody', \$body, $valueArray);");

        return $closure;
    }

    private function getTemplate(CookieOrHeaderParams|PathOrQueryParams $params): string
    {
        $template = $params instanceof PathOrQueryParams ? $params->getTemplate() : $params->getTemplates();
        return $this->dumper->dump($template);
    }
}

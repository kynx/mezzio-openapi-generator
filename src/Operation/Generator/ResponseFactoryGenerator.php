<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation\Generator;

use Kynx\Mezzio\OpenApi\Hydrator\HydratorInterface;
use Kynx\Mezzio\OpenApi\Hydrator\HydratorUtil;
use Kynx\Mezzio\OpenApi\Operation\AbstractResponseFactory;
use Kynx\Mezzio\OpenApi\Serializer\SerializerInterface;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseModel;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Negotiation\Negotiator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Psr\Http\Message\ServerRequestInterface;

use function array_filter;
use function array_map;
use function array_merge;
use function array_pop;
use function array_reduce;
use function array_unique;
use function count;
use function current;
use function explode;
use function implode;
use function is_numeric;
use function strtolower;
use function ucfirst;

final class ResponseFactoryGenerator
{
    /**
     * @param array<string, string> $overrideExtractors
     */
    public function __construct(
        private readonly array $overrideExtractors,
        private readonly Dumper $dumper = new Dumper()
    ) {
        $this->dumper->indentation = '    ';
    }

    /**
     * @param array<string, string> $extractorMap
     */
    public function generate(OperationModel $operation, array $extractorMap): PhpFile
    {
        $file = new PhpFile();
        $file->setStrictTypes();

        $class = $file->addClass($operation->getResponseFactoryClassName())
            ->setExtends(AbstractResponseFactory::class)
            ->setFinal();

        $namespace = current($file->getNamespaces());
        $namespace->addUse(AbstractResponseFactory::class);

        $this->addConstructor($namespace, $class, $operation);
        $this->addExtractorProperty($namespace, $class, $operation, $extractorMap);

        foreach ($operation->getResponseStatuses() as $status) {
            $this->addGetResponseMethod($namespace, $class, $status, $operation);
        }

        return $file;
    }

    private function addConstructor(PhpNamespace $namespace, ClassType $class, OperationModel $operation): void
    {
        if (! ($operation->responsesRequireNegotiation() || $operation->responsesRequireSerialization())) {
            return;
        }

        $constructor = $class->addMethod('__construct')
            ->setPublic();

        if ($operation->responsesRequireSerialization()) {
            $namespace->addUse(SerializerInterface::class);
            $constructor->addPromotedParameter('serializer')
                ->setType(SerializerInterface::class)
                ->setPrivate()
                ->setReadOnly();
        }

        $constructor->addParameter('maxMemory')
            ->setType('int')
            ->setDefaultValue(new Literal('self::DEFAULT_MAX_MEMORY'));

        if ($operation->responsesRequireNegotiation()) {
            $namespace->addUse(Negotiator::class);
            $class->addProperty('negotiator')
                ->setType(Negotiator::class)
                ->setPrivate()
                ->setReadOnly();

            $constructor->addBody('$this->negotiator = new Negotiator();');
        }

        $constructor->addBody('parent::__construct($maxMemory);');
    }

    /**
     * @param array<string, string> $extractorMap
     */
    public function addExtractorProperty(
        PhpNamespace $namespace,
        ClassType $class,
        OperationModel $operation,
        array $extractorMap
    ): void {
        $classes = [];
        foreach ($operation->getResponseStatuses() as $status) {
            $classes = array_merge($classes, $this->getResponseUses($operation->getResponsesOfStatus($status)));
        }
        $classes = array_unique($classes);
        ksort($classes);

        $extractors = [];
        foreach ($classes as $className) {
            $extractor = $this->overrideExtractors[$className] ?? $extractorMap[$className];
            $namespace->addUse($className);
            $namespace->addUse($extractor);

            $extractors[] = new Literal(sprintf(
                "%s::class => %s::class",
                $namespace->simplifyName($className),
                $namespace->simplifyName($extractor)
            ));
        }

        if ($extractors === []) {
            return;
        }

        $namespace->addUse(HydratorInterface::class);
        $class->addProperty('extractors', $extractors)
            ->setType('array')
            ->setPrivate()
            ->setComment('@var array<class-string, class-string<HydratorInterface>>');
    }

    /**
     */
    private function addGetResponseMethod(
        PhpNamespace $namespace,
        ClassType $class,
        string $status,
        OperationModel $operation
    ): void {
        $responses = $operation->getResponsesOfStatus($status);
        $mimeTypes = $this->getMimeTypes($responses);

        foreach ($this->getResponseUses($responses) as $use) {
            $namespace->addUse($use);
        }

        $returnType = $this->getReturnType($responses);
        $namespace->addUse($returnType);
        $method = $class->addMethod('get' . ucfirst(strtolower($status)) . 'Response')
            ->setPublic()
            ->setReturnType($returnType);

        if (count($mimeTypes) > 1) {
            $this->addNegotiatedResponse($namespace, $method, $status, $operation);
        } elseif (count($mimeTypes) === 1) {
            $this->addSingleMimeTypeResponse($namespace, $method, $status, $operation);
        } else {
            $this->addEmptyResponse($namespace, $method, $status, $operation);
        }
    }

    /**
     */
    private function addNegotiatedResponse(
        PhpNamespace $namespace,
        Method $method,
        string $status,
        OperationModel $operation
    ): void {
        $responses    = $operation->getResponsesOfStatus($status);
        $mimeTypes    = $this->getMimeTypes($responses);
        $headers      = $operation->getResponseHeaders($status);
        $reasonPhrase = $this->getReasonPhrase($responses);
        $status       = is_numeric($status) ? (int) $status : 200;

        $namespace->addUse(ServerRequestInterface::class);

        $method->addParameter('request')
            ->setType(ServerRequestInterface::class);
        $method->addParameter('model')
            ->setType($this->getResponseTypes($responses));

        $method->addBody('$accept = $request->getHeaderLine("Accept");');
        $method->addBody(
            '$mimeType = $this->getMimeType($this->negotiator, $this->serializer, $accept, ?);',
            [$mimeTypes]
        );

        if ($headers !== []) {
            $method->addParameter('headers')
                ->setType('array');
            $method->addBody('$headers["Content-Type"] = "$mimeType; charset=utf-8";');
        } else {
            $method->addBody('$headers = ["Content-Type" => "$mimeType; charset=utf-8"];');
        }

        $extractor = $this->getExtractor($namespace, $responses);

        $method->addBody('$body = $this->serializer->serialize($mimeType, ?, $model);', [$extractor]);
        $method->addBody('return $this->getResponse($body, ?, ?, $headers);', [$status, $reasonPhrase]);
    }

    /**
     */
    private function addSingleMimeTypeResponse(
        PhpNamespace $namespace,
        Method $method,
        string $status,
        OperationModel $operation
    ): void {
        $responses    = $operation->getResponsesOfStatus($status);
        $mimeType     = current($this->getMimeTypes($responses));
        $headers      = $operation->getResponseHeaders($status);
        $reasonPhrase = $this->getReasonPhrase($responses);
        $status       = is_numeric($status) ? (int) $status : 200;

        $method->addParameter('model')
            ->setType($this->getResponseTypes($responses));

        if ($headers !== []) {
            $method->addParameter('headers')
                ->setType('array');
            $method->addBody('$headers["Content-Type"] = ?;', ["$mimeType; charset=utf-8"]);
        } else {
            $method->addBody('$headers = ["Content-Type" => ?];', ["$mimeType; charset=utf-8"]);
        }

        $extractor = $this->getExtractor($namespace, $responses);

        $method->addBody('$body = $this->serializer->serialize(?, ?, $model);', [$mimeType, $extractor]);
        $method->addBody('return $this->getResponse($body, ?, ?, $headers);', [$status, $reasonPhrase]);
    }

    private function addEmptyResponse(
        PhpNamespace $namespace,
        Method $method,
        string $status,
        OperationModel $operation,
    ): void {
        $responses    = $operation->getResponsesOfStatus($status);
        $headers      = $operation->getResponseHeaders($status);
        $reasonPhrase = $this->getReasonPhrase($responses);
        $status       = is_numeric($status) ? (int) $status : 204;

        $namespace->addUse(EmptyResponse::class);

        if ($headers !== []) {
            $method->addParameter('headers')
                ->setType('array');
            $method->addBody('return (new EmptyResponse(?, $headers))->withStatus(?, ?);', [
                $status,
                $status,
                $reasonPhrase,
            ]);
        } else {
            $method->addBody('return (new EmptyResponse())->withStatus(?, ?);', [$status, $reasonPhrase]);
        }
    }

    /**
     * @param array<int, ResponseModel> $responses
     * @param array<string, string> $extractorMap
     */
    private function getExtractor(PhpNamespace $namespace, array $responses): Literal
    {
        $uses = $this->getResponseUses($responses);

        if ($this->isObjectArrayResponse($responses)) {
            $namespace->addUse(HydratorUtil::class);
            return new Literal('HydratorUtil::extractObjectArray($model, $this->extractors)');
        } elseif ($this->hasObjectArrayResponse($responses)) {
            $namespace->addUse(HydratorUtil::class);
            return new Literal('HydratorUtil::extractMixedArray($model, $this->extractors)');
        } elseif (count($uses) > 0) {
            return new Literal('$this->extractors[$model::class]::extract($model)');
        }

        return new Literal('$model');
    }

    /**
     * @param array<int, ResponseModel> $responses
     * @return array<int, string>
     */
    private function getMimeTypes(array $responses): array
    {
        return array_filter(array_map(fn (ResponseModel $model): string|null => $model->getMimeType(), $responses));
    }

    /**
     * @param array<int, ResponseModel> $responses
     * @return array<int, string>
     */
    private function getResponseUses(array $responses): array
    {
        $uses = [];
        foreach ($responses as $response) {
            $property = $response->getType();
            if ($property === null) {
                continue;
            }

            $uses = array_merge($uses, $property->getUses());
        }

        return array_unique($uses);
    }


    /**
     * Returns `true` if all responses are `array<array-key, ClassString>`
     *
     * @param array<int, ResponseModel> $responses
     */
    private function isObjectArrayResponse(array $responses): bool
    {
        if (! count($responses)) {
            return false;
        }

        foreach ($responses as $response) {
            $property = $response->getType();
            if ($property === null) {
                continue;
            }
            if (! $property instanceof ArrayProperty) {
                return false;
            }
            $objectTypes = array_filter(
                $property->getTypes(),
                fn (ClassString|PropertyType $type): bool => $type instanceof ClassString
            );
            if (count($objectTypes) !== count($property->getTypes())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, ResponseModel> $responses
     */
    private function hasObjectArrayResponse(array $responses): bool
    {
        if (! count($responses)) {
            return false;
        }

        $hasArray = $hasObject = false;
        foreach ($responses as $response) {
            $property = $response->getType();
            if ($property === null) {
                continue;
            }
            if (in_array(PropertyType::Array, $property->getTypes())) {
                $hasArray = true;
            }
            if ($property instanceof ArrayProperty) {
                $hasArray = true;
            }
            $objectTypes = array_filter(
                $property->getTypes(),
                fn (ClassString|PropertyType $type): bool => $type instanceof ClassString
            );
            if ($objectTypes !== []) {
                $hasObject = true;
            }
        }

        return $hasArray && $hasObject;
    }

    /**
     * @param array<int, ResponseModel> $responses
     */
    private function getResponseTypes(array $responses): string
    {
        $types = [];
        foreach ($responses as $response) {
            if ($response->getType() === null) {
                continue;
            }

            $types[] = $response->getType()->getPhpType();
        }

        $allTypes = explode('|', implode('|', $types));
        return implode('|', array_unique($allTypes));
    }

    /**
     * @param array<int, ResponseModel> $responses
     * @return class-string
     */
    private function getReturnType(array $responses): string
    {
        $emptyTypes = array_filter($responses, fn (ResponseModel $model): bool => $model->getMimeType() === null);

        if (count($emptyTypes) === count($responses)) {
            return EmptyResponse::class;
        }

        return Response::class;
    }

    /**
     * @param array<int, ResponseModel> $responses
     */
    private function getReasonPhrase(array $responses): string
    {
        foreach ($responses as $response) {
            return $response->getDescription();
        }
        return '';
    }
}

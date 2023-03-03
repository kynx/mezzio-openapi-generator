<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation\Generator;

use Kynx\Mezzio\OpenApi\Operation\AbstractResponseFactory;
use Kynx\Mezzio\OpenApi\Serializer\SerializerInterface;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
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

        foreach ($operation->getResponseStatuses() as $status) {
            $this->addGetResponseMethod($namespace, $class, $status, $operation, $extractorMap);
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
    private function addGetResponseMethod(
        PhpNamespace $namespace,
        ClassType $class,
        string $status,
        OperationModel $operation,
        array $extractorMap
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
            $this->addNegotiatedResponse($namespace, $method, $status, $operation, $extractorMap);
        } elseif (count($mimeTypes) === 1) {
            $this->addSingleMimeTypeResponse($namespace, $method, $status, $operation, $extractorMap);
        } else {
            $this->addEmptyResponse($namespace, $method, $status, $operation);
        }
    }

    /**
     * @param array<string, string> $extractorMap
     */
    private function addNegotiatedResponse(
        PhpNamespace $namespace,
        Method $method,
        string $status,
        OperationModel $operation,
        array $extractorMap
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
            $method->addBody('$headers = ["ContentType" => "$mimeType; charset=utf-8"];');
        }

        $this->addExtractor($namespace, $method, $responses, $extractorMap);

        $method->addBody('$body = $this->serializer->serialize($mimeType, $extractor, $model);');
        $method->addBody('return $this->getResponse($body, ?, ?, $headers);', [$status, $reasonPhrase]);
    }

    /**
     * @param array<string, string> $extractorMap
     */
    private function addSingleMimeTypeResponse(
        PhpNamespace $namespace,
        Method $method,
        string $status,
        OperationModel $operation,
        array $extractorMap
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

        $this->addExtractor($namespace, $method, $responses, $extractorMap);

        $method->addBody('$body = $this->serializer->serialize(?, $extractor, $model);', [$mimeType]);
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
    private function addExtractor(PhpNamespace $namespace, Method $method, array $responses, array $extractorMap): void
    {
        $uses = $this->getResponseUses($responses);

        $extractors = [];
        foreach ($uses as $class) {
            $extractor = $this->overrideExtractors[$class] ?? $extractorMap[$class] ?? null;
            if ($extractor !== null) {
                $namespace->addUse($extractor);
                $extractors[$namespace->simplifyName($class)] = $namespace->simplifyName($extractor);
            }
        }

        if (count($extractors) > 1) {
            $literals = [];
            foreach ($extractors as $class => $extractor) {
                $literals[] = new Literal("$class::class => $extractor::class");
            }
            $method->addBody('$extractor = match (get_class($model)) {'
                . GeneratorUtil::formatAsList($this->dumper, $literals)
                . '};');
        } elseif (count($extractors) === 1) {
            $method->addBody('$extractor = ?;', [new Literal(array_pop($extractors) . '::class')]);
        } else {
            $method->addBody('$extractor = null;');
        }
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

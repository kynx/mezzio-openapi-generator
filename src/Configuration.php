<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use JsonSerializable;

use function get_object_vars;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\ConfigurationTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class Configuration implements JsonSerializable
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $openApiFile = '',
        private readonly string $baseNamespace = '',
        private readonly string $baseDir = '',
        private readonly string $testNamespace = '',
        private readonly string $testDir = '',
        private readonly string $modelNamespace = '',
        private readonly string $operationNamespace = '',
        private readonly string $handlerNamespace = '',
        private readonly string $routePrefix = 'api'
    ) {
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function getOpenApiFile(): string
    {
        return $this->openApiFile;
    }

    public function getBaseNamespace(): string
    {
        return $this->baseNamespace;
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    public function getTestNamespace(): string
    {
        return $this->testNamespace;
    }

    public function getTestDir(): string
    {
        return $this->testDir;
    }

    public function getModelNamespace(): string
    {
        return $this->modelNamespace;
    }

    public function getOperationNamespace(): string
    {
        return $this->operationNamespace;
    }

    public function getHandlerNamespace(): string
    {
        return $this->handlerNamespace;
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    public function jsonSerialize(): mixed
    {
        $config = get_object_vars($this);
        unset($config['projectDir']);
        return $config;
    }
}

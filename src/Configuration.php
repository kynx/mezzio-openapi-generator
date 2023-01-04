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
        private readonly string $sourceNamespace = '',
        private readonly string $sourceDir = '',
        private readonly string $testNamespace = '',
        private readonly string $testDir = ''
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

    public function getSourceNamespace(): string
    {
        return $this->sourceNamespace;
    }

    public function getSourceDir(): string
    {
        return $this->sourceDir;
    }

    public function getTestNamespace(): string
    {
        return $this->testNamespace;
    }

    public function getTestDir(): string
    {
        return $this->testDir;
    }

    public function jsonSerialize(): mixed
    {
        $config = get_object_vars($this);
        unset($config['projectDir']);
        return $config;
    }
}

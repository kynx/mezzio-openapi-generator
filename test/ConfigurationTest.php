<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Configuration
 */
final class ConfigurationTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $projectDir         = __DIR__;
        $openApiFile        = 'foo.json';
        $sourceNamespace    = "\\Kynx\\Api";
        $sourceDir          = "src/Api";
        $testNamespace      = "\\KynxTest\Api";
        $testDir            = "test/Api";
        $modelNamespace     = 'Model';
        $operationNamespace = 'Operation';
        $handlerNamespace   = 'Handler';
        $routePrefix        = 'pet';
        $configuration      = new Configuration(
            $projectDir,
            $openApiFile,
            $sourceNamespace,
            $sourceDir,
            $testNamespace,
            $testDir,
            $modelNamespace,
            $operationNamespace,
            $handlerNamespace,
            $routePrefix
        );

        self::assertSame($projectDir, $configuration->getProjectDir());
        self::assertSame($openApiFile, $configuration->getOpenApiFile());
        self::assertSame($sourceNamespace, $configuration->getBaseNamespace());
        self::assertSame($sourceDir, $configuration->getBaseDir());
        self::assertSame($testNamespace, $configuration->getTestNamespace());
        self::assertSame($testDir, $configuration->getTestDir());
        self::assertSame($modelNamespace, $configuration->getModelNamespace());
        self::assertSame($operationNamespace, $configuration->getOperationNamespace());
        self::assertSame($routePrefix, $configuration->getRoutePrefix());
    }

    public function testJsonSerializeRemovesProjectDir(): void
    {
        $expected      = [
            'openApiFile'        => 'foo.json',
            'baseNamespace'      => "\\Kynx\\Api",
            'baseDir'            => "src/Api",
            'testNamespace'      => "\\KynxTest\Api",
            'testDir'            => "test/Api",
            'modelNamespace'     => '',
            'operationNamespace' => '',
            'handlerNamespace'   => '',
            'routePrefix'        => 'api',
        ];
        $configuration = new Configuration(__DIR__, ...$expected);
        $actual        = $configuration->jsonSerialize();
        self::assertSame($expected, $actual);
    }
}

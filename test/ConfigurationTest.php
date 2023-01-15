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
        $projectDir      = __DIR__;
        $openApiFile     = 'foo.json';
        $sourceNamespace = "\\Kynx\\Api";
        $sourceDir       = "src/Api";
        $testNamespace   = "\\KynxTest\Api";
        $testDir         = "test/Api";
        $configuration   = new Configuration(
            $projectDir,
            $openApiFile,
            $sourceNamespace,
            $sourceDir,
            $testNamespace,
            $testDir
        );

        self::assertSame($projectDir, $configuration->getProjectDir());
        self::assertSame($openApiFile, $configuration->getOpenApiFile());
        self::assertSame($sourceNamespace, $configuration->getBaseNamespace());
        self::assertSame($sourceDir, $configuration->getBaseDir());
        self::assertSame($testNamespace, $configuration->getTestNamespace());
        self::assertSame($testDir, $configuration->getTestDir());
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
        ];
        $configuration = new Configuration(__DIR__, ...$expected);
        $actual        = $configuration->jsonSerialize();
        self::assertSame($expected, $actual);
    }
}

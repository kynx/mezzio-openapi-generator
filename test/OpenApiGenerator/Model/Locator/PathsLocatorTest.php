<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Paths;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\PathsLocator;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\PathItemLocator
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Locator\PathsLocator
 */
final class PathsLocatorTest extends TestCase
{
    private PathsLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new PathsLocator();
    }

    public function testGetNamedSchemasSkipsNullPath(): void
    {
        $paths = new Paths([
            '/pets' => null,
        ]);

        self::assertTrue($paths->validate(), implode("\n", $paths->getErrors()));
        $actual = $this->locator->getNamedSchemas($paths);
        self::assertEmpty($actual);
    }

    public function testGetNamedSchemasReturnsSchema(): void
    {
        $schema   = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $paths    = new Paths([
            '/my/pets' => [
                'get' => [
                    'responses' => [
                        'default' => [
                            'description' => 'Pets',
                            'content'     => [
                                'application/json' => [
                                    'schema' => $schema,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $expected = ['' => new NamedSchema('my pets defaultResponse', $schema)];

        self::assertTrue($paths->validate(), implode("\n", $paths->getErrors()));
        $actual = $this->locator->getNamedSchemas($paths);
        self::assertEquals($expected, $actual);
    }
}

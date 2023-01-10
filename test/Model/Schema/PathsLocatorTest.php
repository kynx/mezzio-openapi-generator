<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Paths;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathsLocator;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 * @uses \Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathsLocator
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
        $schema        = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $get           = new Operation([
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
        ]);
        $paths         = $this->getPaths([
            '/my/pets' => [
                'get' => $get,
            ],
        ]);
        $schemaPointer = '/paths/~1my~1pets/get/responses/default/content/application~1json/schema';
        $expected      = [
            $schemaPointer          => new NamedSpecification('my pets defaultResponse', $schema),
            '/paths/~1my~1pets/get' => new NamedSpecification('my petsOperation', $get),
        ];

        $actual = $this->locator->getNamedSchemas($paths);
        self::assertEquals($expected, $actual);
    }

    /**
     * @param  array<array-key, array<array-key, mixed>> $spec
     */
    private function getPaths(array $spec): Paths
    {
        $paths = new Paths($spec);
        $paths->setDocumentContext(new OpenApi([]), new JsonPointer('/paths'));
        self::assertTrue($paths->validate(), implode("\n", $paths->getErrors()));

        return $paths;
    }
}

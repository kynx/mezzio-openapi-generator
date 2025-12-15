<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Paths;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathsLocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function implode;

#[CoversClass(PathsLocator::class)]
#[UsesClass(MediaTypeLocator::class)]
#[UsesClass(NamedSpecification::class)]
#[UsesClass(OperationLocator::class)]
#[UsesClass(ParameterLocator::class)]
#[UsesClass(PathItemLocator::class)]
#[UsesClass(RequestBodyLocator::class)]
#[UsesClass(ResponseLocator::class)]
#[UsesClass(SchemaLocator::class)]
#[UsesClass(ModelUtil::class)]
#[UsesClass(RouteUtil::class)]
final class PathsLocatorTest extends TestCase
{
    private PathsLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new PathsLocator(new PathItemLocator());
    }

    public function testGetNamedSchemasSkipsNullPath(): void
    {
        $paths = new Paths([
            '/pets' => null,
        ]);

        self::assertTrue($paths->validate(), implode("\n", $paths->getErrors()));
        $actual = $this->locator->getNamedSpecifications($paths);
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
            $schemaPointer => new NamedSpecification('Path my pets get defaultResponse', $schema),
        ];

        $actual = $this->locator->getNamedSpecifications($paths);
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

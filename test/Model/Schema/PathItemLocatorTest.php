<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator
 */
final class PathItemLocatorTest extends TestCase
{
    private PathItemLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new PathItemLocator();
    }

    public function testGetNamedSchemasSingleOperationUsesBaseName(): void
    {
        $schema        = $this->getSchema();
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
        $pathItem      = $this->getPathItem([
            'get' => $get,
        ]);
        $schemaPointer = '/paths/pet/get/responses/default/content/application~1json/schema';
        $expected      = [
            $schemaPointer   => new NamedSpecification('Foo defaultResponse', $schema),
            '/paths/pet/get' => new NamedSpecification('FooOperation', $get),
        ];

        self::assertTrue($pathItem->validate(), implode("\n", $pathItem->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $pathItem);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasAppendsOperationMethod(): void
    {
        $getSchema  = $this->getSchema();
        $postSchema = $this->getSchema();
        $get        = new Operation([
            'responses' => [
                'default' => [
                    'description' => 'Pets',
                    'content'     => [
                        'application/json' => [
                            'schema' => $getSchema,
                        ],
                    ],
                ],
            ],
        ]);
        $post       = new Operation([
            'responses' => [
                'default' => [
                    'description' => 'Pets',
                    'content'     => [
                        'application/json' => [
                            'schema' => $postSchema,
                        ],
                    ],
                ],
            ],
        ]);
        $pathItem   = $this->getPathItem([
            'get'  => $get,
            'post' => $post,
        ]);
        $subPointer = '/responses/default/content/application~1json/schema';
        $expected   = [
            '/paths/pet/get' . $subPointer  => new NamedSpecification('Foo get defaultResponse', $getSchema),
            '/paths/pet/post' . $subPointer => new NamedSpecification('Foo post defaultResponse', $postSchema),
            '/paths/pet/get'                => new NamedSpecification('Foo getOperation', $get),
            '/paths/pet/post'               => new NamedSpecification('Foo postOperation', $post),
        ];

        self::assertTrue($pathItem->validate(), implode("\n", $pathItem->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $pathItem);
        self::assertEquals($expected, $actual);
    }

    private function getPathItem(array $spec): PathItem
    {
        $pathItem = new PathItem($spec);
        $pathItem->setDocumentContext(new OpenApi([]), new JsonPointer('/paths/pet'));
        return $pathItem;
    }

    private function getSchema(): Schema
    {
        return new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
    }
}

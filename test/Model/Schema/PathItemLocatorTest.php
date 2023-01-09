<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSchema
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
        $schema   = $this->getSchema();
        $pathItem = new PathItem([
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
        ]);
        $expected = ['' => new NamedSchema('Foo defaultResponse', $schema)];

        self::assertTrue($pathItem->validate(), implode("\n", $pathItem->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $pathItem);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasAppendsOperationMethod(): void
    {
        $getSchema  = $this->getSchema();
        $postSchema = $this->getSchema();
        $pathItem   = new PathItem([
            'get'  => [
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
            ],
            'post' => [
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
            ],
        ]);
        $pathItem->setDocumentContext(new OpenApi([]), new JsonPointer('/paths/pet'));
        $subPointer = '/responses/default/content/application~1json/schema';
        $expected   = [
            '/paths/pet/get' . $subPointer  => new NamedSchema('Foo get defaultResponse', $getSchema),
            '/paths/pet/post' . $subPointer => new NamedSchema('Foo post defaultResponse', $postSchema),
        ];

        self::assertTrue($pathItem->validate(), implode("\n", $pathItem->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $pathItem);
        self::assertEquals($expected, $actual);
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

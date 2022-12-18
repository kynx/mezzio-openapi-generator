<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\Model;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\PathItemLocator;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\OperationLocator
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Locator\PathItemLocator
 */
final class PathItemLocatorTest extends TestCase
{
    private PathItemLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new PathItemLocator();
    }

    public function testGetModelsSingleOperationUsesBaseName(): void
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
        $expected = ['' => new Model('Foo defaultResponse', $schema)];

        self::assertTrue($pathItem->validate(), implode("\n", $pathItem->getErrors()));
        $actual = $this->locator->getModels('Foo', $pathItem);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsAppendsOperationMethod(): void
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
            '/paths/pet/get' . $subPointer  => new Model('Foo get defaultResponse', $getSchema),
            '/paths/pet/post' . $subPointer => new Model('Foo post defaultResponse', $postSchema),
        ];

        self::assertTrue($pathItem->validate(), implode("\n", $pathItem->getErrors()));
        $actual = $this->locator->getModels('Foo', $pathItem);
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

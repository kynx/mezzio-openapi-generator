<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Operation\Schema\PathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\Schema\PathItemLocator
 */
final class PathItemLocatorTest extends TestCase
{
    private PathItemLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new PathItemLocator();
    }

    public function testGetNamesSpecificationReturnsOperations(): void
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
        $expected   = [
            '/paths/pet/get'  => new NamedSpecification('Foo get Operation', $get),
            '/paths/pet/post' => new NamedSpecification('Foo post Operation', $post),
        ];

        self::assertTrue($pathItem->validate(), implode("\n", $pathItem->getErrors()));
        $actual = $this->locator->getNamedSpecifications('Foo', $pathItem);
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

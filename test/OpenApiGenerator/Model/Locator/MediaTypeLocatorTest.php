<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\MediaTypeLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Locator\MediaTypeLocator
 */
final class MediaTypeLocatorTest extends TestCase
{
    private MediaTypeLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new MediaTypeLocator();
    }

    public function testGetModelsReturnsEmpty(): void
    {
        $actual = $this->locator->getModels('Foo', []);
        self::assertEmpty($actual);
    }

    public function testGetModelsUnresolvedReferenceThrowsException(): void
    {
        $ref        = '#/components/schemas/Pet';
        $mediaTypes = [
            'application/json' => new MediaType(['schema' => new Reference(['$ref' => $ref])]),
        ];

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unresolved reference: '$ref'");
        $this->locator->getModels('', $mediaTypes);
    }

    public function testGetModelsIgnoresNullSchemas(): void
    {
        $mediaTypes = [
            'application/json' => new MediaType(['schema' => null]),
        ];

        $actual = $this->locator->getModels('', $mediaTypes);
        self::assertEmpty($actual);
    }

    public function testGetModelsRemovesDuplicates(): void
    {
        $pointer   = '/components/schemas/Pet';
        $duplicate = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $duplicate->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $mediaTypes = [
            'application/json' => new MediaType([
                'schema' => $duplicate,
            ]),
            'application/xml'  => new MediaType([
                'schema' => $duplicate,
            ]),
        ];
        $expected   = [$pointer => new NamedSchema('Pet', $duplicate)];

        foreach ($mediaTypes as $mediaType) {
            self::assertTrue($mediaType->validate());
        }
        $actual = $this->locator->getModels('', $mediaTypes);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsAppendsType(): void
    {
        $json = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $json->setDocumentContext(new OpenApi([]), new JsonPointer('/a/b/c'));
        $xml = new Schema([
            'type'       => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $xml->setDocumentContext(new OpenApi([]), new JsonPointer('/a/b/d'));
        $mediaTypes = [
            'application/json' => new MediaType(['schema' => $json]),
            'application/xml'  => new MediaType(['schema' => $xml]),
        ];
        $expected   = [
            '/a/b/c' => new NamedSchema('FooJson', $json),
            '/a/b/d' => new NamedSchema('FooXml', $xml),
        ];

        $actual = $this->locator->getModels('Foo', $mediaTypes);
        self::assertEquals($expected, $actual);
    }
}

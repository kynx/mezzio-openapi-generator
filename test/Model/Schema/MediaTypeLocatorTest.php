<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MediaTypeLocator::class)]
#[UsesClass(NamedSpecification::class)]
#[UsesClass(SchemaLocator::class)]
#[UsesClass(ModelException::class)]
#[UsesClass(ModelUtil::class)]
final class MediaTypeLocatorTest extends TestCase
{
    private MediaTypeLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new MediaTypeLocator();
    }

    public function testGetNamedSchemasReturnsEmpty(): void
    {
        $actual = $this->locator->getNamedSpecifications('Foo', []);
        self::assertEmpty($actual);
    }

    public function testGetNamedSchemasUnresolvedReferenceThrowsException(): void
    {
        $ref        = '#/components/schemas/Pet';
        $mediaTypes = [
            'application/json' => new MediaType(['schema' => new Reference(['$ref' => $ref])]),
        ];

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unresolved reference: '$ref'");
        $this->locator->getNamedSpecifications('', $mediaTypes);
    }

    public function testGetNamedSchemasIgnoresNullSchemas(): void
    {
        $mediaTypes = [
            'application/json' => new MediaType(['schema' => null]),
        ];

        $actual = $this->locator->getNamedSpecifications('', $mediaTypes);
        self::assertEmpty($actual);
    }

    public function testGetNamedSchemasRemovesDuplicates(): void
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
        $expected   = [$pointer => new NamedSpecification('schema Pet', $duplicate)];

        foreach ($mediaTypes as $mediaType) {
            self::assertTrue($mediaType->validate());
        }
        $actual = $this->locator->getNamedSpecifications('', $mediaTypes);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasAppendsType(): void
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
            '/a/b/c' => new NamedSpecification('Foo application json', $json),
            '/a/b/d' => new NamedSpecification('Foo application xml', $xml),
        ];

        $actual = $this->locator->getNamedSpecifications('Foo', $mediaTypes);
        self::assertEquals($expected, $actual);
    }
}

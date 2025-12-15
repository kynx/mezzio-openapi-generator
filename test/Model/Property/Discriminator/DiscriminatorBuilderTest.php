<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property\Discriminator;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\DiscriminatorBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function array_merge;
use function array_values;
use function implode;

#[CoversClass(DiscriminatorBuilder::class)]
final class DiscriminatorBuilderTest extends TestCase
{
    private DiscriminatorBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new DiscriminatorBuilder();
    }

    public function testGetDiscriminatorReturnsNull(): void
    {
        $schema = new Schema([]);
        $actual = $this->builder->getDiscriminator($schema, []);
        self::assertNull($actual);
    }

    public function testGetDiscriminatorReturnsPropertyValue(): void
    {
        $expected = new PropertyValue('pet_type', ['Cat' => '\\Cat', 'Dog' => '\\Dog', 'pooch' => '\\Dog']);
        $schema   = $this->getSchema([
            'discriminator' => [
                'propertyName' => 'pet_type',
                'mapping'      => [
                    'pooch' => '#/components/schemas/Dog',
                ],
            ],
        ]);
        $classMap = [
            '/components/schemas/Cat' => '\\Cat',
            '/components/schemas/Dog' => '\\Dog',
        ];

        $actual = $this->builder->getDiscriminator($schema, $classMap);
        self::assertEquals($expected, $actual);
    }

    public function testGetDiscriminatorUsesDiscriminatorFromAllOf(): void
    {
        $expected = new PropertyValue('pet_type', ['Cat' => '\\Cat', 'Dog' => '\\Dog']);
        $pet      = new Schema([
            'type'          => 'object',
            'discriminator' => [
                'propertyName' => 'pet_type',
            ],
            'properties'    => [
                'pet_type' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $cat      = new Schema([
            'allOf' => [
                $pet,
                [
                    'type'       => 'object',
                    'properties' => [
                        'hunts' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ],
        ]);
        $dog      = new Schema([
            'allOf' => [
                $pet,
                [
                    'type'       => 'object',
                    'properties' => [
                        'barks' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ],
        ]);
        $schema   = $this->getSchema([], ['Pet' => $pet, 'Cat' => $cat, 'Dog' => $dog], ['Cat' => $cat, 'Dog' => $dog]);
        $classMap = [
            '/components/schemas/Cat' => '\\Cat',
            '/components/schemas/Dog' => '\\Dog',
        ];

        $actual = $this->builder->getDiscriminator($schema, $classMap);
        self::assertEquals($expected, $actual);
    }

    public function testGetDiscriminatorReturnsPropertyList(): void
    {
        $expected = new PropertyList(['\\Cat' => ['hunts'], '\\Dog' => ['barks']]);
        $schema   = $this->getSchema([]);
        $classMap = [
            '/components/schemas/Cat' => '\\Cat',
            '/components/schemas/Dog' => '\\Dog',
        ];

        $actual = $this->builder->getDiscriminator($schema, $classMap);
        self::assertEquals($expected, $actual);
    }

    /**
     * @param array<string, Schema>|null $components
     */
    private function getSchema(array $spec, array|null $components = null, array|null $oneOf = null): Schema
    {
        $components = $components ?? [
            'Cat' => new Schema([
                'type'       => 'object',
                'properties' => [
                    'hunts' => [
                        'type' => 'boolean',
                    ],
                ],
            ]),
            'Dog' => new Schema([
                'type'       => 'object',
                'properties' => [
                    'barks' => [
                        'type' => 'boolean',
                    ],
                ],
            ]),
        ];
        $oneOf      = $oneOf ?? array_values($components);
        $schema     = new Schema(array_merge(['oneOf' => $oneOf], $spec));

        $schema->setDocumentContext(new OpenApi([]), new JsonPointer(''));
        foreach ($components as $name => $component) {
            $component->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/' . $name));
        }
        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));

        return $schema;
    }
}

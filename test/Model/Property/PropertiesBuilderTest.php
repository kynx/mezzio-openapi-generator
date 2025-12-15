<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function implode;

#[CoversClass(PropertiesBuilder::class)]
#[UsesClass(ModelUtil::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyMetadata::class)]
#[UsesClass(PropertyType::class)]
#[UsesClass(SimpleProperty::class)]
final class PropertiesBuilderTest extends TestCase
{
    use OperationTrait;

    private PropertiesBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->getPropertiesBuilder();
    }

    public function testGetPropertiesAllOfSetsRequired(): void
    {
        $required = new PropertyMetadata('', '', true);
        $expected = [
            new SimpleProperty('$id', 'id', $required, PropertyType::Integer),
            new SimpleProperty('$name', 'name', new PropertyMetadata(), PropertyType::String),
        ];
        $schema   = $this->getSchema('/components/schemas/Foo', [
            'allOf' => [
                [
                    'type'       => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                    ],
                    'required'   => ['id'],
                ],
                [
                    'type'       => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getProperties($schema, []);
        self::assertEquals($expected, $actual);
    }

    public function testGetPropertiesAnyOfOverridesRequired(): void
    {
        $expected = [
            new SimpleProperty('$id', 'id', new PropertyMetadata(), PropertyType::Integer),
            new SimpleProperty('$name', 'name', new PropertyMetadata(), PropertyType::String),
        ];
        $schema   = $this->getSchema('/components/schemas/Foo', [
            'anyOf' => [
                [
                    'type'       => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                    ],
                    'required'   => ['id'],
                ],
                [
                    'type'       => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ]);

        $actual = $this->builder->getProperties($schema, []);
        self::assertEquals($expected, $actual);
    }

    public function testGetPropertiesCreatesUniqueNames(): void
    {
        $expected = [
            new SimpleProperty('$euro1', '€', new PropertyMetadata(), PropertyType::Integer),
            new SimpleProperty('$euro2', 'Euro', new PropertyMetadata(), PropertyType::String),
        ];
        $schema   = $this->getSchema('/components/schemas/Foo', [
            'type'       => 'object',
            'properties' => [
                '€'    => [
                    'type' => 'integer',
                ],
                'Euro' => [
                    'type' => 'string',
                ],
            ],
        ]);

        $actual = $this->builder->getProperties($schema, []);
        self::assertEquals($expected, $actual);
    }

    public function testGetPropertiesUsesClassNames(): void
    {
        $expected   = [
            new SimpleProperty('$bar', 'bar', new PropertyMetadata(), new ClassString('\\Bar')),
        ];
        $bar        = $this->getSchema('/components/schemas/Bar', []);
        $schema     = $this->getSchema('/components/schemas/Foo', [
            'type'       => 'object',
            'properties' => [
                'bar' => $bar,
            ],
        ]);
        $classNames = ['/components/schemas/Bar' => '\\Bar'];

        // need to reset context to simulate referenced schema
        $bar->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/Bar'));

        $actual = $this->builder->getProperties($schema, $classNames);
        self::assertEquals($expected, $actual);
    }

    private function getSchema(string $pointer, array $spec): Schema
    {
        $schema = new Schema($spec);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));
        return $schema;
    }
}

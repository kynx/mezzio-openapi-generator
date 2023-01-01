<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Code\Normalizer\ConstantNameNormalizer;
use Kynx\Code\Normalizer\UniqueConstantLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
use Kynx\Code\Normalizer\WordCase;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumCase;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder
 */
final class ModelsBuilderTest extends TestCase
{
    private ModelsBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $propertiesBuilder = new PropertiesBuilder(
            new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix())
        );
        $caseLabeler       = new UniqueConstantLabeler(
            new ConstantNameNormalizer('Case', WordCase::Pascal),
            new NumberSuffix()
        );

        $this->builder = new ModelsBuilder($propertiesBuilder, $caseLabeler);
    }

    public function testGetModelsReturnsEnum(): void
    {
        $pointer     = '/components/schemas/Foo';
        $expected    = [
            new EnumModel(
                '\\Foo',
                '/components/schemas/Foo',
                new EnumCase('Cat', 'cat'),
                new EnumCase('Dog', 'dog'),
                new EnumCase('BoolCase', 'bool')
            ),
        ];
        $namedSchema = $this->getNamedSchema('Foo', [
            'type' => 'string',
            'enum' => ['cat', 'dog', 'bool'],
        ]);
        $classNames  = [$pointer => '\\Foo'];

        $actual = $this->builder->getModels($namedSchema, $classNames, []);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsReturnsInterfaceAndClass(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $pointer          = '/components/schemas/Foo';
        $expected         = [
            new InterfaceModel(
                '\\FooInterface',
                $pointer,
                new SimpleProperty('$a', 'a', $propertyMetadata, PropertyType::String)
            ),
            new ClassModel(
                '\\Foo',
                $pointer,
                ['\\FooInterface'],
                new SimpleProperty('$a', 'a', $propertyMetadata, PropertyType::String)
            ),
        ];
        $namedSchema      = $this->getNamedSchema('Foo', [
            'type'       => 'object',
            'properties' => [
                'a' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $classNames       = [$pointer => '\\Foo'];
        $interfaceNames   = [$pointer => '\\FooInterface'];

        $actual = $this->builder->getModels($namedSchema, $classNames, $interfaceNames);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelsAllOfSetsImplements(): void
    {
        $expected       = [
            new ClassModel(
                '\\Foo',
                '/components/schemas/Foo',
                ['\\BarInterface', '\\BazInterface']
            ),
        ];
        $bar            = $this->getNamedSchema('Bar', []);
        $baz            = $this->getNamedSchema('Baz', []);
        $foo            = $this->getNamedSchema('Foo', [
            'allOf' => [
                $bar->getSchema(),
                $baz->getSchema(),
            ],
        ]);
        $classNames     = [
            '/components/schemas/Foo' => '\\Foo',
            '/components/schemas/Bar' => '\\Bar',
            '/components/schemas/Baz' => '\\Baz',
        ];
        $interfaceNames = [
            '/components/schemas/Bar' => '\\BarInterface',
            '/components/schemas/Baz' => '\\BazInterface',
        ];

        // need to reset documentContext to emulate references...
        $bar->getSchema()->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/Bar'));
        $baz->getSchema()->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/Baz'));

        $actual = $this->builder->getModels($foo, $classNames, $interfaceNames);
        self::assertEquals($expected, $actual);
    }

    private function getNamedSchema(string $name, array $spec): NamedSchema
    {
        $schema = new Schema($spec);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/' . $name));
        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));

        return new NamedSchema($name, $schema);
    }
}
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
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\EnumCase
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\EnumModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ClassModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 *
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
        $namedSchema = $this->getNamedSpecification('Foo', [
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
        $namedSchema      = $this->getNamedSpecification('Foo', [
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
        $bar            = $this->getNamedSpecification('Bar', []);
        $baz            = $this->getNamedSpecification('Baz', []);
        $foo            = $this->getNamedSpecification('Foo', [
            'allOf' => [
                $bar->getSpecification(),
                $baz->getSpecification(),
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
        $bar->getSpecification()->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/Bar'));
        $baz->getSpecification()->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/Baz'));

        $actual = $this->builder->getModels($foo, $classNames, $interfaceNames);
        self::assertEquals($expected, $actual);
    }

    private function getNamedSpecification(string $name, array $spec): NamedSpecification
    {
        $schema = new Schema($spec);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/' . $name));
        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));

        return new NamedSpecification($name, $schema);
    }
}

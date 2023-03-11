<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ClassModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelException
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer
 * @uses \Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder
 */
final class ModelCollectionBuilderTest extends TestCase
{
    use ModelTrait;
    use OperationTrait;

    private ModelCollectionBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $modelsBuilder = $this->getModelsBuilder($this->getPropertiesBuilder());
        $classLabeler  = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $classNamer    = new NamespacedNamer('', $classLabeler);

        $this->builder = new ModelCollectionBuilder(
            $classNamer,
            $modelsBuilder
        );
    }

    public function testGetModelCollectionReturnsUniqueClassNames(): void
    {
        $expected = new ModelCollection();
        $expected->add(new ClassModel('\\Euro1', '/components/schemas/€', []));
        $expected->add(new ClassModel('\\Euro2', '/components/schemas/euro', []));
        $namedSchemas = [
            $this->getNamedSchema('€', [
                'type' => 'object',
            ]),
            $this->getNamedSchema('euro', [
                'type' => 'object',
            ]),
        ];

        $actual = $this->builder->getModelCollection($namedSchemas);
        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionAllOfReturnsInterfaceAndClasses(): void
    {
        $expected = new ModelCollection();
        $expected->add(new ClassModel(
            '\\Foo',
            '/components/schemas/Foo',
            ['\\BarInterface', '\\BazInterface']
        ));
        $expected->add(new InterfaceModel('\\BarInterface', '/components/schemas/Bar'));
        $expected->add(new ClassModel('\\Bar', '/components/schemas/Bar', ['\\BarInterface']));
        $expected->add(new InterfaceModel('\\BazInterface', '/components/schemas/Baz'));
        $expected->add(new ClassModel('\\Baz', '/components/schemas/Baz', ['\\BazInterface']));

        $bar = $this->getNamedSchema('Bar', [
            'type' => 'object',
        ]);
        $baz = $this->getNamedSchema('Baz', [
            'type' => 'object',
        ]);
        $foo = $this->getNamedSchema('Foo', [
            'allOf' => [
                $bar->getSpecification(),
                $baz->getSpecification(),
            ],
        ]);

        // need to reset documentContext to emulate references...
        $bar->getSpecification()->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/Bar'));
        $baz->getSpecification()->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/Baz'));

        $actual = $this->builder->getModelCollection([$foo, $bar, $baz]);
        self::assertEquals($expected, $actual);
    }

    private function getNamedSchema(string $name, array $spec): NamedSpecification
    {
        $schema = new Schema($spec);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/' . $name));
        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));

        return new NamedSpecification($name, $schema);
    }
}

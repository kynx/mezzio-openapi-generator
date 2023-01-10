<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Schema;
use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\ConstantNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueConstantLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
use Kynx\Code\Normalizer\WordCase;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamespacedNamer;
use Kynx\Mezzio\OpenApiGenerator\Model\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ClassModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelException
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamespacedNamer
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder
 */
final class ModelCollectionBuilderTest extends TestCase
{
    private ModelCollectionBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $propertyLabeler   = new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix());
        $propertiesBuilder = new PropertiesBuilder($propertyLabeler);
        $caseLabeler       = new UniqueConstantLabeler(
            new ConstantNameNormalizer('Case', WordCase::Pascal),
            new NumberSuffix()
        );
        $modelsBuilder     = new ModelsBuilder($propertiesBuilder, $caseLabeler);
        $operationBuilder  = new OperationBuilder($propertyLabeler);
        $classLabeler      = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $classNamer        = new NamespacedNamer('', $classLabeler);

        $this->builder = new ModelCollectionBuilder(
            $classNamer,
            $modelsBuilder,
            $operationBuilder
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

    public function testGetModelCollectionReturnsOperation(): void
    {
        $expected = new ModelCollection();
        $expected->add(
            new ClassModel(
                '\\PatchOperation',
                '/paths/foo/patch',
                [],
                new SimpleProperty('$requestBody', '', new PropertyMetadata(), PropertyType::String)
            )
        );
        $operation = new Operation([
            'requestBody' => [
                'content' => [
                    'default' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
            'responses'   => [],
        ]);
        $operation->setDocumentContext(new OpenApi([]), new JsonPointer('/paths/foo/patch'));
        self::assertTrue($operation->validate(), implode("\n", $operation->getErrors()));

        $namedSpec = new NamedSpecification('PatchOperation', $operation);

        $actual = $this->builder->getModelCollection([$namedSpec]);
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

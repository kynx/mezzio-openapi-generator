<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
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
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamespacedNamer;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder
 */
final class ModelCollectionBuilderTest extends TestCase
{
    private ModelCollectionBuilder $builder;

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
        $modelsBuilder     = new ModelsBuilder($propertiesBuilder, $caseLabeler);
        $classLabeler      = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $classNamer        = new NamespacedNamer('', $classLabeler);

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
                $bar->getSchema(),
                $baz->getSchema(),
            ],
        ]);

        // need to reset documentContext to emulate references...
        $bar->getSchema()->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/Bar'));
        $baz->getSchema()->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/Baz'));

        $actual = $this->builder->getModelCollection([$foo, $bar, $baz]);
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

<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\Reader;
use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\ConstantNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueConstantLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
use Kynx\Code\Normalizer\WordCase;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumCase;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\Namer\FlatNamer;
use Kynx\Mezzio\OpenApiGenerator\Model\OpenApiParser;
use Kynx\Mezzio\OpenApiGenerator\Model\Property;
use Kynx\Mezzio\OpenApiGenerator\Model\PropertyType;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\OpenApiParser
 * @psalm-suppress InternalClass
 * @psalm-suppress InternalMethod
 */
final class OpenApiParserTest extends TestCase
{
    private const ASSET_NAMESPACE = __NAMESPACE__ . '\\Asset';
    private FlatNamer $classNamer;
    private UniqueVariableLabeler $propertyLabeler;
    private UniqueConstantLabeler $caseLabeler;

    protected function setUp(): void
    {
        parent::setUp();

        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $this->classNamer = new FlatNamer(self::ASSET_NAMESPACE, $classLabeler);
        $this->propertyLabeler = new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix());
        $this->caseLabeler = new UniqueConstantLabeler(
            new ConstantNameNormalizer('Case', WordCase::Pascal),
            new NumberSuffix()
        );
    }

    public function testGetModelCollectionNoSchemasReturnsEmptyCollection(): void
    {
        $parser = $this->getOpenApiParser('no-schemas.yaml');

        $actual = $parser->getModelCollection();
        self::assertCount(0, $actual);
    }

    public function testGetModelCollectionReturnsModelCollection(): void
    {
        $parser = $this->getOpenApiParser('simple.yaml');
        $firstPointer = '/components/schemas/First';
        $firstProperties = [
            'id' => new Property('$id', false, PropertyType::Integer),
            'name' => new Property('$name', false, [PropertyType::String, PropertyType::Null]),
        ];
        $secondPointer = '/components/schemas/Second';
        $secondProperties = [
            'bits' => new Property('$bits', true, PropertyType::List),
        ];

        $models = [
            new ClassModel(self::ASSET_NAMESPACE . '\\First', $firstPointer, [], ...$firstProperties),
            new ClassModel(self::ASSET_NAMESPACE . '\\Second', $secondPointer, [], ...$secondProperties),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $parser->getModelCollection();

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsInlineModel(): void
    {
        $parser = $this->getOpenApiParser('inline.yaml');
        $anonPointer = '/components/schemas/First/properties/anon';
        $anonProperties = [
            'id' => new Property('$id', false, PropertyType::Integer),
            'name' => new Property('$name', false, PropertyType::String),
        ];
        $anonClass = self::ASSET_NAMESPACE . '\\FirstAnon';

        $firstPointer = '/components/schemas/First';
        $firstProperties = [
            'anon' => new Property('$anon', false, $anonClass),
        ];

        $models = [
            new ClassModel(self::ASSET_NAMESPACE . '\\FirstAnon', $anonPointer, [], ...$anonProperties),
            new ClassModel(self::ASSET_NAMESPACE . '\\First', $firstPointer, [], ...$firstProperties),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $parser->getModelCollection();

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsEnum(): void
    {
        $parser = $this->getOpenApiParser('enum.yaml');
        $enumPointer = '/components/schemas/AnEnum';
        $enumClass = self::ASSET_NAMESPACE . '\\AnEnum';
        $enumCases = [
            new EnumCase('FirstVal', 'first val'),
            new EnumCase('SecondVal', 'second val')
        ];

        $firstPointer = '/components/schemas/First';
        $firstProperties = [
            'enum' => new Property('$enum', false, $enumClass),
        ];

        $models = [
            new EnumModel($enumClass, $enumPointer, ...$enumCases),
            new ClassModel(self::ASSET_NAMESPACE . '\\First', $firstPointer, [], ...$firstProperties),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $parser->getModelCollection();

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsAllOf(): void
    {
        $parser = $this->getOpenApiParser('all-of.yaml');
        $petPointer = '/components/schemas/Pet';
        $petProperties = [
            'petType' => new Property('$petType', true, PropertyType::String),
        ];
        $petInterface = self::ASSET_NAMESPACE . '\\PetInterface';
        $catPointer = '/components/schemas/Cat';
        $catProperties = [
            'petType' => new Property('$petType', true, PropertyType::String),
            'name' => new Property('$name', true, PropertyType::String),
        ];
        $dogPointer = '/components/schemas/Dog';
        $dogProperties = [
            'petType' => new Property('$petType', true, PropertyType::String),
            'bark' => new Property('$bark', false, PropertyType::String),
        ];

        $models = [
            new InterfaceModel($petInterface, $petPointer, ...$petProperties),
            new ClassModel(self::ASSET_NAMESPACE . '\\Pet', $petPointer, [$petInterface], ...$petProperties),
            new ClassModel(self::ASSET_NAMESPACE . '\\Cat', $catPointer, [$petInterface], ...$catProperties),
            new ClassModel(self::ASSET_NAMESPACE . '\\Dog', $dogPointer, [$petInterface], ...$dogProperties),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $parser->getModelCollection();

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsAnyOf(): void
    {
        $parser = $this->getOpenApiParser('any-of.yaml');
        $enumClass = self::ASSET_NAMESPACE . '\\PetByTypePetType';
        $enumPointer = '/components/schemas/PetByType/properties/pet_type';
        $enumCases = [
            new EnumCase('Cat', 'Cat'),
            new EnumCase('Dog', 'Dog'),
        ];
        $requestPointer = '/components/schemas/PetRequest';
        $requestProperties = [
            'age' => new Property('$age', false, PropertyType::Integer),
            'nickname' => new Property('$nickname', false, PropertyType::String),
            'pet_type' => new Property('$petType', false, $enumClass),
            'hunts' => new Property('$hunts', false, PropertyType::Boolean),
        ];
        $agePointer = '/components/schemas/PetByAge';
        $ageProperties = [
            'age' => new Property('$age', true, PropertyType::Integer),
            'nickname' => new Property('$nickname', false, PropertyType::String),
        ];
        $typePointer = '/components/schemas/PetByType';
        $typeProperties = [
            'pet_type' => new Property('$petType', true, $enumClass),
            'hunts' => new Property('$hunts', false, PropertyType::Boolean)
        ];

        $models = [
            new EnumModel($enumClass, $enumPointer, ...$enumCases),
            new ClassModel(self::ASSET_NAMESPACE . '\\PetRequest', $requestPointer, [], ...$requestProperties),
            new ClassModel(self::ASSET_NAMESPACE . '\\PetByAge', $agePointer, [], ...$ageProperties),
            new ClassModel(self::ASSET_NAMESPACE . '\\PetByType', $typePointer, [], ...$typeProperties),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $parser->getModelCollection();

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsUntypedEnum(): void
    {
        $parser = $this->getOpenApiParser('enum-untyped.yaml');
        $untypedPointer = '/components/schemas/Untyped';
        $types = [
            PropertyType::Boolean,
            PropertyType::Float,
            PropertyType::Integer,
            PropertyType::Null,
            PropertyType::String,
        ];
        $untypedProperties = [
            'foo' => new Property('$foo', false, $types)
        ];
        $models = [
            new ClassModel(self::ASSET_NAMESPACE . '\\Untyped', $untypedPointer, [], ...$untypedProperties),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $parser->getModelCollection();

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsOneOfScalar(): void
    {
        $parser = $this->getOpenApiParser('one-of-scalar.yaml');
        $scalarPointer = '/components/schemas/Scalar';
        $scalarProperties = [
            'foo' => new Property('$foo', false, [PropertyType::String, PropertyType::Float]),
        ];
        $models = [
            new ClassModel(self::ASSET_NAMESPACE . '\\Scalar', $scalarPointer, [], ...$scalarProperties),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $parser->getModelCollection();

        self::assertEquals($expected, $actual);
    }

    private function getOpenApiParser(string $file): OpenApiParser
    {
        $openApi = Reader::readFromYamlFile(__DIR__ . '/Asset/' . $file);
        self::assertTrue($openApi->validate(), "Invalid openapi schema");

        return new OpenApiParser($openApi, $this->classNamer, $this->propertyLabeler, $this->caseLabeler);
    }
}

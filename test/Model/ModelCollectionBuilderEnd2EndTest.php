<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\Reader;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumCase;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathsLocator;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @coversNothing
 */
final class ModelCollectionBuilderEnd2EndTest extends TestCase
{
    use ModelTrait;
    use OperationTrait;

    private OpenApiLocator $locator;
    private ModelCollectionBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new OpenApiLocator(new PathsLocator(new PathItemLocator()));
        $this->builder = $this->getModelCollectionBuilder($this->getPropertiesBuilder(), '');
    }

    public function testGetModelCollectionNoSchemasReturnsEmptyCollection(): void
    {
        $unresolved = $this->getUnresolved('no-schemas.yaml');

        $actual = $this->builder->getModelCollection($unresolved);
        self::assertCount(0, $actual);
    }

    public function testGetModelCollectionReturnsModelCollection(): void
    {
        $unresolved = $this->getUnresolved('simple.yaml');

        $nullable         = new PropertyMetadata(...['nullable' => true]);
        $required         = new PropertyMetadata(...['required' => true]);
        $firstPointer     = '/components/schemas/First';
        $firstProperties  = [
            new SimpleProperty('$id', 'id', new PropertyMetadata(), PropertyType::Integer),
            new SimpleProperty('$name', 'name', $nullable, PropertyType::String),
        ];
        $secondPointer    = '/components/schemas/Second';
        $secondProperties = [
            new SimpleProperty('$bits', 'bits', $required, PropertyType::Array),
        ];

        $models   = [
            new ClassModel('\\Schema\\First', $firstPointer, [], ...$firstProperties),
            new ClassModel('\\Schema\\Second', $secondPointer, [], ...$secondProperties),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $this->builder->getModelCollection($unresolved);

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsInlineModel(): void
    {
        $unresolved = $this->getUnresolved('inline.yaml');

        $anonPointer    = '/paths/~1first/get/responses/200/content/application~1json/schema';
        $anonProperties = [
            new SimpleProperty('$id', 'id', new PropertyMetadata(), PropertyType::Integer),
            new SimpleProperty('$name', 'name', new PropertyMetadata(), PropertyType::String),
        ];
        $anonClass      = '\\Path\\First\\Get\\Status200Response';

        $models   = [
            new ClassModel($anonClass, $anonPointer, [], ...$anonProperties),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $this->builder->getModelCollection($unresolved);

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsEnum(): void
    {
        $unresolved = $this->getUnresolved('enum.yaml');

        $enumPointer = '/components/schemas/AnEnum';
        $enumClass   = '\\Schema\\AnEnum';
        $enumCases   = [
            new EnumCase('FirstVal', 'first val'),
            new EnumCase('SecondVal', 'second val'),
        ];

        $firstPointer    = '/components/schemas/First';
        $firstProperties = [
            new SimpleProperty('$enum', 'enum', new PropertyMetadata(), new ClassString($enumClass, true)),
        ];

        $models   = [
            new ClassModel('\\Schema\\First', $firstPointer, [], ...$firstProperties),
            new EnumModel($enumClass, $enumPointer, ...$enumCases),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $this->builder->getModelCollection($unresolved);

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsAllOf(): void
    {
        $unresolved = $this->getUnresolved('all-of.yaml');

        $required        = new PropertyMetadata(...['required' => true]);
        $petTypeProperty = new SimpleProperty('$petType', 'petType', $required, PropertyType::String);

        $petPointer    = '/components/schemas/Pet';
        $petProperties = [$petTypeProperty];
        $petInterface  = '\\Schema\\PetInterface';
        $catPointer    = '/components/schemas/Cat';
        $catProperties = [
            $petTypeProperty,
            new SimpleProperty('$name', 'name', $required, PropertyType::String),
        ];
        $dogPointer    = '/components/schemas/Dog';
        $dogProperties = [
            $petTypeProperty,
            new SimpleProperty('$bark', 'bark', new PropertyMetadata(), PropertyType::String),
        ];

        $models   = [
            new ClassModel('\\Schema\\Cat', $catPointer, [$petInterface], ...$catProperties),
            new InterfaceModel($petInterface, $petPointer, ...$petProperties),
            new ClassModel('\\Schema\\Pet', $petPointer, [$petInterface], ...$petProperties),
            new ClassModel('\\Schema\\Dog', $dogPointer, [$petInterface], ...$dogProperties),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $this->builder->getModelCollection($unresolved);

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsAnyOf(): void
    {
        $unresolved = $this->getUnresolved('any-of.yaml');

        $enumClass   = '\\Schema\\PetByType\\PetType';
        $enumPointer = '/components/schemas/PetByType/properties/pet_type';
        $enumCases   = [
            new EnumCase('Cat', 'Cat'),
            new EnumCase('Dog', 'Dog'),
        ];

        $requestClass      = '\\Schema\\PetRequest';
        $requestPointer    = '/components/schemas/PetRequest';
        $requestProperties = [
            new SimpleProperty('$age', 'age', new PropertyMetadata(), PropertyType::Integer),
            new SimpleProperty('$nickname', 'nickname', new PropertyMetadata(), PropertyType::String),
            new SimpleProperty('$petType', 'pet_type', new PropertyMetadata(), new ClassString($enumClass, true)),
            new SimpleProperty('$hunts', 'hunts', new PropertyMetadata(), PropertyType::Boolean),
        ];

        $models   = [
            new ClassModel($requestClass, $requestPointer, [], ...$requestProperties),
            new EnumModel($enumClass, $enumPointer, ...$enumCases),
        ];
        $expected = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $this->builder->getModelCollection($unresolved);

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsUntypedEnum(): void
    {
        $unresolved = $this->getUnresolved('enum-untyped.yaml');

        $untypedPointer = '/components/schemas/Untyped';
        $types          = [
            PropertyType::Boolean,
            PropertyType::Number,
            PropertyType::Integer,
            PropertyType::Null,
            PropertyType::String,
        ];
        $nullable       = new PropertyMetadata(...['nullable' => true]);
        $union          = new UnionProperty('$foo', 'foo', $nullable, null, ...$types);
        $models         = [
            new ClassModel('\\Schema\\Untyped', $untypedPointer, [], $union),
        ];
        $expected       = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $this->builder->getModelCollection($unresolved);

        self::assertEquals($expected, $actual);
    }

    public function testGetModelCollectionReturnsOneOfScalar(): void
    {
        $unresolved = $this->getUnresolved('one-of-scalar.yaml');

        $scalarPointer    = '/components/schemas/Scalar';
        $scalarProperties = [
            new UnionProperty('$foo', 'foo', new PropertyMetadata(), null, PropertyType::String, PropertyType::Number),
        ];
        $models           = [
            new ClassModel('\\Schema\\Scalar', $scalarPointer, [], ...$scalarProperties),
        ];
        $expected         = new ModelCollection();
        foreach ($models as $model) {
            $expected->add($model);
        }

        $actual = $this->builder->getModelCollection($unresolved);

        self::assertEquals($expected, $actual);
    }

    /**
     * @return list<NamedSpecification>
     */
    private function getUnresolved(string $file): array
    {
        $openApi = Reader::readFromYamlFile(__DIR__ . '/Asset/' . $file);
        self::assertTrue($openApi->validate(), "Invalid openapi schema: " . implode("\n", $openApi->getErrors()));

        return $this->locator->getNamedSpecifications($openApi);
    }
}

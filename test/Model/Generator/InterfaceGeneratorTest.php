<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\InterfaceGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\AbstractProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function array_map;

#[CoversClass(InterfaceGenerator::class)]
#[UsesClass(AbstractClassLikeModel::class)]
#[UsesClass(AbstractGenerator::class)]
#[UsesClass(InterfaceModel::class)]
#[UsesClass(AbstractProperty::class)]
#[UsesClass(PropertyMetadata::class)]
#[UsesClass(PropertyType::class)]
#[UsesClass(SimpleProperty::class)]
#[UsesClass(UnionProperty::class)]
final class InterfaceGeneratorTest extends TestCase
{
    private InterfaceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new InterfaceGenerator();
    }

    public function testAddInterfaceAddsUses(): void
    {
        $expected   = [
            'C'  => 'B\\C',
            'DC' => 'D\\C',
        ];
        $properties = array_map(fn (string $className): ClassString => new ClassString($className), $expected);
        $model      = new InterfaceModel(
            '\\A\\B',
            '/B',
            new UnionProperty('$a', 'a', new PropertyMetadata(), null, ...$properties)
        );
        $namespace  = new PhpNamespace('A');
        $this->generator->addInterface($namespace, $model);

        $actual = $namespace->getUses();
        self::assertSame($expected, $actual);
    }

    public function testAddInterfaceAddsMethods(): void
    {
        $metadata  = new PropertyMetadata(required: true);
        $model     = new InterfaceModel(
            '\\A\\B',
            '/A/B',
            new SimpleProperty('$a', 'a', $metadata, PropertyType::String)
        );
        $namespace = new PhpNamespace('A');
        $interface = $this->generator->addInterface($namespace, $model);

        self::assertTrue($interface->hasMethod('getA'));
        $method = $interface->getMethod('getA');
        self::assertTrue($method->isPublic());
        self::assertSame('string', $method->getReturnType());
    }
}

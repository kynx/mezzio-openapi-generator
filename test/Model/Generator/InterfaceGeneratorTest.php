<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\Model\Generator\InterfaceGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Generator\AbstractGenerator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\AbstractProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Generator\InterfaceGenerator
 */
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
        $expected  = [
            'C'  => 'B\\C',
            'DC' => 'D\\C',
        ];
        $model     = new InterfaceModel(
            '\\A\\B',
            '/B',
            new UnionProperty('$a', 'a', new PropertyMetadata(), ...$expected)
        );
        $namespace = new PhpNamespace('A');
        $this->generator->addInterface($namespace, $model);

        $actual = $namespace->getUses();
        self::assertSame($expected, $actual);
    }

    public function testAddInterfaceAddsMethods(): void
    {
        $metadata  = new PropertyMetadata('', '', true);
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

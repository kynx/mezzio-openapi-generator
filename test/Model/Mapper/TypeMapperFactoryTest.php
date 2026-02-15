<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Mapper;

use cebe\openapi\spec\Schema;
use DateTimeImmutable;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\DateTimeImmutableMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapperFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(TypeMapperFactory::class)]
final class TypeMapperFactoryTest extends TestCase
{
    public function testInvokeReturnsConfiguredInstance(): void
    {
        $expected  = new ClassString(DateTimeImmutable::class);
        $schema    = new Schema(['type' => 'string', 'format' => 'date']);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn([
                ConfigProvider::GEN_KEY => [
                    'type-mappers' => [
                        DateTimeImmutableMapper::class,
                    ],
                ],
            ]);

        $factory  = new TypeMapperFactory();
        $instance = $factory($container);
        $actual   = $instance->map($schema);
        self::assertEquals($expected, $actual);
    }
}

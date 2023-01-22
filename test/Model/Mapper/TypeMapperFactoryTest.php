<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Mapper;

use cebe\openapi\spec\Schema;
use DateTimeImmutable;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\DateTimeImmutableMapper;
use Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapperFactory;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Mapper\TypeMapperFactory
 */
final class TypeMapperFactoryTest extends TestCase
{
    public function testInvokeReturnsConfiguredInstance(): void
    {
        $expected  = new ClassString(DateTimeImmutable::class);
        $schema    = new Schema(['type' => 'string', 'format' => 'date']);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('config')
            ->willReturn([
                'openapi-gen' => [
                    'type_mappers' => [
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

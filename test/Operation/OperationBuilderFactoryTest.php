<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Operation\ParameterBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(OperationBuilderFactory::class)]
#[UsesClass(OperationBuilder::class)]
final class OperationBuilderFactoryTest extends TestCase
{
    use OperationTrait;

    public function testInvokeReturnsInstance(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [ParameterBuilder::class, $this->getParameterBuilder()],
                [RequestBodyBuilder::class, $this->getRequestBodyBuilder()],
                [ResponseBuilder::class, $this->getResponseBuilder()],
            ]);
        $factory = new OperationBuilderFactory();

        $actual = $factory($container);
        self::assertInstanceOf(OperationBuilder::class, $actual);
    }
}

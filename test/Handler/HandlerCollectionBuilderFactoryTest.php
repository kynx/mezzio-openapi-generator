<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilderFactory
 */
final class HandlerCollectionBuilderFactoryTest extends TestCase
{
    use HandlerTrait;
    use OperationTrait;

    public function testInvokeReturnsConfiguredInstance(): void
    {
        $operations = $this->getOperationCollection($this->getOperations());
        $expected   = $this->getHandlerCollection($this->getHandlers($operations));

        $configuration = [
            ConfigProvider::GEN_KEY => [
                'handler-namespace' => 'Api\\Handler',
            ],
        ];
        $classLabeler  = new UniqueClassLabeler(new ClassNameNormalizer('Handler'), new NumberSuffix());
        $classNamer    = new NamespacedNamer('Api\\Handler', $classLabeler);
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', $configuration],
                [NamespacedNamer::class, $classNamer],
            ]);

        $factory  = new HandlerCollectionBuilderFactory();
        $instance = $factory($container);

        $routeCollection = new RouteCollection();
        $routeCollection->add(new RouteModel('/paths/~1foo/get', '/foo', 'get', [], [], null, []));
        $routeCollection->add(new RouteModel('/paths/~1bar/get', '/bar', 'get', [], [], null, []));

        $actual = $instance->getHandlerCollection($routeCollection, $operations);
        self::assertEquals($expected, $actual);
    }
}

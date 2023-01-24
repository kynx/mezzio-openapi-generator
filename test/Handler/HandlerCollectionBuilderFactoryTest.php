<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Configuration;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilderFactory;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilderFactory
 */
final class HandlerCollectionBuilderFactoryTest extends TestCase
{
    private const NAMESPACE = __NAMESPACE__ . '\\Handler';

    public function testInvokeReturnsConfiguredInstance(): void
    {
        $expected = new HandlerCollection();
        $expected->add(new HandlerModel('/paths/~1foo/get', self::NAMESPACE . '\\Foo\\GetHandler', null));

        $configuration = new Configuration(...[
            'projectDir'       => __DIR__,
            'baseNamespace'    => __NAMESPACE__,
            'handlerNamespace' => 'Handler',
        ]);
        $classLabeler  = new UniqueClassLabeler(new ClassNameNormalizer('Handler'), new NumberSuffix());
        $classNamer    = new NamespacedNamer(self::NAMESPACE, $classLabeler);
        $container     = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Configuration::class, $configuration],
                [NamespacedNamer::class, $classNamer],
            ]);

        $factory  = new HandlerCollectionBuilderFactory();
        $instance = $factory($container);

        $routeCollection = new RouteCollection();
        $routeCollection->add(new RouteModel('/paths/~1foo/get', '/foo', 'get', [], []));
        $operationCollection = new OperationCollection();

        $actual = $instance->getHandlerCollection($routeCollection, $operationCollection);
        self::assertEquals($expected, $actual);
    }
}

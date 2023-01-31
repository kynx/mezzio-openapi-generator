<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilder
 */
final class HandlerCollectionBuilderTest extends TestCase
{
    use HandlerTrait;
    use OperationTrait;

    private const NAMESPACE = __NAMESPACE__ . '\\Handler';

    private HandlerCollectionBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->getHandlerCollectionBuilder(self::NAMESPACE);
    }

    public function testGetHandlerCollectionReturnsCollection(): void
    {
        $routes     = [
            new RouteModel('/paths/~1foo/get', '/foo', 'get', [], []),
            new RouteModel('/paths/~1bar/post', '/bar', 'post', [], []),
        ];
        $operations = [
            new OperationModel('\\Foo\\Operation', '/paths/~1foo/get'),
            new OperationModel('\\Bar\\Operation', '/paths/~1bar/post', $this->getPathParams()),
        ];
        $classNames = [
            self::NAMESPACE . '\\Foo\\GetHandler',
            self::NAMESPACE . '\\Bar\\PostHandler',
        ];

        $expected = new HandlerCollection();
        $expected->add(new HandlerModel($routes[0]->getJsonPointer(), $classNames[0], null));
        $expected->add(new HandlerModel($routes[1]->getJsonPointer(), $classNames[1], $operations[1]));

        $routeCollection = new RouteCollection();
        foreach ($routes as $route) {
            $routeCollection->add($route);
        }
        $operationCollection = new OperationCollection();
        foreach ($operations as $operation) {
            $operationCollection->add($operation);
        }

        $actual = $this->builder->getHandlerCollection($routeCollection, $operationCollection);
        self::assertEquals($expected, $actual);
    }

    public function testGetHandlerCollectionRemovesParameterPlaceholders(): void
    {
        $pointer  = '/paths/~1foo~1{petId}/get';
        $expected = new HandlerCollection();
        $expected->add(new HandlerModel($pointer, self::NAMESPACE . '\\Foo\\PetId\\GetHandler', null));

        $routeCollection = new RouteCollection();
        $routeCollection->add(new RouteModel($pointer, '/foo/{petId}', 'get', [], []));

        $actual = $this->builder->getHandlerCollection($routeCollection, new OperationCollection());
        self::assertEquals($expected, $actual);
    }
}

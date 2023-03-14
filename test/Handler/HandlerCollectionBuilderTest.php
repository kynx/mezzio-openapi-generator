<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilder;
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

    private HandlerCollectionBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->getHandlerCollectionBuilder('Api\\Handler');
    }

    public function testGetHandlerCollectionReturnsCollection(): void
    {
        $routes     = [
            new RouteModel('/paths/~1foo/get', '/foo', 'get', [], [], []),
            new RouteModel('/paths/~1bar/post', '/bar', 'post', [], [], []),
        ];
        $operations = [
            new OperationModel(
                'Api\\Operation\\Foo\\Get\\Operation',
                '/paths/~1foo/get',
                $this->getPathParams()
            ),
            new OperationModel(
                'Api\\Operation\\Bar\\Post\\Operation',
                '/paths/~1bar/post',
                null,
                $this->getQueryParams()
            ),
        ];

        $expected = $this->getHandlerCollection($this->getHandlers($this->getOperationCollection($operations)));

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
        $pointer    = '/paths/~1foo~1{petId}/get';
        $operation  = new OperationModel('Api\\Operation\\Foo\\PetId\\Get\\Operation', $pointer);
        $operations = $this->getOperationCollection([$operation]);
        $expected   = $this->getHandlerCollection($this->getHandlers($operations));

        $routeCollection = new RouteCollection();
        $routeCollection->add(new RouteModel($pointer, '/foo/{petId}', 'get', [], [], []));

        $actual = $this->builder->getHandlerCollection($routeCollection, $operations);
        self::assertEquals($expected, $actual);
    }
}

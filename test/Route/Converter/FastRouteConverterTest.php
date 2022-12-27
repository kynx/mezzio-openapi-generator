<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route\Converter;

use cebe\openapi\spec\Operation;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\Converter\FastRouteConverter;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\Converter\FastRouteConverter
 */
final class FastRouteConverterTest extends TestCase
{
    private FastRouteConverter $routeConverter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeConverter = new FastRouteConverter();
    }

    /**
     * @dataProvider sortProvider
     */
    public function testSortReturnsSorted(
        string $aPath,
        string $aMethod,
        array $aParams,
        string $bPath,
        string $bMethod,
        array $bParams
    ): void {
        $a = new HandlerClass('Foo', $this->makeRoute($aPath, $aMethod, $aParams));
        $b = new HandlerClass('Bar', $this->makeRoute($bPath, $bMethod, $bParams));

        $expected   = [$a, $b];
        $collection = new HandlerCollection();
        $collection->add($b);
        $collection->add($a);

        $sorted = $this->routeConverter->sort($collection);
        $actual = iterator_to_array($sorted);
        self::assertSame($expected, $actual);
    }

    public function sortProvider(): array
    {
        return [
            'path'         => ['/a', 'get', [], '/b', 'get', []],
            'method'       => ['/a', 'get', [], '/a', 'post', []],
            'whitespace'   => [' /a ', 'get', [], '/b', 'get', []],
            'param'        => ['/a/c', 'get', [], '/a/{b}', 'get', ['b' => 'string']],
            'both_params'  => ['/a/{b}', 'get', ['b' => 'string'], '/b/{b}', 'get', ['b' => 'string']],
            'nested_param' => ['/a/{b}/c', 'get', ['b' => 'string'], '/a/{b}/d', 'get', ['b' => 'string']],
            'tilde'        => ['/~b', 'get', [], '/a', 'get', []],
            'tilde_param'  => ['/~', 'get', [], '/{a}', 'get', []],
        ];
    }

    /**
     * @dataProvider routeProvider
     */
    public function testConvertReturnsConverted(string $path, array $parameters, string $expected): void
    {
        $route  = $this->makeRoute($path, 'get', $parameters);
        $actual = $this->routeConverter->convert($route);
        self::assertSame($expected, $actual);
    }

    public function routeProvider(): array
    {
        return [
            'no_params' => ['/foo', [], '/foo'],
            'array'     => ['/foo/{arr}', ['arr' => 'array'], '/foo/{arr:.+}'],
            'boolean'   => ['/foo/{bool}', ['bool' => 'boolean'], '/foo/{bool:(true|false)}'],
            'integer'   => ['/foo/{int}', ['int' => 'integer'], '/foo/{int:\d+}'],
            'number'    => ['/foo/{num}', ['num' => 'number'], '/foo/{num:[\d.]+}'],
            'object'    => ['/foo/{obj}', ['obj' => 'object'], '/foo/{obj:.+}'],
            'string'    => ['/foo/{str}', ['str' => 'string'], '/foo/{str:.+}'],
            'multiple'  => [
                '/foo/{int}/{bool}',
                ['int' => 'integer', 'bool' => 'boolean'],
                '/foo/{int:\d+}/{bool:(true|false)}',
            ],
        ];
    }

    private function makeRoute(string $path, string $method, array $params): OpenApiRoute
    {
        $spec = [
            'parameters' => [],
        ];
        foreach ($params as $name => $type) {
            $spec['parameters'][] = $this->makeParam($name, $type);
        }
        return new OpenApiRoute($path, $method, new Operation($spec));
    }

    private function makeParam(string $name, string $type): array
    {
        return [
            'name'     => $name,
            'in'       => 'path',
            'required' => true,
            'schema'   => [
                'type' => $type,
            ],
        ];
    }
}

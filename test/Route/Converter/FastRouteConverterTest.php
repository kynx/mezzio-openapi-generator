<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route\Converter;

use Kynx\Mezzio\OpenApiGenerator\Route\Converter\FastRouteConverter;
use Kynx\Mezzio\OpenApiGenerator\Route\ParameterModel;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
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
        string $bPath,
        string $bMethod,
    ): void {
        $a = new RouteModel('/paths/foo', $aPath, $aMethod, [], [], null, []);
        $b = new RouteModel('/paths/foo', $bPath, $bMethod, [], [], null, []);

        $expected   = [$a, $b];
        $collection = new RouteCollection();
        $collection->add($b);
        $collection->add($a);

        $sorted = $this->routeConverter->sort($collection);
        $actual = iterator_to_array($sorted);
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function sortProvider(): array
    {
        return [
            'path'         => ['/a', 'get', '/b', 'get'],
            'method'       => ['/a', 'get', '/a', 'post'],
            'whitespace'   => [' /a ', 'get', '/b', 'get'],
            'param'        => ['/a/c', 'get', '/a/{b}', 'get'],
            'both_params'  => ['/a/{b}', 'get', '/b/{b}', 'get'],
            'nested_param' => ['/a/{b}/c', 'get', '/a/{b}/d', 'get'],
            'tilde'        => ['/~b', 'get', '/a', 'get'],
            'tilde_param'  => ['/~', 'get', '/{a}', 'get'],
        ];
    }

    /**
     * @dataProvider routeProvider
     * @param list<ParameterModel> $parameters
     */
    public function testConvertReturnsConverted(string $path, array $parameters, string $expected): void
    {
        $route  = new RouteModel('/foo/bar', $path, 'get', $parameters, [], null, []);
        $actual = $this->routeConverter->convert($route);
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: string, 1: list<ParameterModel>, 2: string}>
     */
    public static function routeProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'no_params'       => ['/foo', [], '/foo'],
            'array_simple'    => ['/foo/{arr}', [new ParameterModel('arr', false, 'array', 'simple')], '/foo/{arr:[^/]+}'],
            'boolean_simple'  => ['/foo/{bool}', [new ParameterModel('bool', false, 'boolean', 'simple')], '/foo/{bool:true|false}'],
            'integer_simple'  => ['/foo/{int}', [new ParameterModel('int', false, 'integer', 'simple')], '/foo/{int:\d+}'],
            'number_simple'   => ['/foo/{num}', [new ParameterModel('num', false, 'number', 'simple')], '/foo/{num:[\d\.]+}'],
            'object_simple'   => ['/foo/{obj}', [new ParameterModel('obj', false, 'object', 'simple')], '/foo/{obj:[^/]+}'],
            'string_simple'   => ['/foo/{str}', [new ParameterModel('str', false, 'string', 'simple')], '/foo/{str:[^/]+}'],
            'array_explode'   => ['/foo/{arr}', [new ParameterModel('arr', false, 'array', 'simple', true)], '/foo/{arr:[^/]+}'],
            'boolean_explode' => ['/foo/{bool}', [new ParameterModel('bool', false, 'boolean', 'simple', true)], '/foo/{bool:true|false|,}'],
            'integer_explode' => ['/foo/{int}', [new ParameterModel('int', false, 'integer', 'simple', true)], '/foo/{int:[\d,]+}'],
            'number_explode'  => ['/foo/{num}', [new ParameterModel('num', false, 'number', 'simple', true)], '/foo/{num:[\d\.,]+}'],
            'object_explode'  => ['/foo/{obj}', [new ParameterModel('obj', false, 'object', 'simple', true)], '/foo/{obj:[^/]+}'],
            'string_explode'  => ['/foo/{str}', [new ParameterModel('str', false, 'string', 'simple', true)], '/foo/{str:[^/]+}'],
            'string_label'    => ['/foo/{str}', [new ParameterModel('str', false, 'string', 'label')], '/foo/{str:\.[^/]+}'],
            'string_matrix'   => ['/foo/{str}', [new ParameterModel('str', false, 'string', 'matrix')], '/foo/{str:;[^/]+}'],
            'multiple'        => [
                '/foo/{int}/{bool}',
                [new ParameterModel('int', false, 'integer', 'simple'), new ParameterModel('bool', false, 'boolean', 'simple')],
                '/foo/{int:\d+}/{bool:true|false}',
            ],
        ];
        // phpcs:enable
    }
}

<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApi\OpenApiRouteParameter;
use Kynx\Mezzio\OpenApi\OpenApiSchema;
use Kynx\Mezzio\OpenApi\ParameterStyle;
use Kynx\Mezzio\OpenApi\SchemaType;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\FastRouteConverter;
use PHPUnit\Framework\TestCase;

use function array_map;
use function iterator_to_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\FastRouteConverter
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
        $aParams = array_map(fn (string $param): OpenApiRouteParameter => $this->makeParam($param), $aParams);
        $bParams = array_map(fn (string $param): OpenApiRouteParameter => $this->makeParam($param), $bParams);
        $a       = new HandlerClass('Foo', new OpenApiOperation(null, $aPath, $aMethod, ...$aParams));
        $b       = new HandlerClass('Bar', new OpenApiOperation(null, $bPath, $bMethod, ...$bParams));

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
            'param'        => ['/a/c', 'get', [], '/a/{b}', 'get', ['b']],
            'both_params'  => ['/a/{b}', 'get', ['b'], '/b/{b}', 'get', ['b']],
            'nested_param' => ['/a/{b}/c', 'get', ['b'], '/a/{b}/d', 'get', ['b']],
            'tilde'        => ['/~b', 'get', [], '/a', 'get', []],
            'tilde_param'  => ['/~', 'get', [], '/{a}', 'get', []],
        ];
    }

    /**
     * @dataProvider routeProvider
     */
    public function testConvertReturnsConverted(string $path, array $parameters, string $expected): void
    {
        $operation = new OpenApiOperation(null, $path, 'get', ...$parameters);
        $actual    = $this->routeConverter->convert($operation);
        self::assertSame($expected, $actual);
    }

    public function routeProvider(): array
    {
        $arrayParam  = $this->makeParam('arr', SchemaType::Array);
        $boolParam   = $this->makeParam('bool', SchemaType::Boolean);
        $intParam    = $this->makeParam('int', SchemaType::Integer);
        $numParam    = $this->makeParam('num', SchemaType::Number);
        $objectParam = $this->makeParam('obj', SchemaType::Object);
        $stringParam = $this->makeParam('str');

        return [
            'no_params' => ['/foo', [], '/foo'],
            'array'     => ['/foo/{arr}', [$arrayParam], '/foo/{arr:.+}'],
            'boolean'   => ['/foo/{bool}', [$boolParam], '/foo/{bool:(true|false)}'],
            'integer'   => ['/foo/{int}', [$intParam], '/foo/{int:\d+}'],
            'number'    => ['/foo/{num}', [$numParam], '/foo/{num:[\d.]+}'],
            'object'    => ['/foo/{obj}', [$objectParam], '/foo/{obj:.+}'],
            'string'    => ['/foo/{str}', [$stringParam], '/foo/{str:.+}'],
            'multiple'  => ['/foo/{int}/{bool}', [$intParam, $boolParam], '/foo/{int:\d+}/{bool:(true|false)}'],
        ];
    }

    private function makeParam(string $name, SchemaType $type = SchemaType::String): OpenApiRouteParameter
    {
        return new OpenApiRouteParameter($name, ParameterStyle::Simple, new OpenApiSchema($type));
    }
}

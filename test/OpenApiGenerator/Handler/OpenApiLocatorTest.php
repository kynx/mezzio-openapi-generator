<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Kynx\CodeUtils\ClassNameNormalizer;
use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApi\OpenApiRouteParameter;
use Kynx\Mezzio\OpenApi\OpenApiSchema;
use Kynx\Mezzio\OpenApi\ParameterStyle;
use Kynx\Mezzio\OpenApi\SchemaType;
use Kynx\Mezzio\OpenApiGenerator\Handler\FlatNamer;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\OpenApiLocator;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\OpenApiLocator
 */
final class OpenApiLocatorTest extends TestCase
{
    private OpenApi $openApi;
    private FlatNamer $namer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->openApi = Reader::readFromYamlFile(__DIR__ . '/Asset/openapi-locator.yaml');
        $this->namer = new FlatNamer(__NAMESPACE__, new ClassNameNormalizer('Handler'));
    }

    public function testCreateCreatesHandlersWithClassNames(): void
    {
        $expected = [
            __NAMESPACE__ . '\\OpId',
            __NAMESPACE__ . '\\NoOpIdQueryGet',
            __NAMESPACE__ . '\\GetParamById',
            __NAMESPACE__ . '\\NoOpIdParamIdGet',
            __NAMESPACE__ . '\\ParamWithReferencedSchemaNameGet',
            __NAMESPACE__ . '\\ReferencedParamReferencedParamGet',
        ];
        $locator = new OpenApiLocator($this->openApi, $this->namer);
        $collection = $locator->create();

        self::assertCount(6, $collection);
        $actual = array_map(
            fn (HandlerClass $handler): string => $handler->getClassName(),
            iterator_to_array($collection)
        );
        self::assertSame($expected, $actual);;
    }

    /**
     * @dataProvider createHandlerProvider
     */
    public function testCreateCreatesHandlersWithOperation(?string $operationId, string $path, string $method): void
    {
        $handler = $this->getHandlerClass($path, $method);
        $operation = $handler->getOperation();
        self::assertSame($operationId, $operation->getOperationId());
        self::assertSame($path, $operation->getPath());
        self::assertSame($method, $operation->getMethod());
    }

    public function createHandlerProvider(): array
    {
        return [
            '/op-id'                               => ['opId', '/op-id', 'post'],
            '/no-op-id/query'                      => [null, '/no-op-id/query', 'get'],
            '/param/{paramId}'                     => ['getParamById', '/param/{paramId}', 'get'],
            '/no-op-id/{paramId}'                  => [null, '/no-op-id/{paramId}', 'get'],
            '/param-with-referenced-schema/{name}' => [null, '/param-with-referenced-schema/{name}', 'get'],
            '/referenced-param/{referencedParam}'  => [null, '/referenced-param/{referencedParam}', 'get'],
        ];
    }

    public function testCreateFiltersPathParams(): void
    {
        $handler = $this->getHandlerClass('/no-op-id/query');
        $parameters = $handler->getOperation()->getRouteParameters();
        self::assertCount(0, $parameters);
    }

    public function testCreateAddsPathParams(): void
    {
        $expected = [
            new OpenApiRouteParameter(
                'paramId',
                ParameterStyle::Simple,
                new OpenApiSchema(SchemaType::Integer, 'int64')
            ),
        ];
        $handler = $this->getHandlerClass('/no-op-id/{paramId}');
        $actual = $handler->getOperation()->getRouteParameters();
        self::assertEquals($expected, $actual);
    }

    public function testCreateAddsPathParamsForReferencedSchema(): void
    {
        $expected = [
            new OpenApiRouteParameter(
                'name',
                ParameterStyle::Simple,
                new OpenApiSchema(SchemaType::String)
            ),
        ];
        $handler = $this->getHandlerClass('/param-with-referenced-schema/{name}');
        $actual = $handler->getOperation()->getRouteParameters();
        self::assertEquals($expected, $actual);
    }

    public function testCreateAddsPathParamsForReferencedParam(): void
    {
        $expected = [
            new OpenApiRouteParameter(
                'referencedParam',
                ParameterStyle::Simple,
                new OpenApiSchema(SchemaType::String)
            ),
        ];
        $handler = $this->getHandlerClass('/referenced-param/{referencedParam}');
        $actual = $handler->getOperation()->getRouteParameters();
        self::assertEquals($expected, $actual);
    }

    private function getHandlerClass(string $path, string $method = 'get'): HandlerClass
    {
        $locator = new OpenApiLocator($this->openApi, $this->namer);
        $operation = new OpenApiOperation(null, $path, $method);
        $collection = $locator->create();
        foreach ($collection as $handlerClass) {
            if ($handlerClass->matches($operation)) {
                return $handlerClass;
            }
        }

        self::fail("Cannot find handler for '$path' and '$method'");
    }
}

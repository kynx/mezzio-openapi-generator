<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Parameter;
use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\Namer\FlatNamer;
use Kynx\Mezzio\OpenApiGenerator\Handler\OpenApiParser;
use PHPUnit\Framework\TestCase;

use function array_map;
use function iterator_to_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\OpenApiParser
 */
final class OpenApiParserTest extends TestCase
{
    private OpenApi $openApi;
    private FlatNamer $namer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->openApi = Reader::readFromYamlFile(__DIR__ . '/Asset/openapi-parser.yaml');
        self::assertTrue($this->openApi->validate(), "Invalid openapi schema");

        $labeler     = new UniqueClassLabeler(new ClassNameNormalizer('Handler'), new NumberSuffix());
        $this->namer = new FlatNamer(__NAMESPACE__, $labeler);
    }

    public function testCreateCreatesHandlersWithClassNames(): void
    {
        $expected   = [
            __NAMESPACE__ . '\\OpId1',
            __NAMESPACE__ . '\\NoOpIdQueryGet',
            __NAMESPACE__ . '\\OpId2',
            __NAMESPACE__ . '\\GetParamById',
            __NAMESPACE__ . '\\NoOpIdParamIdGet',
            __NAMESPACE__ . '\\ParamWithReferencedSchemaNameGet',
            __NAMESPACE__ . '\\ReferencedParamReferencedParamGet',
        ];
        $locator    = new OpenApiParser($this->openApi, $this->namer);
        $collection = $locator->getHandlerCollection();

        self::assertCount(7, $collection);
        $actual = array_map(
            fn (HandlerClass $handler): string => $handler->getClassName(),
            iterator_to_array($collection)
        );
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider createHandlerProvider
     */
    public function testCreateCreatesHandlersWithRoute(?string $operationId, string $path, string $method): void
    {
        $handler   = $this->getHandlerClass($path, $method);
        $route     = $handler->getRoute();
        $operation = $route->getOperation();
        self::assertSame($operationId, $operation->operationId);
        self::assertSame($path, $route->getPath());
        self::assertSame($method, $route->getMethod());
    }

    public function createHandlerProvider(): array
    {
        return [
            '/op-id'                               => ['opId', '/op-id', 'post'],
            '/no-op-id/query'                      => [null, '/no-op-id/query', 'get'],
            '/op-id/case'                          => ['OpId', '/op-id/case', 'post'],
            '/param/{paramId}'                     => ['getParamById', '/param/{paramId}', 'get'],
            '/no-op-id/{paramId}'                  => [null, '/no-op-id/{paramId}', 'get'],
            '/param-with-referenced-schema/{name}' => [null, '/param-with-referenced-schema/{name}', 'get'],
            '/referenced-param/{referencedParam}'  => [null, '/referenced-param/{referencedParam}', 'get'],
        ];
    }

    public function testCreateAddsPathParams(): void
    {
        $parameter = new Parameter([
            'name'     => 'paramId',
            'in'       => 'path',
            'required' => true,
            'schema'   => [
                'type'   => 'integer',
                'format' => 'int64',
            ],
        ]);
        $parameter->setDocumentContext(
            $this->openApi,
            new JsonPointer('/paths/~1no-op-id~1{paramId}/get/parameters/0')
        );
        $expected = [$parameter];

        $handler = $this->getHandlerClass('/no-op-id/{paramId}');
        $actual  = $handler->getRoute()->getOperation()->parameters;
        self::assertEquals($expected, $actual);
    }

    public function testCreateAddsPathParamsForReferencedSchema(): void
    {
        $parameter = new Parameter([
            'name'     => 'name',
            'in'       => 'path',
            'required' => true,
            'schema'   => [
                'type' => 'string',
            ],
        ]);
        $parameter->setDocumentContext(
            $this->openApi,
            new JsonPointer('/paths/~1param-with-referenced-schema~1{name}/get/parameters/0')
        );
        self::assertNotNull($parameter->schema);
        $parameter->schema->setDocumentContext($this->openApi, new JsonPointer('/components/schemas/ParamSchema'));
        $expected = [$parameter];

        $handler = $this->getHandlerClass('/param-with-referenced-schema/{name}');
        $actual  = $handler->getRoute()->getOperation()->parameters;
        self::assertEquals($expected, $actual);
    }

    public function testCreateAddsPathParamsForReferencedParam(): void
    {
        $parameter = new Parameter([
            'name'     => 'referencedParam',
            'in'       => 'path',
            'required' => true,
            'schema'   => [
                'type' => 'string',
            ],
        ]);
        $parameter->setDocumentContext(
            $this->openApi,
            new JsonPointer('/components/parameters/MyParam')
        );
        self::assertNotNull($parameter->schema);
        $parameter->schema->setDocumentContext($this->openApi, new JsonPointer('/components/schemas/ParamSchema'));
        $expected = [$parameter];

        $handler = $this->getHandlerClass('/referenced-param/{referencedParam}');
        $actual  = $handler->getRoute()->getOperation()->parameters;
        self::assertEquals($expected, $actual);
    }

    private function getHandlerClass(string $path, string $method = 'get'): HandlerClass
    {
        $locator    = new OpenApiParser($this->openApi, $this->namer);
        $collection = $locator->getHandlerCollection();
        foreach ($collection as $handlerClass) {
            $route = $handlerClass->getRoute();
            if ($route->getPath() === $path && $route->getMethod() === $method) {
                return $handlerClass;
            }
        }

        self::fail("Cannot find handler for '$path' and '$method'");
    }
}

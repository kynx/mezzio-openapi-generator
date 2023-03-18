<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseModel;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel
 */
final class OperationModelTest extends TestCase
{
    use OperationTrait;

    public function testConstructorSetsProperties(): void
    {
        $className     = '\\Foo';
        $jsonPointer   = '/components/schemas/Foo';
        $pathParams    = $this->getPathParams();
        $queryParams   = $this->getQueryParams();
        $headerParams  = $this->getHeaderParams();
        $cookieParams  = $this->getCookieParams();
        $requestBodies = $this->getRequestBodies();
        $models        = [
            $pathParams->getModel(),
            $queryParams->getModel(),
            $headerParams->getModel(),
            $cookieParams->getModel(),
        ];

        $operationModel = new OperationModel(
            $className,
            $jsonPointer,
            $pathParams,
            $queryParams,
            $headerParams,
            $cookieParams,
            $requestBodies
        );
        self::assertSame($className, $operationModel->getClassName());
        self::assertSame($jsonPointer, $operationModel->getJsonPointer());
        self::assertSame($pathParams, $operationModel->getPathParams());
        self::assertSame($queryParams, $operationModel->getQueryParams());
        self::assertSame($headerParams, $operationModel->getHeaderParams());
        self::assertSame($cookieParams, $operationModel->getCookieParams());
        self::assertSame($requestBodies, $operationModel->getRequestBodies());
        self::assertSame($models, $operationModel->getModels());
    }

    /**
     * @dataProvider hasParameterProvider
     * @param list<RequestBodyModel> $requestBodies
     */
    public function testHasParameters(
        PathOrQueryParams|null $pathParams,
        PathOrQueryParams|null $queryParams,
        CookieOrHeaderParams|null $headerParams,
        CookieOrHeaderParams|null $cookieParams,
        array $requestBodies,
        bool $expected
    ): void {
        $operationModel = new OperationModel(
            '\\Foo',
            '/paths/foo/get',
            $pathParams,
            $queryParams,
            $headerParams,
            $cookieParams,
            $requestBodies
        );

        $actual = $operationModel->hasParameters();
        self::assertSame($expected, $actual);
    }

    public function hasParameterProvider(): array
    {
        return [
            'none'   => [null, null, null, null, [], false],
            'path'   => [$this->getPathParams(), null, null, null, [], true],
            'query'  => [null, $this->getQueryParams(), null, null, [], true],
            'header' => [null, null, $this->getHeaderParams(), null, [], true],
            'cookie' => [null, null, null, $this->getCookieParams(), [], true],
            'body'   => [null, null, null, null, $this->getRequestBodies(), true],
        ];
    }

    public function testGetRequestFactoryClassName(): void
    {
        $expected       = 'Foo\\RequestFactory';
        $operationModel = new OperationModel('Foo\\Operation', '/paths/foo/get');

        $actual = $operationModel->getRequestFactoryClassName();
        self::assertSame($expected, $actual);
    }

    public function testGetRequestBodyUsesReturnsUses(): void
    {
        $class          = "Foo\Bar";
        $expected       = [$class];
        $operationModel = new OperationModel(
            'Foo\\Operation',
            '/paths/foo/get',
            null,
            null,
            null,
            null,
            [
                new RequestBodyModel(
                    'text/plain',
                    new SimpleProperty('', '', new PropertyMetadata(), PropertyType::String)
                ),
                new RequestBodyModel(
                    'application/json',
                    new SimpleProperty('', '', new PropertyMetadata(), new ClassString($class))
                ),
            ]
        );

        $actual = $operationModel->getRequestBodyUses();
        self::assertSame($expected, $actual);
    }

    public function testGetRequestBodyTypeReturnsType(): void
    {
        $first          = "Foo\Bar";
        $second         = "Foo\Baz";
        $expected       = "$first|$second";
        $requestBodies  = [
            new RequestBodyModel(
                'application/json',
                new SimpleProperty('', '', new PropertyMetadata(), new ClassString($first))
            ),
            new RequestBodyModel(
                'application/xml',
                new SimpleProperty('', '', new PropertyMetadata(), new ClassString($second))
            ),
        ];
        $operationModel = new OperationModel(
            className:'Foo\\Operation',
            jsonPointer:'/paths/foo/get',
            requestBodies: $requestBodies
        );

        $actual = $operationModel->getRequestBodyType();
        self::assertSame($expected, $actual);
    }

    public function testGetRequestBodyDocBlockTypeReturnsType(): void
    {
        $first          = "Foo\Bar";
        $second         = "Foo\Baz";
        $expected       = "array<int, Bar>|Baz";
        $requestBodies  = [
            new RequestBodyModel(
                'application/json',
                new ArrayProperty('', '', new PropertyMetadata(required: true), true, new ClassString($first))
            ),
            new RequestBodyModel(
                'application/xml',
                new SimpleProperty('', '', new PropertyMetadata(), new ClassString($second))
            ),
        ];
        $operationModel = new OperationModel(
            className:'Foo\\Operation',
            jsonPointer:'/paths/foo/get',
            requestBodies: $requestBodies
        );

        $actual = $operationModel->getRequestBodyDocBlockType();
        self::assertSame($expected, $actual);
    }

    public function testResponsesRequireNegotiationReturnsTrue(): void
    {
        $class          = new ClassString('Foo\\Response');
        $respopnses     = [
            new ResponseModel(
                '200',
                'OK',
                'application/json',
                new SimpleProperty('', '', new PropertyMetadata(), $class)
            ),
            new ResponseModel(
                '200',
                'OK',
                'application/xml',
                new SimpleProperty('', '', new PropertyMetadata(), $class)
            ),
        ];
        $operationModel = new OperationModel(
            'Foo\\Operation',
            '/paths/foo/get',
            ...['responses' => $respopnses]
        );

        $actual = $operationModel->responsesRequireNegotiation();
        self::assertTrue($actual);
    }

    public function testResponseStatusRequiresNegotiationReturnsTrue(): void
    {
        $class          = new ClassString('Foo\\Response');
        $respopnses     = [
            new ResponseModel(
                '200',
                'OK',
                'application/json',
                new SimpleProperty('', '', new PropertyMetadata(), $class)
            ),
            new ResponseModel(
                '200',
                'OK',
                'application/xml',
                new SimpleProperty('', '', new PropertyMetadata(), $class)
            ),
        ];
        $operationModel = new OperationModel(
            'Foo\\Operation',
            '/paths/foo/get',
            ...['responses' => $respopnses]
        );

        $actual = $operationModel->responseStatusRequiresNegotiation('200');
        self::assertTrue($actual);
    }

    public function testResponseStatusRequiresNegotiationReturnsFalse(): void
    {
        $class          = new ClassString('Foo\\Response');
        $respopnses     = [
            new ResponseModel(
                '200',
                'OK',
                'application/json',
                new SimpleProperty('', '', new PropertyMetadata(), $class)
            ),
        ];
        $operationModel = new OperationModel(
            'Foo\\Operation',
            '/paths/foo/get',
            ...['responses' => $respopnses]
        );

        $actual = $operationModel->responseStatusRequiresNegotiation('200');
        self::assertFalse($actual);
    }

    public function testGetResponseStatusReturnsStatuses(): void
    {
        $expected       = ['200', '404'];
        $respopnses     = [
            new ResponseModel(
                '200',
                'OK',
                'application/json',
                new SimpleProperty('', '', new PropertyMetadata(), new ClassString('Foo\\Bar'))
            ),
            new ResponseModel(
                '404',
                'Not found',
                'text/plaiin',
                new SimpleProperty('', '', new PropertyMetadata(), PropertyType::String)
            ),
        ];
        $operationModel = new OperationModel(
            'Foo\\Operation',
            '/paths/foo/get',
            ...['responses' => $respopnses]
        );

        $actual = $operationModel->getResponseStatuses();
        self::assertSame($expected, $actual);
    }

    public function testGetResponsesOfStatusReturnsStatuses(): void
    {
        $expected       = [
            new ResponseModel(
                '200',
                'OK',
                'application/json',
                new SimpleProperty('', '', new PropertyMetadata(), new ClassString('Foo\\Bar'))
            ),
        ];
        $operationModel = new OperationModel(
            'Foo\\Operation',
            '/paths/foo/get',
            ...['responses' => $expected]
        );

        $actual = $operationModel->getResponsesOfStatus('200');
        self::assertSame($expected, $actual);
    }

    public function testGetResponsesOfStatusReturnsEmpty(): void
    {
        $expected       = [];
        $responses      = [
            new ResponseModel(
                '200',
                'OK',
                'application/json',
                new SimpleProperty('', '', new PropertyMetadata(), new ClassString('Foo\\Bar'))
            ),
        ];
        $operationModel = new OperationModel(
            'Foo\\Operation',
            '/paths/foo/get',
            ...['responses' => $responses]
        );

        $actual = $operationModel->getResponsesOfStatus('404');
        self::assertSame($expected, $actual);
    }
}

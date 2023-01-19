<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
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
}

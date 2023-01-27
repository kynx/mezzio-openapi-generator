<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Responses;
use Exception;
use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\ModelException
 */
final class ModelExceptionTest extends TestCase
{
    public function testInvalidModelPath(): void
    {
        $expected  = "'/foo' is not a valid path";
        $exception = ModelException::invalidModelPath('/foo');
        self::assertSame($expected, $exception->getMessage());
    }

    public function testSchemaExists(): void
    {
        $expected  = "Model '\\Foo' already exists";
        $model     = new InterfaceModel('\\Foo', '/Foo');
        $exception = ModelException::modelExists($model);
        self::assertSame($expected, $exception->getMessage());
    }

    public function testUnrecognizedType(): void
    {
        $expected  = "Unrecognized type 'foo'";
        $exception = ModelException::unrecognizedType('foo');
        self::assertSame($expected, $exception->getMessage());
    }

    public function testUnrecognizedValue(): void
    {
        $expected  = "Unrecognized value 'stdClass'";
        $value     = new stdClass();
        $exception = ModelException::unrecognizedValue($value);
        self::assertSame($expected, $exception->getMessage());
    }

    public function testInvalidOpenApiSchema(): void
    {
        $expected   = "Invalid OpenApiSchema attribute for class 'stdClass'";
        $class      = new stdClass();
        $reflection = new ReflectionClass($class);
        $previous   = new Exception();
        $exception  = ModelException::invalidOpenApiSchema($reflection, $previous);
        self::assertSame($expected, $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testMissingDocumentContext(): void
    {
        $expected  = "Specification is missing a document context";
        $exception = ModelException::missingDocumentContext();
        self::assertSame($expected, $exception->getMessage());
    }

    public function testUnresolvedReference(): void
    {
        $expected  = "Unresolved reference: '/foo'";
        $reference = new Reference(['$ref' => '/foo']);
        $exception = ModelException::unresolvedReference($reference);
        self::assertSame($expected, $exception->getMessage());
    }

    public function testInvalidSchemaReturnsUnknown(): void
    {
        $expected = "Cannot parse foo at pointer 'unknown'";
        $exception = ModelException::invalidSchemaItem('foo', null);
        self::assertSame($expected, $exception->getMessage());
    }

    public function testInvalidSchemaReturnsPointer(): void
    {
        $pointer = '/paths/~foo/get/responses';
        $expected = "Cannot parse foo at pointer '$pointer'";
        $parent = new Responses([]);
        $parent->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $exception = ModelException::invalidSchemaItem('foo', $parent);
        self::assertSame($expected, $exception->getMessage());
    }
}

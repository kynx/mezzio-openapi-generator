<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation\Generator;

use Kynx\Mezzio\OpenApi\Operation\AbstractResponseFactory;
use Kynx\Mezzio\OpenApi\Serializer\SerializerInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\ResponseFactoryGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\ResponseModel;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PromotedParameter;
use PHPUnit\Framework\TestCase;

use function trim;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\Generator\ResponseFactoryGenerator
 */
final class ResponseFactoryGeneratorTest extends TestCase
{
    use GeneratorTrait;

    private const NAMESPACE = __NAMESPACE__ . '\\Foo\\Get';

    private ResponseFactoryGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ResponseFactoryGenerator([]);
    }

    public function testGenerateReturnsFile(): void
    {
        $className = self::NAMESPACE . '\\Operation';
        $pointer   = '/paths/foo/get';
        $operation = new OperationModel($className, $pointer);

        $file = $this->generator->generate($operation, []);
        self::assertTrue($file->hasStrictTypes());

        $namespace    = $this->getNamespace($file, self::NAMESPACE);
        $expectedUses = [
            'AbstractResponseFactory' => AbstractResponseFactory::class,
        ];
        $uses         = $namespace->getUses();
        self::assertSame($expectedUses, $uses);

        $class = $this->getClass($namespace, 'ResponseFactory');
        self::assertSame(AbstractResponseFactory::class, $class->getExtends());
        self::assertTrue($class->isFinal());
    }

    public function testGenerateAddsEmptyResponseGetter(): void
    {
        $expected  = "return (new EmptyResponse())->withStatus(201, 'Created');";
        $response  = new ResponseModel('201', 'Created', null, null);
        $className = self::NAMESPACE . '\\Operation';
        $pointer   = '/paths/foo/get';
        $operation = new OperationModel($className, $pointer, null, null, null, null, [], [$response]);

        $file = $this->generator->generate($operation, []);

        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $class     = $this->getClass($namespace, 'ResponseFactory');
        $method    = $this->getMethod($class, 'get201Response');

        $expectedUses = [
            'AbstractResponseFactory' => AbstractResponseFactory::class,
            'EmptyResponse'           => EmptyResponse::class,
        ];
        $uses         = $namespace->getUses();
        self::assertSame($expectedUses, $uses);

        self::assertTrue($method->isPublic());
        self::assertSame(EmptyResponse::class, $method->getReturnType());

        $actual = trim($method->getBody());
        self::assertSame($expected, $actual);
    }

    public function testGenerateEmptyDefaultResponseSetsStatus204(): void
    {
        $expected  = "return (new EmptyResponse())->withStatus(204, 'No content');";
        $response  = new ResponseModel('default', 'No content', null, null);
        $className = self::NAMESPACE . '\\Operation';
        $pointer   = '/paths/foo/get';
        $operation = new OperationModel($className, $pointer, null, null, null, null, [], [$response]);

        $file = $this->generator->generate($operation, []);

        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $class     = $this->getClass($namespace, 'ResponseFactory');
        $method    = $this->getMethod($class, 'getDefaultResponse');

        $actual = trim($method->getBody());
        self::assertSame($expected, $actual);
    }

    public function testGenerateAddsSingleMimeTypeResponse(): void
    {
        $expected = <<<SINGLE_MIME_TYPE
        \$headers = ["Content-Type" => 'application/json; charset=utf-8'];
        \$extractor = BarHydrator::class;
        \$body = \$this->serializer->serialize('application/json', \$extractor, \$model);
        return \$this->getResponse(\$body, 409, 'Duplicates detected', \$headers);
        SINGLE_MIME_TYPE;

        $type      = new SimpleProperty('', '', new PropertyMetadata(), new ClassString('Foo\\Bar'));
        $response  = new ResponseModel('409', 'Duplicates detected', 'application/json', $type);
        $className = self::NAMESPACE . '\\Operation';
        $pointer   = '/paths/foo/get';
        $operation = new OperationModel($className, $pointer, null, null, null, null, [], [$response]);

        $file = $this->generator->generate($operation, ['Foo\\Bar' => 'Foo\\BarHydrator']);

        $namespace   = $this->getNamespace($file, self::NAMESPACE);
        $class       = $this->getClass($namespace, 'ResponseFactory');
        $constructor = $this->getMethod($class, '__construct');
        $method      = $this->getMethod($class, 'get409Response');

        $expectedUses = [
            'Bar'                     => 'Foo\\Bar',
            'BarHydrator'             => 'Foo\\BarHydrator',
            'AbstractResponseFactory' => AbstractResponseFactory::class,
            'SerializerInterface'     => SerializerInterface::class,
            'Response'                => Response::class,
        ];
        $uses         = $namespace->getUses();
        self::assertSame($expectedUses, $uses);

        self::assertTrue($constructor->isPublic());

        $parameters = $constructor->getParameters();
        self::assertCount(2, $parameters);

        self::assertArrayHasKey('serializer', $parameters);
        $serializer = $parameters['serializer'];
        self::assertInstanceOf(PromotedParameter::class, $serializer);
        self::assertSame(SerializerInterface::class, $serializer->getType());
        self::assertTrue($serializer->isPrivate());
        self::assertTrue($serializer->isReadOnly());

        self::assertArrayHasKey('maxMemory', $parameters);
        $maxMemory = $parameters['maxMemory'];
        self::assertSame('int', $maxMemory->getType());
        self::assertEquals(new Literal('self::DEFAULT_MAX_MEMORY'), $maxMemory->getDefaultValue());

        $body = trim($constructor->getBody());
        self::assertSame('parent::__construct($maxMemory);', $body);

        self::assertTrue($method->isPublic());
        self::assertSame(Response::class, $method->getReturnType());
        $parameters = $method->getParameters();
        self::assertCount(1, $parameters);
        self::assertArrayHasKey('model', $parameters);
        $model = $parameters['model'];
        self::assertSame('Foo\\Bar', $model->getType());

        $actual = trim($method->getBody());
        self::assertSame($expected, $actual);
    }
}

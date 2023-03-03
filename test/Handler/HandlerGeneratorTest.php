<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiHandler;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerGenerator;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function trim;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Handler\HandlerGenerator
 */
final class HandlerGeneratorTest extends TestCase
{
    use GeneratorTrait;
    use OperationTrait;

    private HandlerGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new HandlerGenerator();
    }

    public function testGenerateReturnsFile(): void
    {
        $pointer   = '/paths/~1foo/get';
        $className = __NAMESPACE__ . '\\GetHandler';
        $operation = new OperationModel('\\Foo\\Operation', $pointer);
        $handler   = new HandlerModel($pointer, $className, $operation);

        $file      = $this->generator->generate($handler);
        $namespace = $this->getNamespace($file, __NAMESPACE__);
        $class     = $this->getClass($namespace, 'GetHandler');
        $method    = $this->getMethod($class, 'handle');

        $expectedUses = [
            'ResponseFactory'         => $operation->getResponseFactoryClassName(),
            'OpenApiHandler'          => OpenApiHandler::class,
            'ResponseInterface'       => ResponseInterface::class,
            'ServerRequestInterface'  => ServerRequestInterface::class,
            'RequestHandlerInterface' => RequestHandlerInterface::class,
        ];
        $uses         = $namespace->getUses();
        self::assertSame($expectedUses, $uses);

        self::assertTrue($class->isFinal());
        self::assertSame([RequestHandlerInterface::class], $class->getImplements());

        $attributes = $class->getAttributes();
        self::assertCount(1, $attributes);
        $attribute = $attributes[0];
        self::assertSame(OpenApiHandler::class, $attribute->getName());
        self::assertSame([$pointer], $attribute->getArguments());

        self::assertTrue($method->isPublic());
        self::assertSame(ResponseInterface::class, $method->getReturnType());
        $parameters = $method->getParameters();
        self::assertArrayHasKey('request', $parameters);
        $parameter = $parameters['request'];
        self::assertSame(ServerRequestInterface::class, $parameter->getType());

        self::assertSame('// @todo Add your handler logic...', trim($method->getBody()));
    }

    public function testGenerateAddsOperation(): void
    {
        $expected = <<<HANDLE_BODY
        \$operation = \$request->getAttribute(OpenApiRequest::class);
        assert(\$operation instanceof Request);
        
        // @todo Add your handler logic...
        HANDLE_BODY;

        $pointer   = '/paths/~1foo/get';
        $className = __NAMESPACE__ . '\\GetHandler';
        $operation = new OperationModel('\\Foo\\Operation', $pointer, $this->getPathParams('Foo'));
        $handler   = new HandlerModel($pointer, $className, $operation);

        $file      = $this->generator->generate($handler);
        $namespace = $this->getNamespace($file, __NAMESPACE__);
        $class     = $this->getClass($namespace, 'GetHandler');
        $method    = $this->getMethod($class, 'handle');

        $uses = $namespace->getUses();
        self::assertArrayHasKey('OpenApiRequest', $uses);
        self::assertArrayHasKey('Request', $uses);

        $actual = trim($method->getBody());
        self::assertSame($expected, $actual);
    }
}

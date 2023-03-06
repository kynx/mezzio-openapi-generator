<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequestFactory;
use Kynx\Mezzio\OpenApi\Attribute\OpenApiRouteDelegator;
use Kynx\Mezzio\OpenApi\Middleware\OpenApiOperationMiddleware;
use Kynx\Mezzio\OpenApi\Middleware\ValidationMiddleware;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Route\Converter\ConverterInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\NamerInterface;
use Mezzio\Application;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Psr\Container\ContainerInterface;

use function assert;
use function current;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\RouteDelegatorGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RouteDelegatorGenerator
{
    public function __construct(
        private readonly ConverterInterface $routeConverter,
        private readonly NamerInterface $routeNamer,
        private readonly string $className
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @param array<string, string> $handlerMap
     */
    public function generate(RouteCollection $routes, array $handlerMap): PhpFile
    {
        $routes = $this->routeConverter->sort($routes);

        $file = new PhpFile();
        $file->setStrictTypes();

        $class = $file->addClass($this->className)
            ->setFinal();

        $namespace = current($file->getNamespaces());
        $namespace->addUse(Application::class)
            ->addUse(OpenApiOperationMiddleware::class)
            ->addUse(OpenApiRequestFactory::class)
            ->addUse(OpenApiRouteDelegator::class)
            ->addUse(ValidationMiddleware::class)
            ->addUse(ContainerInterface::class)
            ->addUseFunction('assert');

        $class->addAttribute(OpenApiRouteDelegator::class);

        $invoke = $class->addMethod('__invoke');
        $invoke->addParameter('container')
            ->setType(ContainerInterface::class);
        $invoke->addParameter('serviceName')
            ->setType('string');
        $invoke->addParameter('callback')
            ->setType('callable');
        $invoke->setReturnType(Application::class);

        $invoke->addBody('$app = $callback();');
        $invoke->addBody('assert($app instanceof ?);', [
            new Literal($namespace->simplifyName(Application::class)),
        ]);

        foreach ($routes as $route) {
            $this->addRoute($namespace, $invoke, $route, $handlerMap);
        }

        $invoke->addBody('');
        $invoke->addBody('return $app;');

        return $file;
    }

    /**
     * @param array<string, string> $handlerMap
     */
    private function addRoute(PhpNamespace $namespace, Method $invoke, RouteModel $route, array $handlerMap): void
    {
        $pointer = $route->getJsonPointer();
        assert(isset($handlerMap[$pointer]));
        $handlerClass = $handlerMap[$pointer];
        $alias        = GeneratorUtil::getAlias($namespace->simplifyName($handlerClass));
        $namespace->addUse($handlerClass, $alias);

        $middleware = [
            new Literal($namespace->simplifyName(ValidationMiddleware::class) . '::class'),
            new Literal($namespace->simplifyName(OpenApiOperationMiddleware::class) . '::class'),
            new Literal($alias . '::class'),
        ];
        $converted  = $this->routeConverter->convert($route);

        $openApiOperationClass = $namespace->simplifyName(OpenApiRequestFactory::class);
        $options = [new Literal("$openApiOperationClass::class => ?", [$pointer])];

        $invoke->addBody('');
        $invoke->addBody('$app?(?, ?, ?)->setOptions(?);', [
            new Literal('->' . $route->getMethod()),
            $converted,
            $middleware,
            $this->routeNamer->getName($route),
            $options,
        ]);
    }
}

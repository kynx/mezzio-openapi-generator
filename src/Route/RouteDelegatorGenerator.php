<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiOperationFactory;
use Kynx\Mezzio\OpenApi\Attribute\OpenApiRouteDelegator;
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
            ->addUse(OpenApiOperationFactory::class)
            ->addUse(OpenApiRouteDelegator::class)
            ->addUse(ContainerInterface::class);

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
        $invoke->addBody('');

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
        $converted = $this->routeConverter->convert($route);

        $pointer = $route->getJsonPointer();
        assert(isset($handlerMap[$pointer]));
        $handlerClass = $handlerMap[$pointer];

        $namespace->addUse($handlerClass);
        $invoke->addBody('$app?(?, ?, ?)', [
            new Literal('->' . $route->getMethod()),
            $converted,
            new Literal($namespace->simplifyName($handlerClass) . '::class'),
            $this->routeNamer->getName($route),
        ]);

        $openApiOperationClass = $namespace->simplifyName(OpenApiOperationFactory::class);
        $invoke->addBody('    ->setOptions([? => ?]);', [
            new Literal("$openApiOperationClass::class"),
            $pointer,
        ]);
    }
}

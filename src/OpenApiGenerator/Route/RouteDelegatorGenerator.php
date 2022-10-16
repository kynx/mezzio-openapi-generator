<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApi\RouteOptionInterface;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\Converter\ConverterInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\NamerInterface;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Mezzio\Application;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Psr\Container\ContainerInterface;

use function implode;
use function sprintf;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\RouteDelegatorGeneratorTest
 */
final class RouteDelegatorGenerator
{
    public function __construct(
        private readonly ConverterInterface $routeConverter,
        private readonly NamerInterface $routeNamer
    ) {
    }

    public function generate(HandlerCollection $handlers, PhpFile $file): PhpFile
    {
        $namespaces = $file->getNamespaces();
        $namespace = current($namespaces);
        assert($namespace instanceof PhpNamespace);

        $classes = $file->getClasses();
        $delegator = current($classes);
        assert($delegator instanceof ClassType);

        $namespace->addUse(Application::class);
        $namespace->addUse(RouteOptionInterface::class);

        if ($delegator->hasMethod('__invoke')) {
            $delegator->removeMethod('__invoke');
        }

        $invoke = $delegator->addMethod('__invoke');
        $invoke->addParameter('container')
            ->setType(ContainerInterface::class);
        $invoke->addParameter('serviceName')
            ->setType('string');
        $invoke->addParameter('callback')
            ->setType('callable');
        $invoke->setReturnType(Application::class);

        $invoke->addBody('$app = $callback();');
        $invoke->addBody('assert($app instanceof ?);', [
            new Literal($namespace->simplifyName(Application::class))
        ]);
        $invoke->addBody('');

        foreach ($handlers as $handler) {
            $this->addRoute($namespace, $invoke, $handler);
        }

        $invoke->addBody('');
        $invoke->addBody('return $app;');

        return $file;
    }

    private function addRoute(PhpNamespace $namespace, Method $invoke, HandlerClass $handler): void
    {
        $route     = $handler->getRoute();
        $converted = $this->routeConverter->convert($route);

        $namespace->addUse($handler->getClassName());
        $invoke->addBody('$app?(?, ?, ?)', [
            new Literal('->' . $route->getMethod()),
            $converted,
            new Literal($namespace->simplifyName($handler->getClassName()) . '::class'),
            $this->routeNamer->getName($route),
        ]);
        $invoke->addBody('    ->setOptions([? => ?]);', [
            new Literal($namespace->simplifyName(RouteOptionInterface::class) . '::PATH'),
            $route->getPath(),
        ]);
    }
}

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
use Psr\Container\ContainerInterface;

use function implode;
use function sprintf;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\RouteDelegatorGeneratorTest
 */
final class RouteDelegatorGenerator
{
    public function __construct(
        private ConverterInterface $routeConverter,
        private NamerInterface $routeNamer
    ) {
    }

    public function generate(OpenApi $openApi, HandlerCollection $handlers, ClassGenerator $generator): string
    {
        $lines = [
            '$app = $callback();',
            sprintf('assert($app instanceof \\%s::class);', Application::class),
            '',
        ];

        foreach ($handlers as $handler) {
            $lines[] = $this->getRoute($handler);
        }

        $lines[] = '';
        $lines[] = 'return $app;';

        if ($generator->hasMethod('__invoke')) {
            $generator->removeMethod('__invoke');
        }

        $parameters = [
            new ParameterGenerator('container', ContainerInterface::class),
            new ParameterGenerator('serviceName', 'string'),
            new ParameterGenerator('callback', 'callable'),
        ];
        $invoke     = new MethodGenerator('__invoke');
        $invoke->setParameters($parameters)
            ->setReturnType(Application::class)
            ->setBody(implode("\n", $lines));
        $generator->addMethodFromGenerator($invoke);

        return $generator->generate();
    }

    private function getRoute(HandlerClass $handler): string
    {
        $route     = $handler->getRoute();
        $operation = $route->getOperation();
        $converted = $this->routeConverter->convert($handler->getRoute());
        return sprintf(
            "\$app->%s('%s', \\%s::class, '%s')->setOptions([\\%s::PATH => '%s']);",
            $route->getMethod(),
            $converted,
            $handler->getClassName(),
            $this->routeNamer->getName($route),
            RouteOptionInterface::class,
            $route->getPath()
        );
    }
}

<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApi\RouteOptionInterface;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
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
        private RouteConverterInterface $routeConverter,
        private RouteNamerInterface $routeNamer
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
        $operation = $handler->getOperation();
        $route     = $this->routeConverter->convert($handler->getOperation());
        return sprintf(
            "\$app->%s('%s', \\%s::class, '%s')->setOptions([\\%s::PATH => '%s']);",
            $operation->getMethod(),
            $route,
            $handler->getClassName(),
            $this->routeNamer->getName($operation),
            RouteOptionInterface::class,
            $operation->getPath()
        );
    }
}

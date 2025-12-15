<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Server;
use Kynx\Mezzio\OpenApiGenerator\Security\SecurityModelResolver;

use function array_filter;
use function current;
use function parse_url;
use function strtolower;

use const PHP_URL_PATH;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RouteCollectionBuilder
{
    /**
     * @param array<string, class-string> $middleware
     */
    public function __construct(private readonly array $middleware)
    {
    }

    public function getRouteCollection(OpenApi $openApi, SecurityModelResolver $securityModelResolver): RouteCollection
    {
        $collection = new RouteCollection();

        $basePath = '';
        $server   = current($openApi->servers);
        if ($server instanceof Server) {
            $basePath = (string) parse_url($server->url, PHP_URL_PATH);
        }
        if ($basePath === '/') {
            $basePath = '';
        }
        // @fixme Resolve relative paths...

        /** @var string $path */
        foreach ($openApi->paths as $path => $pathItem) {
            foreach ($pathItem->getOperations() as $method => $operation) {
                $pointer     = $operation->getDocumentPosition()?->getPointer() ?? '';
                $method      = strtolower((string) $method);
                $pathParams  = $this->getParams($operation, 'path');
                $queryParams = $this->getParams($operation, 'query');
                $middleware  = $this->getMiddleware($operation);

                $collection->add(new RouteModel(
                    $pointer,
                    $basePath . $path,
                    $method,
                    $pathParams,
                    $queryParams,
                    $securityModelResolver->resolve($operation->security),
                    $middleware
                ));
            }
        }

        return $collection;
    }

    /**
     * @return list<ParameterModel>
     */
    public function getParams(Operation $operation, string $in): array
    {
        $parameters = array_filter($operation->parameters, function (Parameter|Reference $param) use ($in): bool {
            return $param instanceof Parameter && $param->in === $in;
        });

        $params = [];
        foreach ($parameters as $parameter) {
            $type    = $style = null;
            $explode = false;
            $schema  = $parameter->schema;
            if ($schema instanceof Schema) {
                $type    = $schema->type;
                $style   = $parameter->style;
                $explode = $parameter->explode;
            }
            $params[] = new ParameterModel(
                $parameter->name,
                $schema === null,
                $type,
                $style,
                $explode
            );
        }

        return $params;
    }

    /**
     * @return list<class-string>
     */
    private function getMiddleware(Operation $operation): array
    {
        $extensions = $operation->getExtensions();
        /** @var array<array-key, class-string> $middlewareNames */
        $middlewareNames = (array) ($extensions['x-psr15-middleware'] ?? null);
        if ($middlewareNames === []) {
            return [];
        }

        $middleware = [];
        foreach ($middlewareNames as $name) {
            if (isset($this->middleware[$name])) {
                $middleware[] = $this->middleware[$name];
            }
        }
        return $middleware;
    }
}

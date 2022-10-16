<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route\Converter;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\Converter\ConverterInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;

use function array_filter;
use function iterator_to_array;
use function sprintf;
use function str_replace;
use function trim;
use function usort;

/**
 * @see https://github.com/OAI/OpenAPI-Specification/issues/93 for optional param broohaha
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\Converter\FastRouteConverterTest
 */
final class FastRouteConverter implements ConverterInterface
{
    /**
     * @see https://spec.openapis.org/oas/v3.1.0#path-templating-matching
     * @see \FastRoute\DataGenerator\RegexBasedAbstract::addStaticRoute()
     *
     * @inheritDoc
     *
     * Sort operations to avoid static route shadowing
     */
    public function sort(HandlerCollection $collection): HandlerCollection
    {
        $handlers = iterator_to_array($collection);
        usort(
            $handlers,
            fn (HandlerClass $a, HandlerClass $b): int => $this->sortRoutes($a->getRoute(), $b->getRoute())
        );

        $sorted = new HandlerCollection();
        foreach ($handlers as $handler) {
            $sorted->add($handler);
        }

        return $sorted;
    }

    public function convert(OpenApiRoute $route): string
    {
        $operation = $route->getOperation();

        /** @var Parameter $pathParams */
        $pathParams = array_filter($operation->parameters, function (Parameter|Reference $param): bool {
            return $param instanceof Parameter && $param->in === 'path';
        });

        $search = $replace = [];
        // The spec does _not_ support optional params
        foreach ($pathParams as $parameter) {
            $search[]  = '{' . $parameter->name . '}';
            $replace[] = sprintf(
                '{%s:%s}',
                $parameter->name,
                $this->getRegexp($parameter->schema)
            );
        }

        return str_replace($search, $replace, Util::encodePath($route->getPath()));
    }

    private function sortRoutes(OpenApiRoute $first, OpenApiRoute $second): int
    {
        // replace '~' with space so '{' and '}' are sorted after it
        $firstPath  = str_replace('~', ' ', trim($first->getPath()));
        $secondPath = str_replace('~', ' ', trim($second->getPath()));

        if ($firstPath === $secondPath) {
            return $first->getMethod() <=> $second->getMethod();
        }
        return $firstPath <=> $secondPath;
    }

    /**
     * @see https://datatracker.ietf.org/doc/html/draft-bhutton-json-schema-00#section-4.2.1
     * @see https://spec.openapis.org/oas/v3.1.0#data-types
     */
    private function getRegexp(Schema $schema): string
    {
        // Do we need to support `null`, `array` and `object`?
        // @fixme: We need to support different serialization styles: 'label' / 'matrix'
        return match ($schema->type) {
            'boolean' => '(true|false)', // + (1|0|yes|no) ?
            'integer' => '\d+',
            'number'  => '[\d.]+',
            default => '.+',
        };
    }
}

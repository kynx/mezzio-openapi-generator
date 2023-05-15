<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route\Converter;

use Kynx\Mezzio\OpenApiGenerator\Route\ParameterModel;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil;

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
     * Sort routes to avoid static route shadowing
     */
    public function sort(RouteCollection $collection): RouteCollection
    {
        $handlers = iterator_to_array($collection);
        usort(
            $handlers,
            fn (RouteModel $a, RouteModel $b): int => $this->sortRoutes($a, $b)
        );

        $sorted = new RouteCollection();
        foreach ($handlers as $handler) {
            $sorted->add($handler);
        }

        return $sorted;
    }

    public function convert(RouteModel $route): string
    {
        $pathParams = $route->getPathParams();

        $search = $replace = [];
        // The spec does _not_ support optional params
        foreach ($pathParams as $parameter) {
            $search[]  = '{' . $parameter->getName() . '}';
            $replace[] = sprintf(
                '{%s:%s}',
                $parameter->getName(),
                $this->getRegexp($parameter)
            );
        }

        return str_replace($search, $replace, RouteUtil::encodePath($route->getPath()));
    }

    private function sortRoutes(RouteModel $first, RouteModel $second): int
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
    private function getRegexp(ParameterModel $parameter): string
    {
        if ($parameter->hasContent()) {
            return '.+';
        }

        return match ($parameter->getStyle()) {
            'simple' => $this->getSimpleRegexp($parameter),
            'label' => $this->getLabelRegexp($parameter),
            'matrix' => $this->getMatrixRegexp($parameter)
        };
    }

    private function getSimpleRegexp(ParameterModel $parameter): string
    {
        if ($parameter->getExplode()) {
            return match ($parameter->getType()) {
                'boolean' => 'true|false|,',
                'integer' => '[\d,]+',
                'number'  => '[\d\.,]+',
                default   => '[^/]+',
            };
        }

        return match ($parameter->getType()) {
            'boolean' => 'true|false',
            'integer' => '\d+',
            'number'  => '[\d\.]+',
            default   => '[^/]+',
        };
    }

    private function getLabelRegexp(ParameterModel $parameter): string
    {
        return '\.' . $this->getSimpleRegexp($parameter);
    }

    private function getMatrixRegexp(ParameterModel $parameter): string
    {
        return ';' . $this->getSimpleRegexp($parameter);
    }
}

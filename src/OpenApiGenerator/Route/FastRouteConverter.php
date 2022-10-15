<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApi\OpenApiSchema;
use Kynx\Mezzio\OpenApi\SchemaType;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerClass;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;

use function iterator_to_array;
use function sprintf;
use function str_replace;
use function trim;
use function usort;

/**
 * @see https://github.com/OAI/OpenAPI-Specification/issues/93 for optional param broohaha
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\FastRouteConverterTest
 */
final class FastRouteConverter implements RouteConverterInterface
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
            fn (HandlerClass $a, HandlerClass $b): int => $this->sortOperations($a->getOperation(), $b->getOperation())
        );

        $sorted = new HandlerCollection();
        foreach ($handlers as $handler) {
            $sorted->add($handler);
        }

        return $sorted;
    }

    public function convert(OpenApiOperation $operation): string
    {
        $search = $replace = [];

        // The spec does _not_ support optional params
        foreach ($operation->getRouteParameters() as $parameter) {
            $search[]  = '{' . $parameter->getName() . '}';
            $replace[] = sprintf(
                '{%s:%s}',
                $parameter->getName(),
                $this->getRegexp($parameter->getSchema())
            );
        }

        return str_replace($search, $replace, Util::encodePath($operation->getPath()));
    }

    private function sortOperations(OpenApiOperation $first, OpenApiOperation $second): int
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
    private function getRegexp(OpenApiSchema $schema): string
    {
        // Do we need to support `null`, `array` and `object`?
        // @fixme: We need to support different serialization styles: 'label' / 'matrix'
        return match ($schema->getType()) {
            SchemaType::Boolean => '(true|false)', // + (1|0|yes|no) ?
            SchemaType::Integer => '\d+',
            SchemaType::Number  => '[\d.]+',
            default => '.+',
        };
    }
}

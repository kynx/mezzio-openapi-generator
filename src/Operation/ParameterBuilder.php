<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;

use function array_filter;
use function array_keys;
use function array_map;
use function current;
use function implode;
use function str_replace;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\ParameterBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ParameterBuilder
{
    public function __construct(
        private readonly UniqueVariableLabeler $propertyLabeler,
        private readonly PropertyBuilder $propertyBuilder
    ) {
    }

    /**
     * @param array<string, string> $classNames
     */
    public function getParameterModel(
        Operation $operation,
        string $path,
        string $className,
        string $in,
        array $classNames
    ): CookieOrHeaderParams|PathOrQueryParams|null {
        /** @var list<Parameter> $params */
        $params = array_filter($operation->parameters, function (Parameter|Reference $parameter) use ($in) {
            return $parameter instanceof Parameter && $parameter->in === $in;
        });

        if ($params === []) {
            return null;
        }

        $propertyNames = array_map(fn (Parameter $parameter): string => $parameter->name, $params);
        /** @var array<string, string> $names */
        $names      = $this->propertyLabeler->getUnique($propertyNames);
        $properties = [];

        foreach ($params as $parameter) {
            $name   = $parameter->name;
            $schema = null;

            if (isset($parameter->schema)) {
                $schema = $parameter->schema;
            } elseif ($parameter->content !== []) {
                $content = current($parameter->content);
                $schema  = $content->schema ?? null;
            }

            if ($schema instanceof Schema) {
                $properties[] = $this->propertyBuilder->getProperty(
                    $schema,
                    $names[$name],
                    $name,
                    $parameter->required,
                    $classNames
                );
            }
        }

        $pointer = $operation->getDocumentPosition()?->getPointer() ?? '';
        // note fake pointer - a group of params does not have a single pointer
        $model = new ClassModel($className, $pointer . '/parameters/' . $in, [], ...$properties);

        return match ($in) {
            'cookie' => new CookieOrHeaderParams($this->getCookieTemplates($params), $model),
            'header' => new CookieOrHeaderParams($this->getHeaderTemplates($params), $model),
            'path'   => new PathOrQueryParams($this->getPathTemplate($params, $path), $model),
            'query'  => new PathOrQueryParams($this->getQueryTemplate($params), $model),
        };
    }

    /**
     * @param list<Parameter> $params
     */
    private function getCookieTemplates(array $params): array
    {
        $templates = [];
        foreach ($params as $param) {
            if (! (isset($param->schema) && $param->style === 'form')) {
                continue;
            }
            $templates[$param->name] = '{' . $param->name . $this->getExplode($param) . '}';
        }

        return $templates;
    }

    /**
     * @param list<Parameter> $params
     */
    private function getHeaderTemplates(array $params): array
    {
        $templates = [];
        foreach ($params as $param) {
            if (! (isset($param->schema) && $param->style === 'simple')) {
                continue;
            }
            $templates[$param->name] = '{' . $param->name . $this->getExplode($param) . '}';
        }

        return $templates;
    }

    /**
     * @param list<Parameter> $params
     */
    private function getPathTemplate(array $params, string $path): string
    {
        $replace = [];
        foreach ($params as $param) {
            $key           = '{' . $param->name . '}';
            $explode       = $this->getExplode($param);
            $replace[$key] = match ($param->style) {
                'simple' => '{' . $param->name . $explode . '}',
                'label'  => '{.' . $param->name . $explode . '}',
                'matrix' => '{;' . $param->name . $explode . '}',
            };
        }

        return str_replace(array_keys($replace), $replace, $path);
    }

    /**
     * @param list<Parameter> $params
     */
    private function getQueryTemplate(array $params): string
    {
        $parts = [];

        foreach ($params as $param) {
            if (! isset($param->schema)) {
                continue;
            }

            $explode = $this->getExplode($param);
            $parts[] = match ($param->style) {
                'form'           => $param->name . $explode,
                'spaceDelimited' => $param->name . ($explode ?: '_'),
                'pipeDelimited'  => $param->name . ($explode ?: '|'),
                'deepObject'     => $param->name . '%',
            };
        }

        if ($parts === []) {
            return '';
        }
        return '{?' . implode(',', $parts) . '}';
    }

    private function getExplode(Parameter $param): string
    {
        return $param->explode ? '*' : '';
    }
}

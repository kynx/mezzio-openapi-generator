<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;

use function array_filter;
use function array_map;
use function array_pop;
use function assert;
use function count;
use function current;
use function in_array;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\OperationBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class OperationBuilder
{
    public function __construct(
        private readonly UniqueVariableLabeler $propertyLabeler,
        private readonly PropertyBuilder $propertyBuilder = new PropertyBuilder()
    ) {
    }

    /**
     * @param array<string, string> $classNames
     * @return list<AbstractClassLikeModel>
     */
    public function getModels(NamedSpecification $namedSpec, array $classNames): array
    {
        $pointer = $namedSpec->getJsonPointer();
        assert(isset($classNames[$pointer]));

        $className = $classNames[$pointer];
        $operation = $namedSpec->getSpecification();
        assert($operation instanceof Operation);

        /** @var list<AbstractClassLikeModel> $models */
        $models = [];
        /** @var list<PropertyInterface> $properties */
        $properties = [];
        $metadata   = ['required' => true];
        $positions  = [
            'path'   => $className . '\PathParams',
            'query'  => $className . '\QueryParams',
            'header' => $className . '\HeaderParams',
            'cookie' => $className . '\CookieParams',
        ];
        foreach ($positions as $in => $name) {
            $model = $this->getParamModel($operation, $name, $in, $classNames);
            if ($model !== null) {
                $properties[] = new SimpleProperty(
                    '$' . $in . 'Params',
                    '',
                    new PropertyMetadata(...$metadata),
                    $model->getClassName()
                );
                $models[]     = $model;
            }
        }

        $requestBody = $this->getRequestBodyProperty($operation, $classNames);
        if ($requestBody !== null) {
            $properties[] = $requestBody;
        }

        if ($properties === []) {
            return [];
        }

        $pointer  = $operation->getDocumentPosition()?->getPointer() ?? '';
        $models[] = new OperationModel($className, $pointer, ...$properties);

        return $models;
    }

    /**
     * @param array<string, string> $classNames
     */
    private function getParamModel(
        Operation $operation,
        string $className,
        string $in,
        array $classNames
    ): ClassModel|null {
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
                $content = array_pop($parameter->content);
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
        return new ClassModel($className, $pointer . '/parameters/' . $in, [], ...$properties);
    }

    /**
     * @param array<string, string> $classNames
     */
    private function getRequestBodyProperty(Operation $operation, array $classNames): PropertyInterface|null
    {
        $requestBody = $operation->requestBody;
        if (! $requestBody instanceof RequestBody) {
            return null;
        }

        /** @var list<PropertyType|string> $types */
        $types = [];
        /** @var list<ArrayProperty> $arrayProperties */
        $arrayProperties = [];
        foreach ($requestBody->content as $mediaType) {
            assert($mediaType->schema instanceof Schema);

            $toAdd    = [];
            $array    = null;
            $property = $this->propertyBuilder->getProperty($mediaType->schema, '', '', false, $classNames);
            if ($property instanceof SimpleProperty) {
                $toAdd[] = $property->getType();
            } elseif ($property instanceof UnionProperty) {
                $toAdd = $property->getMembers();
            } elseif ($property instanceof ArrayProperty) {
                $array = $property;
            }

            foreach ($toAdd as $type) {
                if (! in_array($type, $types)) {
                    $types[] = $type;
                }
            }

            if ($array !== null && ! in_array($array, $arrayProperties)) {
                $arrayProperties[] = $array;
            }
        }

        $metadata = new PropertyMetadata(...['required' => $requestBody->required]);

        if ($types === [] && count($arrayProperties) === 1) {
            $array = current($arrayProperties);
            return new ArrayProperty('$requestBody', '', $metadata, $array->isList(), $array->getMemberType());
        }

        if ($arrayProperties !== [] && ! in_array(PropertyType::Array, $types)) {
            $types[] = PropertyType::Array;
        }

        if (count($types) === 1) {
            return new SimpleProperty('$requestBody', '', $metadata, current($types));
        }
        return new UnionProperty('$requestBody', '', $metadata, ...$types);
    }
}

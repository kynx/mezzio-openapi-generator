<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;

use function array_combine;
use function array_merge;
use function array_pad;
use function array_slice;
use function array_unique;
use function count;
use function explode;
use function implode;
use function in_array;
use function ksort;
use function preg_replace;
use function str_starts_with;
use function ucfirst;
use function uksort;
use function usort;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Generator\AbstractGeneratorTest
 *
 * @psalm-type UsesArray = array<string, string|null>
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
abstract class AbstractGenerator
{
    /**
     * @return list<PropertyInterface> $property
     */
    protected function getOrderedParameters(AbstractClassLikeModel $model): array
    {
        $properties = $model->getProperties();
        usort($properties, function (PropertyInterface $a, PropertyInterface $b): int {
            $sort = $this->getOrder($a->getMetadata()) <=> $this->getOrder($b->getMetadata());
            if ($sort === 0) {
                return $a->getName() <=> $b->getName();
            }

            return $sort;
        });

        return $properties;
    }

    private function getOrder(PropertyMetadata $metadata): int
    {
        if ($metadata->getDefault() !== null) {
            return 1;
        }
        if ($metadata->isNullable()) {
            return 2;
        }
        return $metadata->isRequired() ? 0 : 2;
    }

    protected function getClassLikeName(AbstractClassLikeModel|EnumModel $modelClass): string
    {
        return GeneratorUtil::getClassName($modelClass->getClassName());
    }

    protected function getMethodName(PropertyInterface $property): string
    {
        $propertyName = $this->normalizePropertyName($property);
        if ($property instanceof SimpleProperty && $property->getType() === PropertyType::Boolean) {
            return str_starts_with($propertyName, 'is') ? $propertyName : 'is' . ucfirst($propertyName);
        }
        return 'get' . ucfirst($propertyName);
    }

    protected function getType(PropertyInterface $property): string
    {
        $types = [$property->getPhpType()];

        $metadata = $property->getMetadata();
        if ($metadata->isNullable() || ! $metadata->isRequired()) {
            $types[] = 'null';
        }

        return implode('|', $types);
    }

    /**
     * @param list<PropertyInterface> $properties
     * @return UsesArray
     */
    protected function getPropertyUses(array $properties): array
    {
        $uses = [];
        foreach ($properties as $property) {
            $uses = array_merge($uses, $property->getUses());
        }

        $uses = array_unique($uses);
        uksort(
            $uses,
            fn (mixed $a, mixed $b): int => count(explode('\\', (string) $b)) <=> count(explode('\\', (string) $a))
        );
        $aliased = $this->createAliases(array_combine($uses, array_pad([], count($uses), null)));
        ksort($aliased);

        return $aliased;
    }

    protected function normalizePropertyName(PropertyInterface $property): string
    {
        return preg_replace('/^\$/', '', $property->getName());
    }

    /**
     * @param UsesArray $fqns
     * @return UsesArray
     */
    private function createAliases(array $fqns, int $segments = 2): array
    {
        $duplicates = $previous = [];
        foreach ($fqns as $fqn => $alias) {
            $key = $alias ?? GeneratorUtil::getClassName($fqn);
            if (! in_array($key, $previous)) {
                $previous[] = $key;
                continue;
            }
            $duplicates[$key][] = $fqn;
        }

        if (count($duplicates) === 0) {
            return $fqns;
        }

        foreach ($duplicates as $toAlias) {
            foreach ($toAlias as $fqn) {
                $parts = explode('\\', $fqn);
                if (count($parts) < $segments) {
                    // can't be aliased...
                    $fqns[$fqn] = $fqn;
                    continue;
                }
                $fqns[$fqn] = implode('', array_slice($parts, 0 - $segments));
            }
        }

        return $this->createAliases($fqns, $segments + 1);
    }
}

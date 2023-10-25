<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\AbstractClassLikeModel;
use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;

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
use function uksort;

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
    protected function getClassLikeName(AbstractClassLikeModel|EnumModel $modelClass): string
    {
        return GeneratorUtil::getClassName($modelClass->getClassName());
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

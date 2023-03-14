<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Nette\PhpGenerator\Dumper;

use function array_pop;
use function array_slice;
use function explode;
use function implode;
use function ltrim;
use function preg_replace;
use function str_starts_with;
use function ucfirst;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\GeneratorUtilTest
 *
 * @psalm-immutable
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class GeneratorUtil
{
    private function __construct()
    {
    }

    /**
     * @psalm-mutation-free
     */
    public static function getNamespace(string $classString): string
    {
        $namespace = implode('\\', array_slice(explode('\\', $classString), 0, -1));
        return ltrim($namespace, '\\');
    }

    /**
     * @psalm-mutation-free
     */
    public static function getClassName(string $fqn): string
    {
        $parts = explode('\\', $fqn);
        return array_pop($parts);
    }

    public static function getMethodName(PropertyInterface $property): string
    {
        $propertyName = self::normalizePropertyName($property);
        if ($property instanceof SimpleProperty && $property->getType() === PropertyType::Boolean) {
            return str_starts_with($propertyName, 'is') ? $propertyName : 'is' . ucfirst($propertyName);
        }
        return 'get' . ucfirst($propertyName);
    }

    public static function getAlias(string $shortName): string
    {
        $parts = explode('\\', $shortName);
        if (count($parts) < 2) {
            return $shortName;
        }
        return implode('', array_slice($parts, 1));
    }

    public static function normalizePropertyName(PropertyInterface $property): string
    {
        return preg_replace('/^\$/', '', $property->getName());
    }

    /**
     * @psalm-mutation-free
     */
    public static function formatAsList(Dumper $dumper, array $list): string
    {
        /** @psalm-suppress ImpureMethodCall */
        $dump = $dumper->dump($list);
        return preg_replace('/^\s*\[(.*)]\s*$/s', '$1', $dump);
    }
}

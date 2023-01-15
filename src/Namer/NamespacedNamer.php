<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Namer;

use Kynx\Code\Normalizer\UniqueClassLabeler;

use function array_combine;
use function array_map;
use function preg_replace;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Namer\NamespacedNamerTest
 */
final class NamespacedNamer implements NamerInterface
{
    public function __construct(private readonly string $baseNamespace, private readonly UniqueClassLabeler $labeler)
    {
    }

    public function keyByUniqueName(array $names): array
    {
        $namespaced = array_map(
            fn (string $name): string => preg_replace('/\s+/', '\\', $name),
            $names
        );
        $classNames = array_map(
            fn (string $unique): string => $this->baseNamespace . '\\' . $unique,
            $this->labeler->getUnique($namespaced)
        );
        return array_combine($classNames, $names);
    }
}

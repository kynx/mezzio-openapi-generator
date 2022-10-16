<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler\Namer;

use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Mezzio\OpenApiGenerator\Handler\Namer\NamerInterface;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;

use function array_combine;
use function array_map;
use function array_slice;
use function explode;
use function implode;
use function preg_replace;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\Namer\FlatNamerTest
 */
final class FlatNamer implements NamerInterface
{
    public function __construct(private readonly string $baseNamespace, private readonly UniqueClassLabeler $labeler)
    {
    }

    public function keyByUniqueName(array $routes): array
    {
        $labels = array_map(fn (OpenApiRoute $route): string => $this->getName($route), $routes);
        /** @var array<array-key, class-string> $unique */
        $unique = $this->labeler->getUnique($labels);
        return array_combine($unique, $routes);
    }

    private function getName(OpenApiRoute $route): string
    {
        if ($route->getOperation()->operationId) {
            return $this->baseNamespace . '\\' . $route->getOperation()->operationId;
        }

        $parts   = array_slice(explode('/', $route->getPath()), 1);
        $parts   = array_map(
            fn (string $part): string => preg_replace('/\{(.*)}/Uu', '$1', $part),
            $parts
        );
        $parts[] = $route->getMethod();

        return $this->baseNamespace . '\\' . implode(' ', $parts);
    }
}

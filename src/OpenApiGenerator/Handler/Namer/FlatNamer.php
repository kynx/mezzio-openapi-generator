<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler\Namer;

use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil;

use function array_combine;
use function array_map;
use function implode;

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

        $parts   = RouteUtil::getPathParts($route->getPath());
        $parts[] = $route->getMethod();

        return $this->baseNamespace . '\\' . implode(' ', $parts);
    }
}

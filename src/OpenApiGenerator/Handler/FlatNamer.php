<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Mezzio\OpenApi\OpenApiOperation;

use function array_combine;
use function array_map;
use function array_slice;
use function explode;
use function implode;
use function preg_replace;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\FlatNamerTest
 */
final class FlatNamer implements HandlerNamerInterface
{
    public function __construct(private string $baseNamespace, private UniqueClassLabeler $labeler)
    {
    }

    public function keyByUniqueName(array $operations): array
    {
        $labels = array_map(fn (OpenApiOperation $operation): string => $this->getName($operation), $operations);
        /** @var array<array-key, string> $unique */
        $unique = $this->labeler->getUnique($labels);
        return array_combine($unique, $operations);
    }

    private function getName(OpenApiOperation $operation): string
    {
        if ($operation->getOperationId()) {
            return $this->baseNamespace . '\\' . $operation->getOperationId();
        }

        $parts   = array_slice(explode('/', $operation->getPath()), 1);
        $parts   = array_map(
            fn (string $part): string => preg_replace('/\{(.*)}/Uu', '$1', $part),
            $parts
        );
        $parts[] = $operation->getMethod();

        return $this->baseNamespace . '\\' . implode(' ', $parts);
    }
}

<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use cebe\openapi\spec\Operation;

use Kynx\CodeUtils\ClassNameNormalizer;
use Kynx\Mezzio\OpenApi\OpenApiOperation;

use Kynx\Mezzio\OpenApiGenerator\Route\Util;

use function array_map;
use function array_merge;
use function array_slice;
use function explode;
use function implode;
use function in_array;
use function preg_replace;
use function preg_split;
use function strtolower;
use function ucfirst;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\FlatNamerTest
 */
final class FlatNamer implements HandlerNamerInterface
{
    private string $baseNamespace;
    private ClassNameNormalizer $normalizer;

    public function __construct(string $baseNamespace, ClassNameNormalizer $normalizer)
    {
        $this->baseNamespace = $baseNamespace;
        $this->normalizer = $normalizer;
    }

    public function getName(OpenApiOperation $operation): string
    {
        if ($operation->getOperationId()) {
            return $this->normalizer->normalize($this->baseNamespace . '\\' . $operation->getOperationId());
        }

        $parts = array_slice(explode('/', $operation->getPath()), 1);
        $parts = array_map(
            fn (string $part): string => preg_replace('/\{(.*)}/Uu', '$1', $part),
            $parts
        );
        $parts[] = $operation->getMethod();
        $className = implode('_', $parts);

        return $this->normalizer->normalize($this->baseNamespace . '\\' . $className);
    }
}
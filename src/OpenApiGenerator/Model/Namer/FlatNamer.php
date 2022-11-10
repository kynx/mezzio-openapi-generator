<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Namer;

use Kynx\Code\Normalizer\UniqueClassLabeler;

use function array_combine;

final class FlatNamer implements NamerInterface
{
    public function __construct(private readonly string $baseNamespace, private readonly UniqueClassLabeler $labeler)
    {
    }

    public function keyByUniqueName(array $names): array
    {
        $classNames = array_map(
            fn (string $unique): string => $this->baseNamespace . '\\' . $unique,
            $this->labeler->getUnique($names)
        );
        return array_combine($classNames, $names);
    }
}
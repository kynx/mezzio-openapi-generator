<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Namer;

interface NamerInterface
{
    /**
     * @param list<string> $names
     * @return array<string, string>
     */
    public function keyByUniqueName(array $names): array;
}

<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Asset;

use Kynx\Mezzio\OpenApi\Attribute\NotOverwritable;

#[NotOverwritable]
final class DoNotWrite
{
    public function custom(): void
    {
    }
}

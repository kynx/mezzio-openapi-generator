<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;

interface ConfigProviderWriterInterface
{
    public function write(
        OperationCollection $operations,
        HandlerCollection $handlers,
        string $delegatorClassName
    ): void;
}

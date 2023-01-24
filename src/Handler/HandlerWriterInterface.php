<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

interface HandlerWriterInterface
{
    public function write(HandlerCollection $handlerCollection): void;
}

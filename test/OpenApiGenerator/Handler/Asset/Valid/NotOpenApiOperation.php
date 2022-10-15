<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler\Asset\Valid;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class NotOpenApiOperation implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
    }
}

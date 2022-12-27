<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler\Asset\Invalid;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OpenApiOperation()]
final class BadOpenApiOperation implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
    }
}

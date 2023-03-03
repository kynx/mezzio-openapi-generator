<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler\Asset\Invalid;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OpenApiRequest()]
final class BadOpenApiOperation implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
    }
}

<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler\Asset\Valid;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OpenApiRequest('myId', '/my-path', 'post')]
final class Handler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
    }
}

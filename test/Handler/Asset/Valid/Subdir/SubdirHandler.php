<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler\Asset\Valid\Subdir;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OpenApiOperation('my-subId', '/subdir/path', 'get')]
final class SubdirHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
    }
}

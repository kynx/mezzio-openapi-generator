<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Route\RouteDelegatorWriterTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RouteDelegatorWriter implements RouteDelegatorWriterInterface
{
    public function __construct(
        private readonly RouteDelegatorGenerator $generator,
        private readonly WriterInterface $writer
    ) {
    }

    public function getDelegatorClassName(): string
    {
        return $this->generator->getClassName();
    }

    public function write(RouteCollection $routes, HandlerCollection $handlerCollection): void
    {
        $handlerMap = $handlerCollection->getHandlerMap();
        $file       = $this->generator->generate($routes, $handlerMap);
        $this->writer->write($file);
    }
}

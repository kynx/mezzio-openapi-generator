<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\FileSystemLocatorTest
 */
interface HandlerLocatorInterface
{
    /**
     * Returns new collection populated with handler files
     */
    public function create(): HandlerCollection;
}

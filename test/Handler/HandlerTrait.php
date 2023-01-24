<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamerInterface;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;

trait HandlerTrait
{
    protected function getHandlerCollectionBuilder(string $namespace): HandlerCollectionBuilder
    {
        return new HandlerCollectionBuilder($this->getHandlerClassNamer($namespace));
    }

    protected function getHandlerClassNamer(string $namespace): NamerInterface
    {
        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Handler'), new NumberSuffix());
        return new NamespacedNamer($namespace, $classLabeler);
    }
}

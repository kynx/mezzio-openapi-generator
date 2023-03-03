<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Handler;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerModel;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamerInterface;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;

use function array_map;
use function array_slice;
use function count;
use function explode;
use function implode;
use function iterator_to_array;

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

    /**
     * @param array<int, HandlerModel> $handlers
     */
    protected function getHandlerCollection(array $handlers): HandlerCollection
    {
        $collection = new HandlerCollection();
        foreach ($handlers as $handler) {
            $collection->add($handler);
        }

        return $collection;
    }

    /**
     * @return array<int, HandlerModel>
     */
    protected function getHandlers(
        OperationCollection $operations,
        string $operationNamespace = 'Api\\Operation',
        string $namespace = 'Api\\Handler'
    ): array {
        return array_map(function (OperationModel $operation) use ($operationNamespace, $namespace): HandlerModel {
            $parts     = explode('\\', $operation->getClassName());
            $className = $namespace . '\\'
                . implode('\\', array_slice($parts, count(explode('\\', $operationNamespace)), -1))
                . 'Handler';
            return new HandlerModel($operation->getJsonPointer(), $className, $operation);
        }, iterator_to_array($operations));
    }
}

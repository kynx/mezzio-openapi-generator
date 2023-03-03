<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\RequestFactoryGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\RequestGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\ResponseFactoryGenerator;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;

use function array_merge;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\OperationWriterTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationWriter implements OperationWriterInterface
{
    public function __construct(
        private readonly ModelGenerator $modelGenerator,
        private readonly HydratorGenerator $hydratorGenerator,
        private readonly RequestGenerator $operationGenerator,
        private readonly RequestFactoryGenerator $operationFactoryGenerator,
        private readonly ResponseFactoryGenerator $responseFactoryGenerator,
        private readonly WriterInterface $writer
    ) {
    }

    public function write(OperationCollection $operations, HydratorCollection $hydratorCollection): void
    {
        $operationModels = $this->getModelCollection($operations);
        foreach ($operationModels as $model) {
            $file = $this->modelGenerator->generate($model);
            $this->writer->write($file);
        }

        $hydrators   = HydratorCollection::fromModelCollection($operationModels);
        $hydratorMap = $hydrators->getHydratorMap();
        foreach ($hydrators as $hydrator) {
            $file = $this->hydratorGenerator->generate($hydrator, $hydratorMap);
            $this->writer->write($file);
        }

        $hydratorMap = array_merge($hydratorCollection->getHydratorMap(), $hydratorMap);
        foreach ($operations as $operation) {
            if ($operation->hasParameters()) {
                $file = $this->operationGenerator->generate($operation);
                $this->writer->write($file);

                $factory = $this->operationFactoryGenerator->generate($operation, $hydratorMap);
                $this->writer->write($factory);
            }

            $responseFactory = $this->responseFactoryGenerator->generate($operation, $hydratorMap);
            $this->writer->write($responseFactory);
        }
    }

    private function getModelCollection(OperationCollection $operations): ModelCollection
    {
        $collection = new ModelCollection();
        foreach ($operations as $operation) {
            foreach ($operation->getModels() as $model) {
                $collection->add($model);
            }
        }

        return $collection;
    }
}

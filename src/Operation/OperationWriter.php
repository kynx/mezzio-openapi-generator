<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\OperationGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\RequestParserGenerator;
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
        private readonly OperationGenerator $operationGenerator,
        private readonly RequestParserGenerator $requestParserGenerator,
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
            if (! $operation->hasParameters()) {
                continue;
            }

            $file = $this->operationGenerator->generate($operation);
            $this->writer->write($file);

            $parser = $this->requestParserGenerator->generate($operation, $hydratorMap);
            $this->writer->write($parser);

            if ($operation->getRequestBodies() !== []) {
                $factory = $this->requestParserFactoryGenerator->generate($operation);
                $this->writer->write($factory);
            }
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

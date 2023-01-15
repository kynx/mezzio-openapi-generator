<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorCollection;
use Kynx\Mezzio\OpenApiGenerator\Hydrator\HydratorWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\ExistingModels;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollection;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterInterface;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;

final class GenerateService implements GenerateServiceInterface
{
    public function __construct(
        private readonly OpenApiLocator $locator,
        private readonly ModelCollectionBuilder $collectionBuilder,
        private readonly ExistingModels $existingModels,
        private readonly ModelWriterInterface $modelWriter,
        private readonly HydratorWriterInterface $hydratorWriter
    ) {
    }

    public function getModels(OpenApi $openApi): ModelCollection
    {
        $namedSchemas = $this->locator->getNamedSpecifications($openApi);
        $collection   = $this->collectionBuilder->getModelCollection($namedSchemas);
        return $this->existingModels->updateClassNames($collection);
    }

    public function createModels(ModelCollection $collection): void
    {
        $this->modelWriter->write($collection);
    }

    public function createHydrators(ModelCollection $collection): void
    {
        $hydratorCollection = HydratorCollection::fromModelCollection($collection);
        $this->hydratorWriter->write($hydratorCollection);
    }
}

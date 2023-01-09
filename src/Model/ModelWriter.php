<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\WriterInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelWriterTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ModelWriter implements ModelWriterInterface
{
    public function __construct(
        private readonly OpenApiLocator $locator,
        private readonly ModelCollectionBuilder $collectionBuilder,
        private readonly ExistingModels $existingModels,
        private readonly ModelGenerator $generator,
        private readonly WriterInterface $writer
    ) {
    }

    public function write(OpenApi $openApi): void
    {
        $namedSchemas = $this->locator->getNamedSchemas($openApi);
        $collection   = $this->collectionBuilder->getModelCollection($namedSchemas);
        $updated      = $this->existingModels->updateClassNames($collection);

        foreach ($updated as $model) {
            $file = $this->generator->generate($model);
            $this->writer->write($file);
        }
    }
}

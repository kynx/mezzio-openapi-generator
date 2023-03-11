<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\ConstantNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueConstantLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
use Kynx\Code\Normalizer\WordCase;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelsBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Namer\NamespacedNamer;

trait ModelTrait
{
    protected function getModelsBuilder(PropertiesBuilder $propertiesBuilder): ModelsBuilder
    {
        $caseLabeler = new UniqueConstantLabeler(
            new ConstantNameNormalizer('Case', WordCase::Pascal),
            new NumberSuffix()
        );

        return new ModelsBuilder($propertiesBuilder, $caseLabeler);
    }

    protected function getModelCollectionBuilder(
        PropertiesBuilder $propertiesBuilder,
        string $namespace
    ): ModelCollectionBuilder {
        $classLabeler = new UniqueClassLabeler(new ClassNameNormalizer('Model'), new NumberSuffix());
        $classNamer   = new NamespacedNamer($namespace, $classLabeler);

        return new ModelCollectionBuilder($classNamer, $this->getModelsBuilder($propertiesBuilder));
    }

    protected function getPropertyLabeler(): UniqueVariableLabeler
    {
        return new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix());
    }
}

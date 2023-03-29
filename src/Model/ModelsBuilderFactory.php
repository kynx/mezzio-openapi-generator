<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Code\Normalizer\ClassConstantNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassConstantLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\WordCase;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelsBuilderFactoryTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ModelsBuilderFactory
{
    public function __invoke(ContainerInterface $container): ModelsBuilder
    {
        $caseLabeler = new UniqueClassConstantLabeler(
            new ClassConstantNameNormalizer('Case', WordCase::Pascal),
            new NumberSuffix()
        );
        return new ModelsBuilder(
            $container->get(PropertiesBuilder::class),
            $caseLabeler
        );
    }
}

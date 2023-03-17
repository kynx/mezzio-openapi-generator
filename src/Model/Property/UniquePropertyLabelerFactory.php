<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\VariableNameNormalizer;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class UniquePropertyLabelerFactory
{
    public function __invoke(ContainerInterface $container): UniqueVariableLabeler
    {
        // @fixme Why aren't we using UniquePropertyLabeler?!
        return new UniqueVariableLabeler(new VariableNameNormalizer(), new NumberSuffix());
    }
}

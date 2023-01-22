<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Mapper;

use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class TypeMapperFactory
{
    public function __invoke(ContainerInterface $container): TypeMapper
    {
        /** @var array $config */
        $config = $container->get('config');
        /** @var array<array-key, class-string<TypeMapperInterface>> $mappers */
        $mappers = $config['openapi-gen']['type_mappers'] ?? [];

        $instances = [];
        foreach ($mappers as $mapper) {
            $instances[] = new $mapper();
        }

        return new TypeMapper(...$instances);
    }
}

<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Mapper;

use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;

use function array_values;
use function is_array;

/**
 * @internal
 *
 * @psalm-immutable
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class TypeMapper
{
    /** @var list<TypeMapperInterface>  */
    private array $mappers;

    public function __construct(TypeMapperInterface ...$mappers)
    {
        $this->mappers = array_values($mappers);
    }

    /**
     * @throws ModelException
     */
    public function map(Schema $schema): ClassString|PropertyType
    {
        if (is_array($schema->type)) {
            throw ModelException::unrecognizedType($schema->type);
        }

        foreach ($this->mappers as $mapper) {
            if ($mapper->canMap($schema->type, $schema->format)) {
                return new ClassString($mapper->getClassString($schema->type, $schema->format));
            }
        }

        return PropertyType::fromSchema($schema);
    }
}

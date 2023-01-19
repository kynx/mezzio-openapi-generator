<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Namer\NamerInterface;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;

use function array_combine;
use function array_keys;
use function array_merge;
use function array_values;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationCollectionBuilder
{
    public function __construct(
        private readonly NamerInterface $namer,
        private readonly OperationBuilder $builder
    ) {
    }

    /**
     * @param list<NamedSpecification> $namedSpecifications
     * @param array<string, string> $classMap
     */
    public function getOperationCollection(array $namedSpecifications, array $classMap): OperationCollection
    {
        $collection = new OperationCollection();

        foreach ($this->getOperations($namedSpecifications, $classMap) as $operation) {
            $collection->add($operation);
        }

        return $collection;
    }

    /**
     * @param list<NamedSpecification> $namedSpecifications
     * @param array<string, string> $classMap
     * @return list<OperationModel>
     */
    private function getOperations(array $namedSpecifications, array $classMap): array
    {
        $classNames = array_merge($classMap, $this->getOperationClasses($namedSpecifications));

        $operations = [];
        foreach ($namedSpecifications as $specification) {
            $operations[] = $this->builder->getOperationModel($specification, $classNames);
        }

        return $operations;
    }

    /**
     * @param list<NamedSpecification> $namedSpecifications
     * @return array<string, string>
     */
    private function getOperationClasses(array $namedSpecifications): array
    {
        $names = [];
        foreach ($namedSpecifications as $namedSchema) {
            $names[$namedSchema->getJsonPointer()] = $namedSchema->getName();
        }
        return array_combine(
            array_keys($names),
            array_keys($this->namer->keyByUniqueName(array_values($names)))
        );
    }
}

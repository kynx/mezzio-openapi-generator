<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\Namer\NamerInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;

use function array_combine;
use function array_keys;
use function array_merge;
use function array_values;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelCollectionBuilderTest
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelCollectionFactoryEnd2EndTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ModelCollectionBuilder
{
    public function __construct(
        private readonly NamerInterface $classNamer,
        private readonly ModelsBuilder $modelsBuilder
    ) {
    }

    /**
     * @param list<NamedSpecification> $namedSchemas
     */
    public function getModelCollection(array $namedSchemas): ModelCollection
    {
        $collection = new ModelCollection();

        foreach ($this->getModels($namedSchemas) as $schemaClass) {
            $collection->add($schemaClass);
        }

        return $collection;
    }

    /**
     * @param list<NamedSpecification> $namedSchemas
     * @return list<ClassModel|EnumModel|InterfaceModel>
     */
    private function getModels(array $namedSchemas): array
    {
        $classNames = $this->getClassNames($namedSchemas);
        /** @var array<string, string> $interfaceNames */
        $interfaceNames = $this->getInterfaceNames($namedSchemas, $classNames);

        $models = [];
        foreach ($namedSchemas as $namedSchema) {
            $models = array_merge($models, $this->modelsBuilder->getModels($namedSchema, $classNames, $interfaceNames));
        }

        return $models;
    }

    /**
     * @param list<NamedSpecification> $namedSchemas
     * @return array<string, string>
     */
    private function getClassNames(array $namedSchemas): array
    {
        $names = [];
        foreach ($namedSchemas as $namedSchema) {
            $names[$namedSchema->getJsonPointer()] = $namedSchema->getName();
        }
        return array_combine(
            array_keys($names),
            array_keys($this->classNamer->keyByUniqueName(array_values($names)))
        );
    }

    /**
     * @param list<NamedSpecification> $namedSchemas
     * @param array<string, string> $classNames
     */
    private function getInterfaceNames(array $namedSchemas, array $classNames): array
    {
        $names = [];
        foreach ($namedSchemas as $namedSchema) {
            $schema = $namedSchema->getSpecification();
            if (empty($schema->allOf)) {
                continue;
            }
            foreach ($schema->allOf as $component) {
                $pointer = $component->getDocumentPosition()?->getPointer() ?? '';
                if (isset($classNames[$pointer])) {
                    $names[$pointer] = $classNames[$pointer] . 'Interface';
                }
            }
        }

        return $names;
    }
}

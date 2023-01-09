<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\spec\Schema;
use Kynx\Code\Normalizer\UniqueConstantLabeler;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertiesBuilder;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;

use function array_map;
use function assert;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelsBuilderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ModelsBuilder
{
    public function __construct(
        private readonly PropertiesBuilder $propertiesBuilder,
        private readonly UniqueConstantLabeler $caseLabeler
    ) {
    }

    /**
     * @param array<string, string> $classNames
     * @param array<string, string> $interfaceNames
     * @return list<AbstractClassLikeModel|EnumModel>
     */
    public function getModels(NamedSpecification $namedSpec, array $classNames, array $interfaceNames): array
    {
        $pointer = $namedSpec->getJsonPointer();
        assert(isset($classNames[$pointer]));

        $className = $classNames[$pointer];
        $schema    = $namedSpec->getSpecification();
        assert($schema instanceof Schema);

        if (ModelUtil::isEnum($schema)) {
            return [new EnumModel($className, $pointer, ...$this->getCases($schema))];
        }

        $models     = [];
        $properties = $this->propertiesBuilder->getProperties($schema, $classNames);

        if (isset($interfaceNames[$pointer])) {
            $models[] = new InterfaceModel(
                $interfaceNames[$pointer],
                $pointer,
                ...$properties
            );
        }

        $models[] = new ClassModel(
            $className,
            $pointer,
            $this->getImplements($namedSpec, $interfaceNames),
            ...$properties
        );

        return $models;
    }

    /**
     * @param array<string, string> $interfaceNames
     */
    private function getImplements(NamedSpecification $namedSpec, array $interfaceNames): array
    {
        $schema = $namedSpec->getSpecification();
        assert($schema instanceof Schema);

        $implements = [];

        $pointer = $namedSpec->getJsonPointer();
        if (isset($interfaceNames[$pointer])) {
            $implements[] = $interfaceNames[$pointer];
        }

        if (! empty($schema->allOf)) {
            $implements = [];
            foreach ($schema->allOf as $component) {
                assert($component instanceof Schema);

                $pointer = ModelUtil::getJsonPointer($component);
                if (isset($interfaceNames[$pointer])) {
                    $implements[] = $interfaceNames[$pointer];
                }
            }
        }

        return $implements;
    }

    /**
     * @return list<EnumCase>
     */
    private function getCases(Schema $schema): array
    {
        $cases = [];
        $enum  = array_map(fn (mixed $value): string => (string) $value, $schema->enum);
        /** @var array<string, string> $names */
        $names = $this->caseLabeler->getUnique($enum);
        foreach ($names as $original => $case) {
            $cases[] = new EnumCase($case, $original);
        }

        return $cases;
    }
}

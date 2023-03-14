<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Hydrator;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiHydrator;
use Kynx\Mezzio\OpenApi\Hydrator\Exception\ExtractionException;
use Kynx\Mezzio\OpenApi\Hydrator\Exception\HydrationException;
use Kynx\Mezzio\OpenApi\Hydrator\HydratorInterface;
use Kynx\Mezzio\OpenApi\Hydrator\HydratorUtil;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use TypeError;

use function array_filter;
use function array_map;
use function array_values;
use function assert;
use function ltrim;
use function sprintf;

/**
 * @internal
 *
 * @psalm-internal Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal KynxTest\Mezzio\OpenApiGenerator
 */
final class HydratorGenerator
{
    private const PROPERTY_MAP            = 'PROPERTY_MAP';
    private const EXTRACT_MAP             = 'EXTRACT_MAP';
    private const VALUE_DISCRIMINATORS    = 'VALUE_DISCRIMINATORS';
    private const PROPERTY_DISCRIMINATORS = 'PROPERTY_DISCRIMINATORS';
    private const PROPERTY_HYDRATORS      = 'PROPERTY_HYDRATORS';
    private const PROPERTY_EXTRACTORS     = 'PROPERTY_EXTRACTORS';
    private const ARRAY_PROPERTIES        = 'ARRAY_PROPERTIES';
    private const ENUMS                   = 'ENUMS';
    private const DEFAULTS                = 'DEFAULTS';

    /**
     * @param array<string, class-string<HydratorInterface>> $overrideHydrators
     */
    public function __construct(
        private readonly array $overrideHydrators,
        private readonly Dumper $dumper = new Dumper()
    ) {
    }

    /**
     * @param array<string, string> $hydratorMap
     */
    public function generate(HydratorModel $model, array $hydratorMap): PhpFile
    {
        $file = new PhpFile();
        $file->setStrictTypes();

        $classModel = $model->getModel();

        $namespace = $file->addNamespace($this->getHydratorNamespace($model->getClassName()));
        $namespace->addUse(HydrationException::class)
            ->addUse(ExtractionException::class)
            ->addUse(HydratorInterface::class)
            ->addUse(OpenApiHydrator::class)
            ->addUse(TypeError::class)
            ->addUse($classModel->getClassName());

        $class = $namespace->addClass($this->getClassName($model->getClassName()))
            ->setImplements([HydratorInterface::class])
            ->setFinal();

        $class->addAttribute(OpenApiHydrator::class, [$classModel->getJsonPointer()]);

        $propertyMap            = $this->getPropertyMap($classModel);
        $extractMap             = $this->getExtractMap($classModel);
        $valueDiscriminators    = $this->getValueDiscriminators($classModel, $hydratorMap);
        $propertyDiscriminators = $this->getPropertyDiscriminators($classModel, $hydratorMap);
        $propertyHydrators      = $this->getPropertyHydrators($classModel, $hydratorMap);
        $enums                  = $this->getEnums($classModel);
        $defaults               = $this->getDefaults($classModel);

        $all = $valueDiscriminators + $propertyDiscriminators + $propertyHydrators + $enums;
        if ($all !== [] || $propertyMap !== [] || $extractMap !== []) {
            $namespace->addUse(HydratorUtil::class);
        }
        if ($all !== []) {
            $arrayProperties = $this->getArrayProperties($classModel);
            $this->addArrayPropertiesConstant($class, $arrayProperties);
        }

        $this->addPropertyMapConstant($class, $propertyMap);
        $this->addExtractMapConstant($class, $extractMap);
        $this->addValueDiscriminatorConstant($namespace, $class, $valueDiscriminators);
        $this->addListDiscriminatorConstant($namespace, $class, $propertyDiscriminators);
        $this->addPropertyHydratorConstant($namespace, $class, $propertyHydrators);
        $this->addEnumConstant($namespace, $class, $enums);
        $this->addDefaultsConstant($namespace, $class, $defaults);

        $this->addHydrateMethod(
            $classModel,
            $class,
            $propertyMap,
            $valueDiscriminators,
            $propertyDiscriminators,
            $propertyHydrators,
            $enums,
            $defaults
        );
        $this->addExtractMethod($namespace, $classModel, $class, $propertyHydrators, $enums);

        return $file;
    }

    private function addPropertyMapConstant(ClassType $class, array $propertyMap): void
    {
        if ($propertyMap === []) {
            return;
        }

        $class->addConstant(self::PROPERTY_MAP, $propertyMap)
            ->setPrivate();
    }

    private function addExtractMapConstant(ClassType $class, array $extractMap): void
    {
        $class->addConstant(self::EXTRACT_MAP, $extractMap)
            ->setPrivate();
    }

    private function addArrayPropertiesConstant(ClassType $class, array $arrayProperties): void
    {
        $class->addConstant(self::ARRAY_PROPERTIES, $arrayProperties)
            ->setPrivate();
    }

    /**
     * @param array<string, array{key: string, map: array<string, string>}> $discriminators
     */
    private function addValueDiscriminatorConstant(
        PhpNamespace $namespace,
        ClassType $class,
        array $discriminators
    ): void {
        if ($discriminators === []) {
            return;
        }

        foreach ($discriminators as $property => $discriminator) {
            $map = [];
            foreach ($discriminator['map'] as $key => $className) {
                $namespace->addUse($className);
                $map[$key] = new Literal(GeneratorUtil::getClassName($className) . '::class');
            }
            $discriminators[$property]['map'] = $map;
        }

        $class->addConstant(self::VALUE_DISCRIMINATORS, $discriminators)
            ->setPrivate();
    }

    /**
     * @param array<string, array<string, list<string>>> $discriminators
     */
    private function addListDiscriminatorConstant(
        PhpNamespace $namespace,
        ClassType $class,
        array $discriminators
    ): void {
        if ($discriminators === []) {
            return;
        }

        $values = [];
        foreach ($discriminators as $property => $discriminator) {
            foreach ($discriminator as $classString => $properties) {
                $namespace->addUse($classString);

                $values[$property][] = new Literal(sprintf(
                    "%s::class => %s",
                    GeneratorUtil::getClassName($classString),
                    $this->dumper->dump($properties)
                ));
            }
        }

        $class->addConstant(self::PROPERTY_DISCRIMINATORS, $values)
            ->setPrivate();
    }

    /**
     * @param array<string, string> $hydrators
     */
    private function addPropertyHydratorConstant(PhpNamespace $namespace, ClassType $class, array $hydrators): void
    {
        if ($hydrators === []) {
            return;
        }

        $values = [];
        foreach ($hydrators as $name => $fullyQualified) {
            $namespace->addUse($fullyQualified);
            $values[$name] = new Literal($namespace->simplifyName($fullyQualified) . '::class');
        }

        $class->addConstant(self::PROPERTY_HYDRATORS, $values)
            ->setPrivate();
    }

    /**
     * @param array<string, string> $enums
     */
    private function addEnumConstant(PhpNamespace $namespace, ClassType $class, array $enums): void
    {
        if ($enums === []) {
            return;
        }

        $values = [];
        foreach ($enums as $name => $enum) {
            $namespace->addUse($enum);
            $values[$name] = new Literal(GeneratorUtil::getClassName($enum) . '::class');
        }

        $class->addConstant(self::ENUMS, $values)
            ->setPrivate();
    }

    private function addDefaultsConstant(PhpNamespace $namespace, ClassType $class, array $defaults): void
    {
        if ($defaults === []) {
            return;
        }

        $namespace->addUseFunction('array_merge');
        $class->addConstant(self::DEFAULTS, $defaults)
            ->setPrivate();
    }

    private function addHydrateMethod(
        ClassModel $model,
        ClassType $class,
        array $propertyMap,
        array $valueDiscriminators,
        array $propertyDiscriminators,
        array $propertyHydrators,
        array $enums,
        array $defaults
    ): void {
        $method = $class->addMethod('hydrate')
            ->setStatic()
            ->setReturnType($model->getClassName());
        $method->addParameter('data')
            ->setType('array');

        // phpcs:disable Generic.Files.LineLength.TooLong
        if ($valueDiscriminators !== []) {
            $method->addBody('$data = HydratorUtil::hydrateDiscriminatorValues($data, self::ARRAY_PROPERTIES, self::VALUE_DISCRIMINATORS);');
        }
        if ($propertyDiscriminators !== []) {
            $method->addBody('$data = HydratorUtil::hydrateDiscriminatorList($data, self::ARRAY_PROPERTIES, self::PROPERTY_DISCRIMINATORS);');
        }
        if ($propertyHydrators !== []) {
            $method->addBody('$data = HydratorUtil::hydrateProperties($data, self::ARRAY_PROPERTIES, self::PROPERTY_HYDRATORS);');
        }
        if ($enums !== []) {
            $method->addBody('$data = HydratorUtil::hydrateEnums($data, self::ARRAY_PROPERTIES, self::ENUMS);');
        }
        if ($defaults !== []) {
            $method->addBody('$data = array_merge(self::DEFAULTS, $data);');
        }
        if ($propertyMap !== []) {
            $method->addBody('$data = HydratorUtil::getMappedProperties($data, self::PROPERTY_MAP);');
        }
        // phpcs:enable

        $className = GeneratorUtil::getClassName($model->getClassName());
        $method->addBody(<<<EOB
        try {
            return new $className(...\$data);
        } catch (TypeError \$error) {
            throw HydrationException::fromThrowable($className::class, \$error);
        }
        EOB);
    }

    private function addExtractMethod(
        PhpNamespace $namespace,
        ClassModel $model,
        ClassType $class,
        array $propertyHydrators,
        array $enums
    ): void {
        $className = $namespace->simplifyName($model->getClassName());
        $method    = $class->addMethod('extract')
            ->setStatic()
            ->setReturnType('bool|array|float|int|string|null');
        $method->addParameter('object')
            ->setType('mixed');

        $method->addBody(<<<FOO
            if (! \$object instanceof $className) {
                throw ExtractionException::invalidObject(\$object, $className::class);
            }
            
            FOO
        );
        $method->addBody('$data = HydratorUtil::extractData($object, self::EXTRACT_MAP);');

        // phpcs:disable Generic.Files.LineLength.TooLong
        if ($enums !== []) {
            $method->addBody('$data = HydratorUtil::extractEnums($data, self::ARRAY_PROPERTIES, self::ENUMS);');
        }
        if ($propertyHydrators !== []) {
            $method->addBody('$data = HydratorUtil::extractProperties($data, self::ARRAY_PROPERTIES, self::PROPERTY_HYDRATORS);');
        }
        // phpcs:enable

        $method->addBody('return $data;');
    }

    /**
     * @param array<string, string> $hydratorMap
     * @return array<string, array{key: string, map: array<string, string>}>
     */
    private function getValueDiscriminators(ClassModel $model, array $hydratorMap): array
    {
        $discriminators = [];
        foreach ($model->getProperties() as $property) {
            if (! $property instanceof UnionProperty) {
                continue;
            }
            $discriminator = $property->getDiscriminator();
            if (! $discriminator instanceof PropertyValue) {
                continue;
            }

            $valueMap = array_map(
                fn (string $classString): string => $this->getFullQualified($hydratorMap[$classString]),
                $discriminator->getValueMap()
            );

            $discriminators[$property->getOriginalName()] = [
                'key' => $discriminator->getKey(),
                'map' => $valueMap,
            ];
        }

        return $discriminators;
    }

    /**
     * @param array<string, string> $hydratorMap
     * @return array<string, array<string, list<string>>>
     */
    private function getPropertyDiscriminators(ClassModel $model, array $hydratorMap): array
    {
        $discriminators = [];
        foreach ($model->getProperties() as $property) {
            if (! $property instanceof UnionProperty) {
                continue;
            }
            $discriminator = $property->getDiscriminator();
            if (! $discriminator instanceof PropertyList) {
                continue;
            }

            $discriminators[$property->getOriginalName()] = DiscriminatorUtil::getListDiscriminator(
                $property,
                $hydratorMap
            );
        }

        return $discriminators;
    }

    /**
     * @param array<string, string> $hydratorMap
     * @return array<string, string>
     */
    private function getPropertyHydrators(ClassModel $model, array $hydratorMap): array
    {
        $hydrators = [];
        foreach ($this->getClassStringProperties($model) as $property) {
            $name = $property->getOriginalName();
            $type = $property->getType();
            assert($type instanceof ClassString);

            if (! $type->isEnum()) {
                $classString      = $type->getClassString();
                $hydrators[$name] = $this->overrideHydrators[$classString]
                    ?? $this->getFullQualified($hydratorMap[$classString]);
            }
        }

        return $hydrators;
    }

    /**
     * @return list<string>
     */
    private function getArrayProperties(ClassModel $model): array
    {
        $filtered = array_filter(
            $model->getProperties(),
            fn (PropertyInterface $property): bool => $property instanceof ArrayProperty
        );

        return array_values(array_map(
            fn (PropertyInterface $property): string => $property->getOriginalName(),
            $filtered
        ));
    }

    /**
     * @return array<string, string>
     */
    private function getEnums(ClassModel $model): array
    {
        $enums = [];
        foreach ($this->getClassStringProperties($model) as $property) {
            $name = $property->getOriginalName();
            $type = $property->getType();
            assert($type instanceof ClassString);

            if ($type->isEnum()) {
                $enums[$name] = $type->getClassString();
            }
        }

        return $enums;
    }

    private function getDefaults(ClassModel $model): array
    {
        $defaults = [];
        foreach ($model->getProperties() as $property) {
            $name     = $property->getOriginalName();
            $metadata = $property->getMetadata();
            if ($metadata->getDefault() !== null) {
                /** @psalm-suppress MixedAssignment */
                $defaults[$name] = $metadata->getDefault();
            } elseif (! $metadata->isRequired() || $metadata->isReadOnly()) {
                $defaults[$name] = null;
            }
        }

        return $defaults;
    }

    private function getPropertyMap(ClassModel $model): array
    {
        $map    = [];
        $hasMap = false;
        foreach ($model->getProperties() as $property) {
            $orig       = $property->getOriginalName();
            $new        = ltrim($property->getName(), '$');
            $map[$orig] = $new;
            $hasMap     = $hasMap || $orig !== $new;
        }

        return $hasMap ? $map : [];
    }

    private function getExtractMap(ClassModel $model): array
    {
        $map = [];
        foreach ($model->getProperties() as $property) {
            $map[$property->getOriginalName()] = GeneratorUtil::getMethodName($property);
        }

        return $map;
    }

    /**
     * @return list<ArrayProperty|SimpleProperty>
     */
    private function getClassStringProperties(ClassModel $model): array
    {
        $properties = [];
        foreach ($model->getProperties() as $property) {
            if ($property instanceof SimpleProperty || $property instanceof ArrayProperty) {
                if ($property->getType() instanceof ClassString) {
                    $properties[] = $property;
                }
            }
        }

        return $properties;
    }

    private function getHydratorNamespace(string $classString): string
    {
        return GeneratorUtil::getNamespace($classString);
    }

    private function getClassName(string $classString): string
    {
        return GeneratorUtil::getClassName($classString);
    }

    private function getFullQualified(string $classString): string
    {
        return $this->getHydratorNamespace($classString) . '\\' . $this->getClassName($classString);
    }
}

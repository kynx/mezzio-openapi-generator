<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

use function sprintf;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Generator\ClassGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 * @psalm-import-type UsesArray from AbstractGenerator
 */
final class ClassGenerator extends AbstractGenerator
{
    public function addClass(PhpNamespace $namespace, ClassModel $model): ClassType
    {
        $class = $namespace->addClass($this->getClassLikeName($model));
        $class->setFinal();

        $aliases = $this->getPropertyUses($model->getProperties());
        foreach ($aliases as $use => $alias) {
            if ($use !== $alias) {
                $namespace->addUse($use, $alias);
            }
        }

        $constructor = $class->addMethod('__construct');
        foreach ($this->getOrderedParameters($model) as $property) {
            $param = $constructor->addPromotedParameter($this->normalizePropertyName($property))
                ->setType($this->getType($property))
                ->setPrivate()
                ->setReadOnly();

            $metadata = $property->getMetadata();
            if ($metadata->getDefault() !== null) {
                $param->setDefaultValue($metadata->getDefault());
            } elseif ($metadata->isNullable() || ! $metadata->isRequired()) {
                $param->setDefaultValue(null);
            }

            if ($property->getDocBlockType() !== null) {
                $constructor->addComment('@param ' . $property->getDocBlockType() . ' ' . $property->getName());
            }
        }

        $this->addMethods($class, $model);

        return $class;
    }

    /**
     * @param UsesArray $aliases
     */
    private function addMethods(ClassType $type, ClassModel $model): void
    {
        foreach ($model->getProperties() as $property) {
            $method = $type->addMethod($this->getMethodName($property));
            $method->setPublic()
                ->setReturnType($this->getType($property))
                ->setBody(sprintf('return $this->%s;', $this->normalizePropertyName($property)))
                ->setComment($this->getDocBlock($property));
        }
    }

    private function getDocBlock(PropertyInterface $property): string|null
    {
        $metadata = $property->getMetadata();

        $docBlock = '';
        if ($metadata->getTitle()) {
            $docBlock .= $metadata->getTitle() . "\n\n";
        }
        if ($metadata->getDescription()) {
            $docBlock .= $metadata->getDescription() . "\n\n";
        }
        if ($metadata->isDeprecated()) {
            $docBlock .= '@deprecated';
        }

        $type = $property->getDocBlockType();
        if ($type !== null) {
            $docBlock .= '@return ' . $type;
        }

        return $docBlock ?: null;
    }
}

<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
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
        $class->setFinal()
            ->setImplements($model->getImplements());

        $aliases = $this->getPropertyUses($model->getProperties());
        foreach ($aliases as $use => $alias) {
            if ($use !== $alias) {
                $namespace->addUse($use, $alias);
            }
        }

        $constructor = $class->addMethod('__construct');
        foreach ($model->getProperties() as $property) {
            $constructor->addPromotedParameter(GeneratorUtil::normalizePropertyName($property))
                ->setType($this->getType($property))
                ->setPrivate()
                ->setReadOnly();

            if ($property->getDocBlockType() !== null) {
                $constructor->addComment('@param ' . $property->getDocBlockType() . ' ' . $property->getName());
            }
        }

        $this->addMethods($class, $model);

        return $class;
    }

    private function addMethods(ClassType $type, ClassModel $model): void
    {
        foreach ($model->getProperties() as $property) {
            $method = $type->addMethod(GeneratorUtil::getMethodName($property));
            $method->setPublic()
                ->setReturnType($this->getType($property))
                ->setBody(sprintf('return $this->%s;', GeneratorUtil::normalizePropertyName($property)))
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

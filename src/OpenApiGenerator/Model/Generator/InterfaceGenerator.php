<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\Model\InterfaceModel;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\PhpNamespace;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Generator\InterfaceGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 * @psalm-import-type UsesArray from AbstractGenerator
 */
final class InterfaceGenerator extends AbstractGenerator
{
    public function addInterface(PhpNamespace $namespace, InterfaceModel $model): InterfaceType
    {
        $interface = $namespace->addInterface($this->getClassLikeName($model));

        $aliases = $this->getPropertyUses($model->getProperties());
        foreach ($aliases as $use => $alias) {
            if ($use !== $alias) {
                $namespace->addUse($use, $alias);
            }
        }

        $this->addMethods($interface, $model, $aliases);

        return $interface;
    }


    /**
     * @param UsesArray $aliases
     */
    private function addMethods(InterfaceType $type, InterfaceModel $model, array $aliases): void
    {
        foreach ($this->getOrderedParameters($model) as $property) {
            $method = $type->addMethod($this->getMethodName($property));
            $method->setPublic()
                ->setReturnType($this->getType($property, $aliases));
        }
    }
}

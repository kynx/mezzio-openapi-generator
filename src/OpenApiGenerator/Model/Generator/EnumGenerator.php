<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Generator;

use Kynx\Mezzio\OpenApiGenerator\Model\EnumModel;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpNamespace;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Generator\EnumGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class EnumGenerator extends AbstractGenerator
{
    public function addEnum(PhpNamespace $namespace, EnumModel $model): EnumType
    {
        $enum = $namespace->addEnum($this->getClassLikeName($model));
        $enum->setType('string');

        foreach ($model->getCases() as $case) {
            $enum->addCase($case->getName(), $case->getValue());
        }

        return $enum;
    }
}

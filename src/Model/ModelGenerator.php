<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiModel;
use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\ClassGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\EnumGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\InterfaceGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Nette\PhpGenerator\PhpFile;

use function assert;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ModelGeneratorTest
 */
final class ModelGenerator
{
    public function __construct(
        private readonly ClassGenerator $classGenerator = new ClassGenerator(),
        private readonly EnumGenerator $enumGenerator = new EnumGenerator(),
        private readonly InterfaceGenerator $interfaceGenerator = new InterfaceGenerator()
    ) {
    }

    public function generate(AbstractClassLikeModel|EnumModel $modelClass): PhpFile
    {
        $file = new PhpFile();
        $file->setStrictTypes();

        $namespace = $file->addNamespace(GeneratorUtil::getNamespace($modelClass->getClassName()));

        if ($modelClass instanceof EnumModel) {
            $added = $this->enumGenerator->addEnum($namespace, $modelClass);
        } elseif ($modelClass instanceof InterfaceModel) {
            $added = $this->interfaceGenerator->addInterface($namespace, $modelClass);
        } else {
            assert($modelClass instanceof ClassModel || $modelClass instanceof OperationModel);
            $added = $this->classGenerator->addClass($namespace, $modelClass);
        }

        if ($modelClass instanceof OperationModel) {
            $namespace->addUse(OpenApiOperation::class);
            $added->addAttribute(OpenApiOperation::class, [$modelClass->getJsonPointer()]);
        } else {
            $namespace->addUse(OpenApiModel::class);
            $added->addAttribute(OpenApiModel::class, [$modelClass->getJsonPointer()]);
        }

        return $file;
    }
}

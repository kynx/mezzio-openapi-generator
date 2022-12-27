<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApi\OpenApiSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\ClassGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\EnumGenerator;
use Kynx\Mezzio\OpenApiGenerator\Model\Generator\InterfaceGenerator;
use Nette\PhpGenerator\PhpFile;

use function array_slice;
use function explode;
use function implode;

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

    public function generate(ClassModel|EnumModel|InterfaceModel $modelClass): PhpFile
    {
        $file = new PhpFile();
        $file->setStrictTypes();

        $namespace = $file->addNamespace($this->getNamespace($modelClass));

        if ($modelClass instanceof ClassModel) {
            $added = $this->classGenerator->addClass($namespace, $modelClass);
        } elseif ($modelClass instanceof EnumModel) {
            $added = $this->enumGenerator->addEnum($namespace, $modelClass);
        } else {
            $added = $this->interfaceGenerator->addInterface($namespace, $modelClass);
        }

        $namespace->addUse(OpenApiSchema::class);
        $added->addAttribute(OpenApiSchema::class, [$modelClass->getJsonPointer()]);

        return $file;
    }

    private function getNamespace(ClassModel|EnumModel|InterfaceModel $modelClass): string
    {
        return implode('\\', array_slice(explode('\\', $modelClass->getClassName()), 0, -1));
    }
}

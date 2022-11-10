<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Code\Normalizer\UniqueVariableLabeler;
use Kynx\Code\Normalizer\WordCase;
use Kynx\Mezzio\OpenApi\OpenApiSchema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

final class ModelGenerator
{
    public function __construct(private UniqueVariableLabeler $variableLabeler)
    {
    }

    /**
     * @return list<PhpFile>
     */
    public function generate(ModelCollection $collection): array
    {
        $files = [];
        foreach ($collection as $modelClass) {
            $files[] = $this->generateModel($modelClass);
        }

        return $files;
    }

    private function generateModel(ClassModel $modelClass): PhpFile
    {
        $file = new PhpFile();
        $file->setStrictTypes(true);

        $schema = $modelClass->getSchema();
        if (Util::isEnum($schema)) {
            $added = $this->addEnumClass($file, $modelClass);
        } else {
            $added = $this->addModelClass($file, $modelClass);
        }
        $added->addAttribute(OpenApiSchema::class, [$modelClass->getJsonPointer()]);

        return $file;
    }

    private function addModelClass(PhpFile $file, ClassModel $model): ClassType
    {
        $class = $file->addClass($model->getClassName());
        $constructor = $class->addMethod('__construct');
    }

    private function addEnumClass(PhpFile $file, ClassModel $model): EnumType
    {
        $schema = $model->getSchema();
        assert(! empty($schema->enum));
        assert(in_array($schema->type, ['integer', 'string'], true));

        $enum = $file->addEnum($model->getClassName());
        $enum->setType($schema->type === 'integer' ? 'int' : 'string');

        foreach ($schema->enum as $value) {

        }

    }

    private function getProperties(ClassModel $modelClass): array
    {
        $properties = [];
        foreach ($modelClass->getSchema()->properties as $property) {

        }
    }
}
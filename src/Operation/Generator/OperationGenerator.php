<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation\Generator;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

use function array_filter;
use function array_merge;
use function implode;
use function ucfirst;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\Generator\OperationGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OperationGenerator
{
    public function generate(OperationModel $operation): PhpFile
    {
        $file = new PhpFile();
        $file->setStrictTypes();

        $namespace = $file->addNamespace(GeneratorUtil::getNamespace($operation->getClassName()));
        $namespace->addUse(OpenApiOperation::class);

        $class = $namespace->addClass(GeneratorUtil::getClassName($operation->getClassName()))
            ->setFinal();

        $class->addAttribute(OpenApiOperation::class, [$operation->getJsonPointer()]);

        $constructor = $class->addMethod('__construct');
        foreach ($this->getParameters($operation) as $name => $parameter) {
            $className = $parameter->getModel()->getClassName();
            $namespace->addUse($className);

            $this->addConstructorParam($constructor, $name, $className);
            $this->addGetter($class, $name, $className);
        }

        $this->addRequestBody($namespace, $class, $constructor, $operation);

        return $file;
    }

    private function addConstructorParam(Method $constructor, string $name, string $type): void
    {
        $constructor->addPromotedParameter($name)
            ->setPrivate()
            ->setReadOnly()
            ->setType($type);
    }

    private function addGetter(ClassType $class, string $name, string $returnType): void
    {
        $methodName = 'get' . ucfirst($name);
        $class->addMethod($methodName)
            ->setPublic()
            ->setReturnType($returnType)
            ->setBody("return \$this->$name;");
    }

    private function addRequestBody(
        PhpNamespace $namespace,
        ClassType $class,
        Method $constructor,
        OperationModel $operation
    ): void {
        if ($operation->getRequestBodies() === []) {
            return;
        }

        $types = [];
        foreach ($operation->getRequestBodies() as $requestBody) {
            $types = array_merge($types, $requestBody->getType()->getTypes());
        }

        $typeStrings = [];
        foreach ($types as $type) {
            if ($type instanceof PropertyType) {
                $typeStrings[] = $type->toPhpType();
            } else {
                $namespace->addUse($type->getClassString());
                $typeStrings[] = $type->getClassString();
            }
        }

        $type = implode('|', $typeStrings);
        $this->addConstructorParam($constructor, 'requestBody', $type);
        $this->addGetter($class, 'requestBody', $type);
    }

    /**
     * @return array<string, CookieOrHeaderParams|PathOrQueryParams>
     */
    private function getParameters(OperationModel $operation): array
    {
        return array_filter([
            'pathParams'   => $operation->getPathParams(),
            'queryParams'  => $operation->getQueryParams(),
            'headerParams' => $operation->getHeaderParams(),
            'cookieParams' => $operation->getCookieParams(),
        ]);
    }
}

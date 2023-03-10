<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation\Generator;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequest;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

use function array_filter;
use function ucfirst;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\Generator\OperationGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RequestGenerator
{
    public function generate(OperationModel $operation): PhpFile
    {
        $file = new PhpFile();
        $file->setStrictTypes();

        $className = $operation->getRequestClassName();
        $namespace = $file->addNamespace(GeneratorUtil::getNamespace($className));
        $namespace->addUse(OpenApiRequest::class);

        $class = $namespace->addClass(GeneratorUtil::getClassName($className))
            ->setFinal();

        $class->addAttribute(OpenApiRequest::class, [$operation->getJsonPointer()]);

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

        foreach ($operation->getRequestBodyUses() as $use) {
            $namespace->addUse($use);
        }
        $type = $operation->getRequestBodyType();

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

<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation\Generator;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequestParserFactory;
use Kynx\Mezzio\OpenApi\Operation\RequestBody\MediaTypeMatcher;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Nette\PhpGenerator\PhpFile;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Operation\Generator\RequestParserFactoryGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class RequestParserFactoryGenerator
{
    public function generate(OperationModel $operation): PhpFile
    {
        $file = new PhpFile();
        $file->setStrictTypes();

        $parserClass  = GeneratorUtil::getNamespace($operation->getClassName()) . '\\RequestParser';
        $factoryClass = $parserClass . 'Factory';
        $namespace    = $file->addNamespace(GeneratorUtil::getNamespace($operation->getClassName()));
        $namespace->addUse(OpenApiRequestParserFactory::class)
            ->addUse(ContainerInterface::class)
            ->addUse(MediaTypeMatcher::class)
            ->addUse($parserClass);

        $class = $namespace->addClass(GeneratorUtil::getClassName($factoryClass))
            ->setFinal()
            ->addAttribute(OpenApiRequestParserFactory::class, [$operation->getJsonPointer()]);

        $invoke = $class->addMethod('__invoke');
        $invoke->setPublic()
            ->setReturnType($parserClass);
        $invoke->addParameter('container')
            ->setType(ContainerInterface::class);

        $parserName = GeneratorUtil::getClassName($parserClass);
        $invoke->addBody("return new $parserName(\$container->get(MediaTypeMatcher::class));");

        return $file;
    }
}

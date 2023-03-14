<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiHandlerFactory;
use Kynx\Mezzio\OpenApi\Serializer\SerializerInterface;
use Nette\PhpGenerator\PhpFile;
use Psr\Container\ContainerInterface;

use function current;

final class HandlerFactoryGenerator
{
    public function generate(HandlerModel $handler): PhpFile
    {
        $pointer   = $handler->getJsonPointer();
        $operation = $handler->getOperation();

        $file = new PhpFile();
        $file->setStrictTypes();

        $class = $file->addClass($handler->getFactoryClassName())
            ->setFinal()
            ->addAttribute(OpenApiHandlerFactory::class, [$pointer]);

        $namespace = current($file->getNamespaces());
        $namespace->addUse(ContainerInterface::class)
            ->addUse(OpenApiHandlerFactory::class)
            ->addUse($operation->getResponseFactoryClassName());

        $method = $class->addMethod('__invoke')
            ->setReturnType($handler->getClassName());
        $method->addParameter('container')
            ->setType(ContainerInterface::class);

        $className       = $namespace->simplifyName($handler->getClassName());
        $responseFactory = $namespace->simplifyName($operation->getResponseFactoryClassName());

        if ($operation->responsesRequireSerialization()) {
            $namespace->addUse(SerializerInterface::class);
            $method->addBody(<<<HANDLER_FACTORY
            return new $className(new $responseFactory(\$container->get(SerializerInterface::class)));
            HANDLER_FACTORY
            );
        } else {
            $method->addBody(<<<HANDLER_FACTORY
            return new $className(new $responseFactory());
            HANDLER_FACTORY
            );
        }


        return $file;
    }
}

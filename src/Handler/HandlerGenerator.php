<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiHandler;
use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequest;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function current;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Handler\HandlerGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class HandlerGenerator
{
    public function generate(HandlerModel $handler): PhpFile
    {
        $pointer   = $handler->getJsonPointer();
        $className = $handler->getClassName();

        $file = new PhpFile();
        $file->setStrictTypes();

        $class = $file->addClass($className)
            ->setFinal()
            ->addImplement(RequestHandlerInterface::class)
            ->addAttribute(OpenApiHandler::class, [$pointer]);

        $namespace = current($file->getNamespaces());
        assert($namespace instanceof PhpNamespace);

        $namespace->addUse(OpenApiHandler::class)
            ->addUse(RequestHandlerInterface::class)
            ->addUse(ResponseInterface::class)
            ->addUse(ServerRequestInterface::class)
            ->addUseFunction('assert');

        $operation = $handler->getOperation();
        $this->addConstructor($namespace, $class, $operation);

        $handle = $class->addMethod('handle')
            ->setPublic()
            ->setReturnType(ResponseInterface::class);
        $handle->addParameter('request')
            ->setType(ServerRequestInterface::class);

        if ($operation->hasParameters()) {
            $requestClass = $operation->getRequestClassName();
            $namespace->addUse(OpenApiRequest::class)
                ->addUse($requestClass);
            $handle->addBody('$operation = $request->getAttribute(?);', [
                new Literal($namespace->simplifyName(OpenApiRequest::class) . '::class'),
            ]);
            $handle->addBody('assert($operation instanceof ?);', [
                new Literal($namespace->simplifyName($requestClass)),
            ]);
            $handle->addBody('');
        }

        $handle->addBody('// @todo Add your handler logic...');

        return $file;
    }

    public function addConstructor(PhpNamespace $namespace, ClassType $class, OperationModel $operation): void
    {
        $responseFactory = $operation->getResponseFactoryClassName();
        $namespace->addUse($responseFactory);

        $constructor = $class->addMethod('__construct')
            ->setPublic();
        $parameter   = $constructor->addPromotedParameter('responseFactory')
            ->setPrivate()
            ->setReadOnly()
            ->setType($responseFactory);

        if (! $operation->responsesRequireSerialization()) {
            $parameter->setDefaultValue(new Literal('new ' . $namespace->simplifyName($responseFactory) . '()'));
        }
    }
}

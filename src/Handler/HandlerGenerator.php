<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Handler;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiHandler;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

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

        $namespace->addUse(OpenApiHandler::class)
            ->addUse(RequestHandlerInterface::class)
            ->addUse(ResponseInterface::class)
            ->addUse(ServerRequestInterface::class)
            ->addUseFunction('assert');

        $handle = $class->addMethod('handle')
            ->setPublic()
            ->setReturnType(ResponseInterface::class);
        $handle->addParameter('request')
            ->setType(ServerRequestInterface::class);

        $operation = $handler->getOperation();
        if ($operation !== null) {
            $operationClass = $operation->getClassName();
            $namespace->addUse($operationClass);
            $handle->addBody('$operation = $request->getAttribute(?);', [
                new Literal($namespace->simplifyName($operationClass) . '::class'),
            ]);
            $handle->addBody('assert($operation instanceof ?);', [
                new Literal($namespace->simplifyName($operationClass)),
            ]);
            $handle->addBody('');
        }

        $handle->addBody('// @todo Add your handler logic...');

        return $file;
    }
}

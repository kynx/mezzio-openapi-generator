<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiConfigProvider;
use Kynx\Mezzio\OpenApi\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Mezzio\Application;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

use function array_slice;
use function current;
use function explode;
use function implode;
use function ksort;
use function sort;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\ConfigProvider\ConfigProviderGeneratorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ConfigProviderGenerator
{
    public function __construct(private readonly string $className)
    {
    }

    public function generate(
        OperationCollection $operations,
        HandlerCollection $handlers,
        string $routeDelegatorClassName
    ): PhpFile {
        $file = new PhpFile();
        $file->setStrictTypes();

        $class = $file->addClass($this->className)
            ->setFinal();

        $namespace = current($file->getNamespaces());
        $namespace->addUse(InvokableFactory::class)
            ->addUse(OpenApiConfigProvider::class)
            ->addUse(Application::class)
            ->addUse($routeDelegatorClassName);

        $class->addAttribute(OpenApiConfigProvider::class);

        $invoke = $class->addMethod('__invoke')
            ->setPublic()
            ->setReturnType('array');

        $invoke->addBody(<<<INVOKE_BODY
        return [
            ? => \$this->getOpenApiConfig(),
            'dependencies'   => \$this->getDependencyConfig(),
        ];
        INVOKE_BODY, [ConfigProvider::CONFIG_KEY]);

        $this->addGetOpenApiConfig($namespace, $class, $operations);
        $this->addGetDependencyConfig($namespace, $class, $handlers, $routeDelegatorClassName);

        return $file;
    }

    private function addGetOpenApiConfig(
        PhpNamespace $namespace,
        ClassType $class,
        OperationCollection $operations
    ): void {
        $factories = [];
        foreach ($operations as $operation) {
            if (! $operation->hasParameters()) {
                continue;
            }
            $factoryName = $operation->getOperationFactoryClassName();
            $alias       = $this->getAlias($namespace->simplifyName($factoryName));
            $namespace->addUse($factoryName, $alias);
            $factories[$operation->getJsonPointer()] = new Literal($alias . '::class');
        }
        ksort($factories);

        $openApiConfig = [
            ConfigProvider::OPERATION_FACTORIES_KEY => $factories,
        ];

        $class->addMethod('getOpenApiConfig')
            ->setPrivate()
            ->setReturnType('array')
            ->addBody('return ?;', [$openApiConfig]);
    }

    private function addGetDependencyConfig(
        PhpNamespace $namespace,
        ClassType $class,
        HandlerCollection $handlers,
        string $routeDelegatorClassName
    ): void {
        $classNames = [];
        foreach ($handlers as $handler) {
            $className = $handler->getClassName();
            $alias     = $this->getAlias($namespace->simplifyName($className));
            $namespace->addUse($className, $alias);
            $classNames[] = $alias;
        }
        sort($classNames);

        $delegators = [
            new Literal(
                $namespace->simplifyName(Application::class) . '::class => '
                . $namespace->simplifyName($routeDelegatorClassName) . '::class'
            ),
        ];

        $invokableFactory = $namespace->simplifyName(InvokableFactory::class) . '::class';
        $factories        = [];
        foreach ($classNames as $dependency) {
            $factories[] = new Literal($dependency . '::class => ' . $invokableFactory);
        }
        $dependencies = [
            'delegators' => $delegators,
            'factories'  => $factories,
        ];

        $class->addMethod('getDependencyConfig')
            ->setPrivate()
            ->setReturnType('array')
            ->addBody('return ?;', [$dependencies]);
    }

    private function getAlias(string $shortName): string
    {
        return implode('', array_slice(explode('\\', $shortName), 1));
    }
}

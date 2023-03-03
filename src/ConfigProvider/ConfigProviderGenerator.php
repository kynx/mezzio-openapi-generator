<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\ConfigProvider;

use Kynx\Mezzio\OpenApi\Attribute\OpenApiConfigProvider;
use Kynx\Mezzio\OpenApi\ConfigProvider;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
use Kynx\Mezzio\OpenApiGenerator\Handler\HandlerCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Mezzio\Application;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

use function asort;
use function current;
use function ksort;
use function ltrim;

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
    public function __construct(private readonly string $openApiFile, private readonly string $className)
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
            $factoryName = $operation->getRequestFactoryClassName();
            $alias       = GeneratorUtil::getAlias($namespace->simplifyName($factoryName));
            $namespace->addUse($factoryName, $alias);
            $factories[$operation->getJsonPointer()] = new Literal($alias . '::class');
        }
        ksort($factories);

        $openApiFile   = '/' . ltrim($this->openApiFile, './');
        $openApiConfig = [
            ConfigProvider::SCHEMA_KEY              => new Literal('getcwd() . ?', [$openApiFile]),
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
            $alias     = GeneratorUtil::getAlias($namespace->simplifyName($className));
            $namespace->addUse($className, $alias);
            if ($handler->getOperation()->responsesRequireSerialization()) {
                $factory = GeneratorUtil::getAlias($namespace->simplifyName($handler->getFactoryClassName()));
                $namespace->addUse($handler->getFactoryClassName(), $factory);
            } else {
                $factory = $namespace->simplifyName(InvokableFactory::class);
            }
            $classNames[$alias] = $factory;
        }
        asort($classNames);

        $delegators = [
            new Literal(
                $namespace->simplifyName(Application::class) . '::class => ['
                . $namespace->simplifyName($routeDelegatorClassName) . '::class]'
            ),
        ];

        $factories = [];
        foreach ($classNames as $alias => $factory) {
            $factories[] = new Literal($alias . '::class => ' . $factory . '::class');
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
}

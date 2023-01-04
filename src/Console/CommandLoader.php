<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Console;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

use function array_keys;
use function class_exists;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Console\CommandLoaderTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Console
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Console
 */
final class CommandLoader implements CommandLoaderInterface
{
    /**
     * @param array<string, class-string<Command>> $commandMap
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $commandMap
    ) {
    }

    public function get(string $name): Command
    {
        if (! $this->has($name)) {
            throw new RuntimeException("Command '$name' does not exist");
        }

        $class = $this->commandMap[$name];

        if ($this->container->has($class)) {
            $instance = $this->container->get($class);
        } elseif (class_exists($class)) {
            /** @psalm-suppress UnsafeInstantiation $instance */
            $instance = new $class();
        } else {
            throw new RuntimeException("Command class '$class' does not exist");
        }

        return $instance;
    }

    public function has(string $name): bool
    {
        return isset($this->commandMap[$name]);
    }

    public function getNames(): array
    {
        return array_keys($this->commandMap);
    }
}

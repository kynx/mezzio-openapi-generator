<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Console;

use Kynx\Mezzio\OpenApiGenerator\Console\CommandLoader;
use KynxTest\Mezzio\OpenApiGenerator\Console\Asset\ContainerCommand;
use KynxTest\Mezzio\OpenApiGenerator\Console\Asset\OtherCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Console\CommandLoader
 */
final class CommandLoaderTest extends TestCase
{
    private ContainerInterface $container;
    private CommandLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var array<string, class-string<Command>> $commands */
        $commands        = [
            'test'        => ContainerCommand::class,
            'other'       => OtherCommand::class,
            'nonexistent' => __NAMESPACE__ . '\\NonExistent',
        ];
        $this->container = $this->createStub(ContainerInterface::class);
        $this->container->method('get')
            ->willReturnMap([
                [ContainerCommand::class, new ContainerCommand()],
            ]);
        $this->container->method('has')
            ->willReturnCallback(fn (string $name): bool => $name === ContainerCommand::class);

        $this->loader = new CommandLoader($this->container, $commands);
    }

    public function testGetInvalidCommandThrowsException(): void
    {
        $expected = "Command 'foo' does not exist";

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage($expected);
        $this->loader->get('foo');
    }

    public function testGetReturnsCommandFromContainer(): void
    {
        $actual = $this->loader->get('test');
        self::assertInstanceOf(ContainerCommand::class, $actual);
    }

    public function testGetInstantiatesCommand(): void
    {
        $actual = $this->loader->get('other');
        self::assertInstanceOf(OtherCommand::class, $actual);
    }

    public function testGetNonExistentThrowsException(): void
    {
        $expected = "Command class '" . __NAMESPACE__ . "\\NonExistent' does not exist";

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage($expected);
        $this->loader->get('nonexistent');
    }

    public function testHasReturnsTrue(): void
    {
        $actual = $this->loader->has('test');
        self::assertTrue($actual);
    }

    public function testHasReturnsFalse(): void
    {
        $actual = $this->loader->has('nothing');
        self::assertFalse($actual);
    }

    public function testGetNamesReturnsCommandNames(): void
    {
        $expected = [
            'test',
            'other',
            'nonexistent',
        ];
        $actual   = $this->loader->getNames();
        self::assertSame($expected, $actual);
    }
}

<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApiGenerator\Route\ParameterModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\ParameterModel
 */
final class ParameterModelTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $name       = 'foo';
        $hasContent = true;
        $type       = 'string';
        $style      = 'matrix';
        $explode    = true;

        $parameter = new ParameterModel($name, $hasContent, $type, $style, $explode);
        self::assertSame($name, $parameter->getName());
        self::assertSame($hasContent, $parameter->hasContent());
        self::assertSame($type, $parameter->getType());
        self::assertSame($style, $parameter->getStyle());
        self::assertSame($explode, $parameter->getExplode());
    }
}

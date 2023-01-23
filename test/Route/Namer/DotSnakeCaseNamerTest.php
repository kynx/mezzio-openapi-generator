<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route\Namer;

use Kynx\Mezzio\OpenApiGenerator\Route\Namer\DotSnakeCaseNamer;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\Namer\DotSnakeCaseNamer
 */
final class DotSnakeCaseNamerTest extends TestCase
{
    private const PREFIX = 'api';

    private DotSnakeCaseNamer $namer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->namer = new DotSnakeCaseNamer(self::PREFIX);
    }

    public function testGetNameUsesRouteAndMethod(): void
    {
        $expected = self::PREFIX . '.find.pet.by_status.get';
        $route    = new RouteModel('/paths/pet~1byStatus/get', '/find/pet/byStatus', 'get', [], []);
        $actual   = $this->namer->getName($route);
        self::assertSame($expected, $actual);
    }

    public function testGetNameParsesMultibyteRoute(): void
    {
        $expected = self::PREFIX . '.find.pet.Яfoo.get';
        $route    = new RouteModel('/find/pet~1Яfoo/get', '/find/pet/Яfoo', 'get', [], []);
        $actual   = $this->namer->getName($route);
        self::assertSame($expected, $actual);
    }

    public function testGetNameStripsParameterMarkersFromRoute(): void
    {
        $expected = self::PREFIX . '.pet.pet_id.get';
        $route    = new RouteModel('/pet~1{petId}', '/pet/{petId}', 'get', [], []);
        $actual   = $this->namer->getName($route);
        self::assertSame($expected, $actual);
    }
}

<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route\Namer;

use cebe\openapi\spec\Operation;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\DotSnakeCaseNamer;
use Kynx\Mezzio\OpenApiGenerator\Route\OpenApiRoute;
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

    public function testGetNameUsesOperationId(): void
    {
        $expected = self::PREFIX . '.find_pet_by_status';
        $route    = new OpenApiRoute('/foo', 'get', new Operation(['operationId' => 'findPetByStatus']));
        $actual   = $this->namer->getName($route);
        self::assertSame($expected, $actual);
    }

    public function testGetNameUsesRouteAndMethod(): void
    {
        $expected = self::PREFIX . '.find.pet.by_status.get';
        $route    = new OpenApiRoute('/find/pet/byStatus', 'get', new Operation([]));
        $actual   = $this->namer->getName($route);
        self::assertSame($expected, $actual);
    }

    public function testGetNameParsesMultibyteRoute(): void
    {
        $expected = self::PREFIX . '.find.pet.Яfoo.get';
        $route    = new OpenApiRoute('/find/pet/Яfoo', 'get', new Operation([]));
        $actual   = $this->namer->getName($route);
        self::assertSame($expected, $actual);
    }

    public function testGetNameStripsParameterMarkersFromRoute(): void
    {
        $expected = self::PREFIX . '.pet.pet_id.get';
        $route    = new OpenApiRoute('/pet/{petId}', 'get', new Operation([]));
        $actual   = $this->namer->getName($route);
        self::assertSame($expected, $actual);
    }
}

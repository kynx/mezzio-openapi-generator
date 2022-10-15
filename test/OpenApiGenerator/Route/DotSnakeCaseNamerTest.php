<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use Kynx\Mezzio\OpenApi\OpenApiOperation;
use Kynx\Mezzio\OpenApiGenerator\Route\DotSnakeCaseNamer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\DotSnakeCaseNamer
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
        $expected  = self::PREFIX . '.find_pet_by_status';
        $operation = new OpenApiOperation('findPetByStatus', 'foo', 'get');
        $actual    = $this->namer->getName($operation);
        self::assertSame($expected, $actual);
    }

    public function testGetNameUsesRouteAndMethod(): void
    {
        $expected  = self::PREFIX . '.find.pet.by_status.get';
        $operation = new OpenApiOperation(null, '/find/pet/byStatus', 'get');
        $actual    = $this->namer->getName($operation);
        self::assertSame($expected, $actual);
    }

    public function testGetNameParsesMultibyteRoute(): void
    {
        $expected  = self::PREFIX . '.find.pet.Яfoo.get';
        $operation = new OpenApiOperation(null, '/find/pet/Яfoo', 'get');
        $actual    = $this->namer->getName($operation);
        self::assertSame($expected, $actual);
    }

    public function testGetNameStripsParameterMarkersFromRoute(): void
    {
        $expected  = self::PREFIX . '.pet.pet_id.get';
        $operation = new OpenApiOperation(null, '/pet/{petId}', 'get');
        $actual    = $this->namer->getName($operation);
        self::assertSame($expected, $actual);
    }
}

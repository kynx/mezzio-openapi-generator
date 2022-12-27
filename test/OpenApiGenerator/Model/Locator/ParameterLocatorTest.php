<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\ParameterLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\SchemaLocator
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Locator\ParameterLocator
 */
final class ParameterLocatorTest extends TestCase
{
    private ParameterLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new ParameterLocator();
    }

    public function testGetNamedSchemasReferencedSchemaThrowsException(): void
    {
        $ref       = '#/components/parameters/Foo';
        $parameter = new Parameter([
            'schema' => new Reference(['$ref' => $ref]),
        ]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unresolved reference: '$ref'");
        $this->locator->getNamedSchemas('', $parameter);
    }

    public function testGetNamedSchemasReturnsSchema(): void
    {
        $schema    = $this->getSchema();
        $parameter = new Parameter([
            'name'   => 'bar',
            'in'     => 'query',
            'schema' => $schema,
        ]);
        $expected  = ['' => new NamedSchema('Foo barParam', $schema)];

        self::assertTrue($parameter->validate(), implode("\n", $parameter->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $parameter);
        self::assertEquals($expected, $actual);
    }

    public function testGetNamedSchemasReturnsContent(): void
    {
        $schema    = $this->getSchema();
        $parameter = new Parameter([
            'name'    => 'bar',
            'in'      => 'query',
            'content' => [
                'application/json' => [
                    'schema' => $schema,
                ],
            ],
        ]);
        $expected  = ['' => new NamedSchema('Foo barParam', $schema)];

        self::assertTrue($parameter->validate(), implode("\n", $parameter->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $parameter);
        self::assertEquals($expected, $actual);
    }

    private function getSchema(): Schema
    {
        return new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
    }
}

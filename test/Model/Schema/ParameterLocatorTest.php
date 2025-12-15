<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function implode;

#[CoversClass(ParameterLocator::class)]
#[UsesClass(MediaTypeLocator::class)]
#[UsesClass(NamedSpecification::class)]
#[UsesClass(SchemaLocator::class)]
#[UsesClass(ModelException::class)]
#[UsesClass(ModelUtil::class)]
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
        $this->locator->getNamedSpecifications('', $parameter);
    }

    public function testGetNamedSchemasReturnsSchema(): void
    {
        $schema    = $this->getSchema();
        $parameter = new Parameter([
            'name'   => 'bar',
            'in'     => 'query',
            'schema' => $schema,
        ]);
        $expected  = ['' => new NamedSpecification('Foo bar', $schema)];

        self::assertTrue($parameter->validate(), implode("\n", $parameter->getErrors()));
        $actual = $this->locator->getNamedSpecifications('Foo', $parameter);
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
        $expected  = ['' => new NamedSpecification('Foo bar', $schema)];

        self::assertTrue($parameter->validate(), implode("\n", $parameter->getErrors()));
        $actual = $this->locator->getNamedSpecifications('Foo', $parameter);
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

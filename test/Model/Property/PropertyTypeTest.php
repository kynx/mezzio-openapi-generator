<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use PHPUnit\Framework\TestCase;
use stdClass;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelException
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType
 */
final class PropertyTypeTest extends TestCase
{
    /**
     * @dataProvider baseTypeProvider
     */
    public function testFromSchemaReturnsBaseTypes(string $type, PropertyType $expected): void
    {
        $schema = $this->getSchema([
            'type' => $type,
        ]);
        $actual = PropertyType::fromSchema($schema);
        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array{0: string, 1: PropertyType}>
     */
    public static function baseTypeProvider(): array
    {
        return [
            "array"   => ["array", PropertyType::Array],
            "boolean" => ["boolean", PropertyType::Boolean],
            "integer" => ["integer", PropertyType::Integer],
            "null"    => ["null", PropertyType::Null],
            "number"  => ["number", PropertyType::Number],
            "object"  => ["object", PropertyType::Object],
            "string"  => ["string", PropertyType::String],
        ];
    }

    public function testFromSchemaUnknownTypeThrowsException(): void
    {
        $schema = $this->getSchema([
            'type' => 'unknown',
        ]);
        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unrecognized type 'unknown'");
        PropertyType::fromSchema($schema);
    }

    /**
     * @dataProvider formatProvider
     */
    public function testFromSchemaReturnsFormatType(string $format, PropertyType $expected): void
    {
        $schema = $this->getSchema([
            'type'   => 'string',
            'format' => $format,
        ]);
        $actual = PropertyType::fromSchema($schema);
        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array{0: string, 1: PropertyType}>
     */
    public static function formatProvider(): array
    {
        return [
            'date'                  => ['date', PropertyType::Date],
            'date-time'             => ['date-time', PropertyType::DateTime],
            'duration'              => ['duration', PropertyType::Duration],
            'email'                 => ['email', PropertyType::Email],
            'hostname'              => ['hostname', PropertyType::Hostname],
            'idn-email'             => ['idn-email', PropertyType::IdnEmail],
            'idn-hostname'          => ['idn-hostname', PropertyType::IdnHostname],
            'ipv4'                  => ['ipv4', PropertyType::IpV4],
            'ipv6'                  => ['ipv6', PropertyType::IpV6],
            'iri'                   => ['iri', PropertyType::Iri],
            'iri-reference'         => ['iri-reference', PropertyType::IriReference],
            'json-pointer'          => ['json-pointer', PropertyType::JsonPointer],
            'regex'                 => ['regex', PropertyType::Regex],
            'relative-json-pointer' => ['relative-json-pointer', PropertyType::RelativeJsonPointer],
            'uri'                   => ['uri', PropertyType::Uri],
            'uri-reference'         => ['uri-reference', PropertyType::UriReference],
            'uri-template'          => ['uri-template', PropertyType::UriTemplate],
            'uuid'                  => ['uuid', PropertyType::Uuid],
            'unknown'               => ['unknown', PropertyType::String],
        ];
    }

    /**
     * @dataProvider valueProvider
     */
    public function testFromValueReturnsType(mixed $value, PropertyType $expected): void
    {
        $actual = PropertyType::fromValue($value);
        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array{0: mixed, 1: PropertyType}>
     */
    public static function valueProvider(): array
    {
        return [
            'boolean' => [true, PropertyType::Boolean],
            'double'  => [1.23, PropertyType::Number],
            'integer' => [123, PropertyType::Integer],
            'null'    => [null, PropertyType::Null],
            'string'  => ['foo', PropertyType::String],
        ];
    }

    public function testFromValueUnknownThrowsException(): void
    {
        $value = new stdClass();
        self::expectException(ModelException::class);
        self::expectExceptionMessage("Unrecognized value 'stdClass'");
        PropertyType::fromValue($value);
    }

    /**
     * @dataProvider toPhpTypeProvider
     */
    public function testToPhpType(PropertyType $type, string $expected): void
    {
        $actual = $type->toPhpType();
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{0: PropertyType, 1: string}>
     */
    public static function toPhpTypeProvider(): array
    {
        return [
            'array'    => [PropertyType::Array, 'array'],
            'bool'     => [PropertyType::Boolean, 'bool'],
            'date'     => [PropertyType::Date, 'string'],
            'datetime' => [PropertyType::Date, 'string'],
            'duration' => [PropertyType::Duration, 'string'],
            'int'      => [PropertyType::Integer, 'int'],
            'float'    => [PropertyType::Number, 'float'],
            'null'     => [PropertyType::Null, 'null'],
            'object'   => [PropertyType::Object, 'object'],
            'string'   => [PropertyType::String, 'string'],
            'uri'      => [PropertyType::Uri, 'string'],
            'other'    => [PropertyType::JsonPointer, 'string'],
        ];
    }

    private function getSchema(array $spec): Schema
    {
        $schema = new Schema($spec);
        self::assertTrue($schema->validate(), implode("\n", $schema->getErrors()));
        return $schema;
    }
}

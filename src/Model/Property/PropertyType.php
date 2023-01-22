<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Property;

use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

use function gettype;

/**
 * @internal
 *
 * @link https://spec.openapis.org/oas/v3.1.0#data-types
 * @link https://json-schema.org/draft/2020-12/json-schema-validation.html#section-6.1.1
 * @link https://json-schema.org/draft/2020-12/json-schema-validation.html#section-7.3
 * @link https://json-schema.org/understanding-json-schema/reference/string.html#format
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Property\PropertyTypeTest
 *
 * @psalm-immutable
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
enum PropertyType
{
    // base types
    case Array;
    case Boolean;
    case Integer;
    case Object;
    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
    case Null;
    case Number;
    case String;

    // string format types
    case Date;
    case DateTime;
    case Duration;
    case Email;
    case Hostname;
    case IdnEmail;
    case IdnHostname;
    case IpV4;
    case IpV6;
    case Iri;
    case IriReference;
    case JsonPointer;
    case Regex;
    case RelativeJsonPointer;
    case Time;
    case Uri;
    case UriReference;
    case UriTemplate;
    case Uuid;

    /**
     * @psalm-mutation-free
     */
    public static function fromSchema(Schema $schema): self
    {
        return match ($schema->type) {
            'array'     => self::Array,
            'boolean'   => self::Boolean,
            'integer'   => self::Integer,
            'null'      => self::Null,
            'number'    => self::Number,
            'object'    => self::Object,
            'string'    => self::fromString($schema->format),
            default     => throw ModelException::unrecognizedType($schema->type),
        };
    }

    /**
     * @psalm-mutation-free
     */
    public static function fromValue(mixed $value): self
    {
        return match (gettype($value)) {
            'boolean' => PropertyType::Boolean,
            'double'  => PropertyType::Number,
            'integer' => PropertyType::Integer,
            'NULL'    => PropertyType::Null,
            'string'  => PropertyType::String,
            default   => throw ModelException::unrecognizedValue($value),
        };
    }

    /**
     * @psalm-mutation-free
     */
    private static function fromString(string|null $format): self
    {
        return match ($format) {
            'date'                  => self::Date,
            'date-time'             => self::DateTime,
            'duration'              => self::Duration,
            'email'                 => self::Email,
            'hostname'              => self::Hostname,
            'idn-email'             => self::IdnEmail,
            'idn-hostname'          => self::IdnHostname,
            'iri'                   => self::Iri,
            'iri-reference'         => self::IriReference,
            'ipv4'                  => self::IpV4,
            'ipv6'                  => self::IpV6,
            'json-pointer'          => self::JsonPointer,
            'regex'                 => self::Regex,
            'relative-json-pointer' => self::RelativeJsonPointer,
            'time'                  => self::Time,
            'uri'                   => self::Uri,
            'uri-reference'         => self::UriReference,
            'uri-template'          => self::UriTemplate,
            'uuid'                  => self::Uuid,
            default                 => self::String,
        };
    }

    public function toPhpType(): string
    {
        return match ($this) {
            self::Array                => 'array',
            self::Boolean              => 'bool',
            self::Integer              => 'int',
            self::Null                 => 'null',
            self::Number               => 'float',
            self::Object               => 'object',
            default                    => 'string',
        };
    }
}

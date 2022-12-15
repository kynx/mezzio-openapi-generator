<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\spec\Schema;

enum PropertyType
{
    private const ENUM_TYPES = ['integer', 'string'];

    case Array;
    case Boolean;
    case Byte;
    case Binary;
    case Date;
    case DateTime;
    case Enum;
    case Float;
    case Integer;
    case List;
    case Null;
    case Object;
    case Password;
    case String;

    public static function fromSchema(Schema $schema): self
    {
        if (in_array($schema->type, self::ENUM_TYPES, true) && ! empty($schema->enum)) {
            return self::Enum;
        }
        if ($schema->type === 'object' && $schema->additionalProperties) {
            return self::Array;
        }
        if ($schema->type === 'string' && $schema->format === 'datetime') {
            return self::DateTime;
        }

        return match ($schema->type) {
            'array'     => self::List,
            'boolean'   => self::Boolean,
            'binary'    => self::Binary,
            'byte'      => self::Byte,
            'date'      => self::Date,
            'date-time' => self::DateTime,
            'integer'   => self::Integer,
            'number'    => self::Float,
            'object'    => self::Object,
            'password'  => self::Password,
            'string'    => self::String,
            default     => throw ModelException::unrecognizedType($schema->type),
        };
    }

    public static function fromValue(mixed $value, bool|null $nullable): self
    {
        if ($value === null && $nullable) {
            return PropertyType::Null;
        }

        return match (gettype($value)) {
            'boolean' => PropertyType::Boolean,
            'double'  => PropertyType::Float,
            'integer' => PropertyType::Integer,
            'string'  => PropertyType::String,
            default   => throw ModelException::unrecognizedValue($value),
        };
    }
}

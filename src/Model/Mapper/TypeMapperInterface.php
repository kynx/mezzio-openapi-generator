<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Mapper;

/**
 * Implement this to map schema `type` and `format` strings to PHP classes or interfaces
 *
 * The class string your implementation returns will be used as a type declaration in the generated models. You will
 * also need to implement a `Kynx\Mezzio\OpenApi\Hydrator\HydratorInterface` that returns concrete instances from
 * incoming data.
 *
 * For a list of possible `type` and `format` values, see the following links:
 *
 * @link https://datatracker.ietf.org/doc/html/draft-bhutton-json-schema-00#section-4.2.1 (type)
 * @link https://datatracker.ietf.org/doc/html/draft-bhutton-json-schema-validation-00#section-7.3 (string formats)
 * @link https://spec.openapis.org/oas/v3.1.0#dataTypeFormat (OAS formats)
 *
 * @psalm-immutable
 */
interface TypeMapperInterface
{
    public function canMap(string $type, string|null $format): bool;

    /**
     * @return class-string
     */
    public function getClassString(string $type, string|null $format): string;
}

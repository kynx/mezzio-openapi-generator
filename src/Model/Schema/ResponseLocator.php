<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Response;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;

use function array_map;
use function array_merge;
use function implode;
use function preg_split;
use function strtolower;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class ResponseLocator
{
    public function __construct(
        private readonly MediaTypeLocator $mediaTypeLocator = new MediaTypeLocator(),
        private readonly SchemaLocator $schemaLocator = new SchemaLocator()
    ) {
    }

    /**
     * @return array<string, NamedSpecification>
     */
    public function getNamedSchemas(string $baseName, Response $response): array
    {
        $models = $this->mediaTypeLocator->getNamedSpecifications($baseName . 'Response', $response->content);
        foreach ($response->headers as $headerName => $header) {
            if ($header instanceof Reference) {
                throw ModelException::unresolvedReference($header);
            }
            if ($header->schema instanceof Reference) {
                throw ModelException::unresolvedReference($header->schema);
            }
            if ($header->schema === null) {
                continue;
            }

            $name   = $baseName . $this->normalizeHeaderName((string) $headerName);
            $models = array_merge($models, $this->schemaLocator->getNamedSpecifications($name, $header->schema));
        }

        return $models;
    }

    public function normalizeHeaderName(string $name): string
    {
        $parts = preg_split('/[^a-z0-9]+/i', strtolower($name));
        return implode('', array_map('ucfirst', $parts)) . 'Header';
    }
}

<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Response;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

use function array_map;
use function array_merge;
use function exp;
use function explode;
use function implode;
use function mb_convert_case;
use function preg_split;
use function str_repeat;
use function str_replace;
use function strtolower;
use function ucfirst;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\ResponseLocatorTest
 */
final class ResponseLocator
{
    private MediaTypeLocator $mediaTypeLocator;
    private SchemaLocator $schemaLocator;

    public function __construct()
    {
        $this->mediaTypeLocator = new MediaTypeLocator();
        $this->schemaLocator    = new SchemaLocator();
    }

    /**
     * @return array<string, Model>
     */
    public function getModels(string $baseName, Response $response): array
    {
        $models = $this->mediaTypeLocator->getModels($baseName . 'Response', $response->content);
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
            $models = array_merge($models, $this->schemaLocator->getModels($name, $header->schema));
        }

        return $models;
    }

    public function normalizeHeaderName(string $name): string
    {
        $parts = preg_split('/[^a-z0-9]+/i', strtolower($name));
        return implode('', array_map('ucfirst', $parts)) . 'Header';
    }
}

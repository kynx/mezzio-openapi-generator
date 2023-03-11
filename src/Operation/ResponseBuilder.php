<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Operation;

use cebe\openapi\spec\Header;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyBuilder;

use function array_key_first;
use function assert;

/**
 * @internal
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ResponseBuilder
{
    public function __construct(private readonly PropertyBuilder $propertyBuilder)
    {
    }

    /**
     * @param array<string, string> $classNames
     * @return list<ResponseModel>
     */
    public function getResponseModels(Operation $operation, array $classNames): array
    {
        assert($operation->responses instanceof Responses);

        $responses = [];
        foreach ($operation->responses->getResponses() as $status => $response) {
            assert($response instanceof Response);

            $headers = $this->getHeaders($response, $classNames);

            if ($response->content === []) {
                $responses[] = new ResponseModel((string) $status, $response->description, null, null, $headers);
                continue;
            }

            /** @var string $mimeType */
            foreach ($response->content as $mimeType => $mediaType) {
                if ($mediaType->schema instanceof Schema) {
                    $type = $this->propertyBuilder->getProperty(
                        $mediaType->schema,
                        '',
                        '',
                        false,
                        $classNames
                    );
                } else {
                    $type = null;
                }

                $responses[] = new ResponseModel((string) $status, $response->description, $mimeType, $type, $headers);
            }
        }

        return $responses;
    }

    /**
     * @param array<string, string> $classNames
     * @return list<ResponseHeader>
     */
    private function getHeaders(Response $response, array $classNames): array
    {
        $headers = [];
        /** @var string $name */
        foreach ($response->headers as $name => $header) {
            assert($header instanceof Header);

            $schema = $mimeType = null;

            if (isset($header->schema)) {
                $schema = $header->schema;
            } elseif ($header->content !== []) {
                $mimeType = (string) array_key_first($header->content);
                $content  = $header->content[$mimeType];
                $schema   = $content->schema ?? null;
            }

            if ($schema instanceof Schema) {
                $property  = $this->propertyBuilder->getProperty(
                    $schema,
                    $name,
                    $name,
                    false,
                    $classNames
                );
                $template  = $this->getHeaderTemplate($name, $header);
                $headers[] = new ResponseHeader($name, $template, $mimeType, $property);
            }
        }

        return $headers;
    }

    private function getHeaderTemplate(string $name, Header $header): string|null
    {
        if (! (isset($header->schema) && $header->style === 'simple')) {
            return null;
        }
        $explode = $header->explode ? '*' : '';
        return '{' . $name . $explode . '}';
    }
}

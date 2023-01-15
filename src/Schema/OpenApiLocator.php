<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Schema;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Paths;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

use function array_values;
use function assert;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Schema\OpenApiLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class OpenApiLocator
{
    public function __construct(private readonly PathsLocator $pathsLocator)
    {
    }

    /**
     * @return list<NamedSpecification>
     */
    public function getNamedSpecifications(OpenApi $openApi): array
    {
        if ($openApi->getDocumentPosition() === null) {
            throw ModelException::missingDocumentContext();
        }

        // Upstream typehint is confused...
        assert($openApi->paths instanceof Paths);
        return array_values($this->pathsLocator->getNamedSpecifications($openApi->paths));
    }
}

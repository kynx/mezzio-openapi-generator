<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Paths;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

use function array_values;
use function assert;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Schema\OpenApiLocatorTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class OpenApiLocator
{
    private PathsLocator $pathsLocator;

    public function __construct()
    {
        $this->pathsLocator = new PathsLocator();
    }

    /**
     * @return list<NamedSpecification>
     */
    public function getNamedSchemas(OpenApi $openApi): array
    {
        if ($openApi->getDocumentPosition() === null) {
            throw ModelException::missingDocumentContext();
        }

        // Upstream typehint is confused...
        assert($openApi->paths instanceof Paths);
        return array_values($this->pathsLocator->getNamedSchemas($openApi->paths));
    }
}

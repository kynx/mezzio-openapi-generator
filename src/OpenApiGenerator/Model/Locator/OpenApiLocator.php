<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Paths;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;

use function array_values;
use function assert;

/**
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\Locator\OpenApiLocatorTest
 */
final class OpenApiLocator
{
    private PathsLocator $pathsLocator;

    public function __construct()
    {
        $this->pathsLocator = new PathsLocator();
    }

    public function getModels(OpenApi $openApi): array
    {
        if ($openApi->getDocumentPosition() === null) {
            throw ModelException::missingDocumentContext();
        }

        // Upstream typehint is confused...
        assert($openApi->paths instanceof Paths);
        return array_values($this->pathsLocator->getModels($openApi->paths));
    }
}

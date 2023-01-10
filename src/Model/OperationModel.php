<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\OperationModelTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class OperationModel extends AbstractClassLikeModel
{
    public function __construct(string $className, string $jsonPointer, PropertyInterface ...$properties)
    {
        parent::__construct($className, $jsonPointer, ...$properties);
    }

    public function getImplements(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\ClassModelTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
final class ClassModel extends AbstractClassLikeModel
{
    /**
     * @param list<string> $implements
     */
    public function __construct(
        string $className,
        string $jsonPointer,
        private readonly array $implements,
        PropertyInterface ...$properties
    ) {
        parent::__construct($className, $jsonPointer, ...$properties);
    }

    /**
     * @return list<string>
     */
    public function getImplements(): array
    {
        return $this->implements;
    }
}

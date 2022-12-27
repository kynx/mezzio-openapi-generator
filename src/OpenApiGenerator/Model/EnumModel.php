<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use function array_values;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Model\EnumModelTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator\Model
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator\Model
 */
final class EnumModel
{
    /** @var list<EnumCase> */
    private readonly array $cases;

    public function __construct(
        private readonly string $className,
        private readonly string $jsonPointer,
        EnumCase ...$cases
    ) {
        $this->cases = array_values($cases);
    }

    public function matches(ClassModel|EnumModel|InterfaceModel $toMatch): bool
    {
        if ($toMatch::class !== self::class) {
            return false;
        }
        return $this->className === $toMatch->getClassName();
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getJsonPointer(): string
    {
        return $this->jsonPointer;
    }

    /**
     * @return list<EnumCase>
     */
    public function getCases(): array
    {
        return $this->cases;
    }
}

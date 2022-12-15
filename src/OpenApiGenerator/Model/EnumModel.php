<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

final class EnumModel
{
    /** @var array<string, EnumCase> */
    private readonly array $cases;

    public function __construct(
        private readonly string $className,
        private readonly string $jsonPointer,
        EnumCase ...$cases
    ) {
        $this->cases = $cases;
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
     * @return array<string, EnumCase>
     */
    public function getCases(): array
    {
        return $this->cases;
    }
}
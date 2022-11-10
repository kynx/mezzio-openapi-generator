<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\spec\Schema;

/**
 * @internal
 */
final class UnresolvedModel
{
    /** @var UnresolvedModel[] */
    private readonly array $dependents;

    public function __construct(
        public readonly string $baseName,
        public readonly string $name,
        private readonly Schema|null $schema,
        UnresolvedModel ...$dependents
    ) {
        $this->dependents = $dependents;
    }

    public function getJsonPointer(): string
    {
        if ($this->schema === null) {
            return '';
        }
        return Util::getJsonPointer($this->schema);
    }

    public function getSchema(): Schema|null
    {
        return $this->schema;
    }

    public function getNames(): array
    {
        $jsonPointer = $this->getJsonPointer();
        $names = $this->name === '' ? [] : [$jsonPointer => $this->baseName . ' ' . $this->name];
        foreach ($this->dependents as $dependent) {
            $names = array_merge($names, $dependent->getNames());
        }

        return $names;
    }

    public function getDependents(array &$unresolved = []): array
    {
        $jsonPointer = $this->getJsonPointer();
        $unresolved[$jsonPointer] = $this;

        $resolved = $this->resolveDependents($unresolved);
        if ($this->schema !== null) {
            $resolved[$jsonPointer] = $this;
        }

        unset($unresolved[$jsonPointer]);
        return $resolved;
    }

    private function resolveDependents(array &$unresolved): array
    {
        $resolved = [];
        foreach ($this->dependents as $dependent) {
            $jsonPointer = $dependent->getJsonPointer();
            if (! isset($resolved[$jsonPointer])) {
                if (isset($unresolved[$jsonPointer])) {
                    throw ModelException::circularReference($this, $dependent);
                }
                $resolved = array_merge($resolved, $dependent->getDependents($unresolved));
            }
        }
        return $resolved;
    }
}
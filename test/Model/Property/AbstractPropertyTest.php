<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\AbstractProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use PHPUnit\Framework\TestCase;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\AbstractProperty
 */
final class AbstractPropertyTest extends TestCase
{
    public function testGettersReturnValues(): void
    {
        $name         = '$foo';
        $originalName = 'foo';
        $metadata     = new PropertyMetadata();

        /**
         * @psalm-suppress InternalClass
         * @psalm-suppress MissingImmutableAnnotation
         */
        $property = new class ($name, $originalName, $metadata) extends AbstractProperty {
            public function __construct(
                protected readonly string $name,
                protected readonly string $originalName,
                protected readonly PropertyMetadata $metadata
            ) {
            }

            /** @psalm-mutation-free  */
            public function getUses(): array
            {
                return [];
            }

            /** @psalm-mutation-free  */
            public function getPhpType(): string
            {
                return '';
            }

            /** @psalm-mutation-free */
            public function getDocBlockType(bool $forUnion = false): string|null
            {
                return null;
            }

            /** @psalm-mutation-free  */
            public function getTypes(): array
            {
                return [PropertyType::String];
            }
        };

        self::assertSame($name, $property->getName());
        self::assertSame($originalName, $property->getOriginalName());
        self::assertSame($metadata, $property->getMetadata());
    }
}

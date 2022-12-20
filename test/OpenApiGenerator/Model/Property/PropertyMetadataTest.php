<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Property;

use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata
 */
final class PropertyMetadataTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $title       = 'Title';
        $description = 'Description';
        $required    = true;
        $nullable    = true;
        $deprecated  = true;
        $default     = 'Default';
        $examples    = ['Example'];

        $metadata = new PropertyMetadata($title, $description, $required, $nullable, $deprecated, $default, $examples);

        self::assertSame($title, $metadata->getTitle());
        self::assertSame($description, $metadata->getDescription());
        self::assertSame($required, $metadata->isRequired());
        self::assertSame($nullable, $metadata->isNullable());
        self::assertSame($deprecated, $metadata->isDeprecated());
        self::assertSame($default, $metadata->getDefault());
        self::assertSame($examples, $metadata->getExamples());
    }
}

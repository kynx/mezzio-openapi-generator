<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\RequestBodyLocator;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\NamedSchema
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\SchemaLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Locator\RequestBodyLocator
 */
final class RequestBodyLocatorTest extends TestCase
{
    private RequestBodyLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new RequestBodyLocator();
    }

    public function testGetNamedSchemasAppendsRequestBodyToName(): void
    {
        $schema      = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $requestBody = new RequestBody([
            'content' => [
                'application/json' => [
                    'schema' => $schema,
                ],
            ],
        ]);
        $expected    = ['' => new NamedSchema('Foo RequestBody', $schema)];

        self::assertTrue($requestBody->validate(), implode("\n", $requestBody->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $requestBody);
        self::assertEquals($expected, $actual);
    }
}

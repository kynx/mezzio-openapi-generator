<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator
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
        $expected    = ['' => new NamedSpecification('Foo RequestBody', $schema)];

        self::assertTrue($requestBody->validate(), implode("\n", $requestBody->getErrors()));
        $actual = $this->locator->getNamedSchemas('Foo', $requestBody);
        self::assertEquals($expected, $actual);
    }
}

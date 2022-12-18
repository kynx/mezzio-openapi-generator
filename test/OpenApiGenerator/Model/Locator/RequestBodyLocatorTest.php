<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\Model;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\RequestBodyLocator;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\MediaTypeLocator
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

    public function testGetModelsAppendsRequestBodyToName(): void
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
        $expected    = ['' => new Model('Foo RequestBody', $schema)];

        self::assertTrue($requestBody->validate(), implode("\n", $requestBody->getErrors()));
        $actual = $this->locator->getModels('Foo', $requestBody);
        self::assertEquals($expected, $actual);
    }
}

<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Locator;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\Model;
use Kynx\Mezzio\OpenApiGenerator\Model\Locator\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Locator\PathsLocator
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Locator\OpenApiLocator
 */
final class OpenApiLocatorTest extends TestCase
{
    private OpenApiLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new OpenApiLocator();
    }

    public function testGetModelsNoDocumentContextThrowsException(): void
    {
        $openApi = new OpenApi([]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage('Specification is missing a document context');
        $this->locator->getModels($openApi);
    }

    public function testGetModelsReturnsList(): void
    {
        $schema   = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $openApi  = new OpenApi([
            'openapi' => '3.0.3',
            'info'    => [
                'title'       => 'Title',
                'description' => 'Description',
                'version'     => '1.0.0',
            ],
            'paths'   => [
                '/my/pets' => [
                    'get' => [
                        'responses' => [
                            'default' => [
                                'description' => 'Pets',
                                'content'     => [
                                    'application/json' => [
                                        'schema' => $schema,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $openApi->setDocumentContext($openApi, new JsonPointer(''));
        $expected = [new Model('my pets defaultResponse', $schema)];

        self::assertTrue($openApi->validate(), implode("\n", $openApi->getErrors()));
        $actual = $this->locator->getModels($openApi);
        self::assertEquals($expected, $actual);
    }
}

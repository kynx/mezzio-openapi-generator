<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\OpenApiLocator;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\NamedSpecification
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathsLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelException
 * @uses \Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil
 * @uses \Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil
 *
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\Schema\OpenApiLocator
 */
final class OpenApiLocatorTest extends TestCase
{
    private OpenApiLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new OpenApiLocator();
    }

    public function testGetNamedSchemasNoDocumentContextThrowsException(): void
    {
        $openApi = new OpenApi([]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage('Specification is missing a document context');
        $this->locator->getNamedSchemas($openApi);
    }

    public function testGetNamedSchemasReturnsList(): void
    {
        $schema  = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $openApi = new OpenApi([
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
        $expected = [new NamedSpecification('my pets defaultResponse', $schema)];

        self::assertTrue($openApi->validate(), implode("\n", $openApi->getErrors()));
        $actual = $this->locator->getNamedSchemas($openApi);
        self::assertEquals($expected, $actual);
    }
}
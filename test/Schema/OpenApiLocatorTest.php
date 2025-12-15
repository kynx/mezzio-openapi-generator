<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Schema;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelException;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\MediaTypeLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\OperationLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\ParameterLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\PathItemLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\RequestBodyLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\ResponseLocator;
use Kynx\Mezzio\OpenApiGenerator\Model\Schema\SchemaLocator;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteUtil;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use Kynx\Mezzio\OpenApiGenerator\Schema\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Schema\PathsLocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function implode;

#[CoversClass(OpenApiLocator::class)]
#[UsesClass(MediaTypeLocator::class)]
#[UsesClass(NamedSpecification::class)]
#[UsesClass(OperationLocator::class)]
#[UsesClass(ParameterLocator::class)]
#[UsesClass(PathItemLocator::class)]
#[UsesClass(PathsLocator::class)]
#[UsesClass(RequestBodyLocator::class)]
#[UsesClass(ResponseLocator::class)]
#[UsesClass(SchemaLocator::class)]
#[UsesClass(ModelException::class)]
#[UsesClass(ModelUtil::class)]
#[UsesClass(RouteUtil::class)]
final class OpenApiLocatorTest extends TestCase
{
    private OpenApiLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new OpenApiLocator(new PathsLocator(new PathItemLocator()));
    }

    public function testGetNamedSchemasNoDocumentContextThrowsException(): void
    {
        $openApi = new OpenApi([]);

        self::expectException(ModelException::class);
        self::expectExceptionMessage('Specification is missing a document context');
        $this->locator->getNamedSpecifications($openApi);
    }

    public function testGetNamedSchemasReturnsList(): void
    {
        $schema    = new Schema([
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                ],
            ],
        ]);
        $operation = new Operation([
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
        ]);
        $openApi   = new OpenApi([
            'openapi' => '3.0.3',
            'info'    => [
                'title'       => 'Title',
                'description' => 'Description',
                'version'     => '1.0.0',
            ],
            'paths'   => [
                '/my/pets' => [
                    'get' => $operation,
                ],
            ],
        ]);
        $openApi->setDocumentContext($openApi, new JsonPointer(''));
        $expected = [
            new NamedSpecification('Path my pets get defaultResponse', $schema),
        ];

        self::assertTrue($openApi->validate(), implode("\n", $openApi->getErrors()));
        $actual = $this->locator->getNamedSpecifications($openApi);
        self::assertEquals($expected, $actual);
    }
}

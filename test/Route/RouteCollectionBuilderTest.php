<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use Kynx\Mezzio\OpenApiGenerator\Route\ParameterModel;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollection;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteModel;
use PHPUnit\Framework\TestCase;

use function implode;
use function iterator_to_array;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteCollectionBuilder
 */
final class RouteCollectionBuilderTest extends TestCase
{
    public function testGetRouteCollectionReturnsCollection(): void
    {
        $expected   = [
            new RouteModel('/paths/~1my~1pets~1{id}/get', '/my/pets/{id}', 'get', [
                new ParameterModel('id', false, 'string', 'simple', false),
            ], [
                new ParameterModel('age', false, 'integer', 'form', true),
            ], []),
            new RouteModel('/paths/~1another-pet/post', '/another-pet', 'post', [], [
                new ParameterModel('complicated', true, null, null, false),
            ], []),
        ];
        $collection = new RouteCollection();
        foreach ($expected as $route) {
            $collection->add($route);
        }

        $openApi = $this->getOpenApi($this->getPathsSpec());
        $builder = new RouteCollectionBuilder([]);
        $actual  = iterator_to_array($builder->getRouteCollection($openApi));
        self::assertEquals($expected, $actual);
    }

    public function testGetRouteCollectionAddsMiddleware(): void
    {
        $middlware = 'Api\Pet\Middleware\Guard';
        $expected = [
            new RouteModel('/paths/~1my~1pets/get', '/my/pets', 'get', [], [], [
                $middlware,
            ]),
        ];
        $collection = new RouteCollection();
        foreach ($expected as $route) {
            $collection->add($route);
        }

        $openApi = $this->getOpenApi($this->getMiddlewareExtensionSpec());
        $builder = new RouteCollectionBuilder(['pet-guard' => $middlware]);
        $actual  = iterator_to_array($builder->getRouteCollection($openApi));
        self::assertEquals($expected, $actual);
    }

    public function getPathsSpec(): array
    {
        $responses = [
            'default' => [
                'description' => 'Pets',
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];

        return [
            '/my/pets/{id}' => [
                'get' => [
                    'type'       => 'object',
                    'parameters' => [
                        [
                            'name'     => 'id',
                            'in'       => 'path',
                            'required' => true,
                            'schema'   => [
                                'type' => 'string',
                            ],
                        ],
                        [
                            'name'   => 'age',
                            'in'     => 'query',
                            'schema' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                    'responses'  => $responses,
                ],
            ],
            '/another-pet'  => [
                'post' => [
                    'name'       => 'complicated',
                    'in'         => 'query',
                    'parameters' => [
                        [
                            'name'    => 'complicated',
                            'in'      => 'query',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses'  => $responses,
                ],
            ],
        ];
    }

    public function getMiddlewareExtensionSpec(): array
    {
        $responses = [
            'default' => [
                'description' => 'Pets',
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];

        return [
            '/my/pets' => [
                'get' => [
                    'type'       => 'object',
                    'x-psr15-middleware' => 'pet-guard',
                    'responses'          => $responses,
                ],
            ],
        ];
    }

    public function getOpenApi(array $pathSpec): OpenApi
    {
        $openApi = new OpenApi([
            'openapi' => '3.0.3',
            'info'    => [
                'title'       => 'Title',
                'description' => 'Description',
                'version'     => '1.0.0',
            ],
            'paths'   => $pathSpec,
        ]);

        $openApi->setDocumentContext($openApi, new JsonPointer(''));
        self::assertTrue($openApi->validate(), implode("\n", $openApi->getErrors()));

        return $openApi;
    }
}

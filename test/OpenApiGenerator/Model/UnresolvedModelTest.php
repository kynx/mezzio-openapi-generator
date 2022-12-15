<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Model;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\ReferenceContext;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Kynx\Mezzio\OpenApiGenerator\Model\UnresolvedModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Model\UnresolvedModel
 */
final class UnresolvedModelTest extends TestCase
{
    public function testGetJsonPointerReturnsEmptyForNullSchema(): void
    {
        $expected = '';
        $model = new UnresolvedModel('', 'Foo', null);
        $actual = $model->getJsonPointer();
        self::assertSame($expected, $actual);
    }

    public function testGetJsonPointerReturnsDocumentPosition(): void
    {
        $expected = '/components/schemas/Foo';
        $schema = new Schema([]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($expected));
        $model = new UnresolvedModel('', 'Foo', $schema);
        $actual = $model->getJsonPointer();
        self::assertSame($expected, $actual);
    }

    public function testGetClassNamesReturnsEmptyForUnnamedModel(): void
    {
        $expected = [];
        $model = new UnresolvedModel('', '', null);
        $actual = $model->getClassNames();
        self::assertSame($expected, $actual);
    }

    public function testGetClassNamesReturnsModelName(): void
    {
        $pointer = '/components/schemas/Foo';
        $expected = [$pointer => 'Base Foo'];

        $schema = new Schema([]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $model = new UnresolvedModel('Base', 'Foo', $schema);
        $actual = $model->getClassNames();
        self::assertSame($expected, $actual);
    }

    public function testGetClassNamesReturnsDependentNames(): void
    {
        $pointer = '/components/schemas/Foo';
        $expected = [$pointer => 'Base Foo'];

        $schema = new Schema([]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer($pointer));
        $dependent = new UnresolvedModel('Base', 'Foo', $schema);
        $model = new UnresolvedModel('', '', $schema, $dependent);
        $actual = $model->getClassNames();
        self::assertSame($expected, $actual);
    }

    public function testGetInterfaceNamesNoSchemaReturnsEmpty(): void
    {
        $expected = [];
        $model = new UnresolvedModel('Foo', 'Bar', null);
        $actual = $model->getInterfaceNames([]);
        self::assertSame($expected, $actual);
    }

    public function testGetInterfaceNamesNotAllOfReturnsEmpty(): void
    {
        $expected = [];
        $schema = new Schema(['type' => 'object']);
        $model = new UnresolvedModel('Foo', 'Bar', $schema);
        $actual = $model->getInterfaceNames([]);
        self::assertSame($expected, $actual);
    }

    public function testGetInterfaceNamesNotComponentReturnsEmpty(): void
    {
        $expected = [];
        $schema = new Schema(['allOf' => [['type' => 'object']]]);
        $schema->setDocumentContext(new OpenApi([]), new JsonPointer('/components/schemas/Bar'));
        $model = new UnresolvedModel('Foo', 'Bar', $schema);
        $actual = $model->getInterfaceNames(['/components/schemas/Bar' => 'Bar']);
        self::assertSame($expected, $actual);
    }

    public function testGetInterfaceNamesReturnsInterfaces(): void
    {
        $expected = [
            '/components/schemas/Cat' => 'Cat Interface',
            '/components/schemas/Dog' => 'Dog Interface',
        ];
        $classNames = [
            '/components/schemas/Cat' => 'Cat',
            '/components/schemas/Dog' => 'Dog',
        ];
        $openApi = new OpenApi([
            'components' => [
                'schemas' => [
                    'Cat' => [
                        'type' => 'object',
                    ],
                    'Dog' => [
                        'type' => 'object,'
                    ],
                    'Pet' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/Cat'],
                            ['$ref' => '#/components/schemas/Dog'],
                        ],
                    ],
                ],
            ],
        ]);
        $openApi->setDocumentContext($openApi, new JsonPointer(''));
        $openApi->resolveReferences(new ReferenceContext($openApi, '/path/to/foo.yml'));
        $model = new UnresolvedModel('', 'Pet', $openApi->components->schemas['Pet']);
        $actual = $model->getInterfaceNames($classNames);
        self::assertSame($expected, $actual);
    }

    public function testGetInterfaceNamesReturnsDependents(): void
    {
        $expected = [
            '/components/schemas/Cat' => 'Cat Interface',
            '/components/schemas/Dog' => 'Dog Interface',
        ];
        $classNames = [
            '/components/schemas/Cat' => 'Cat',
            '/components/schemas/Dog' => 'Dog',
        ];
        $openApi = new OpenApi([
            'components' => [
                'schemas' => [
                    'Cat' => [
                        'type' => 'object',
                    ],
                    'Dog' => [
                        'type' => 'object,'
                    ],
                    'Owner' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                            'pet' => [
                                'allOf' => [
                                    ['$ref' => '#/components/schemas/Cat'],
                                    ['$ref' => '#/components/schemas/Dog'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $openApi->setDocumentContext($openApi, new JsonPointer(''));
        $openApi->resolveReferences(new ReferenceContext($openApi, '/path/to/foo.yml'));
        $dependents = [
            new UnresolvedModel('', 'Owner', $openApi->components->schemas['Owner']->properties['name']),
            new UnresolvedModel('', 'Owner', $openApi->components->schemas['Owner']->properties['pet']),
        ];
        $model = new UnresolvedModel('', 'Owner', $openApi->components->schemas['Owner'], ...$dependents);
        $actual = $model->getInterfaceNames($classNames);
        self::assertSame($expected, $actual);
    }
}

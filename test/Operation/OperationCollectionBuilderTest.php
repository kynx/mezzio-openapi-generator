<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use cebe\openapi\json\JsonPointer;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollection;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilder;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Schema\NamedSpecification;
use PHPUnit\Framework\TestCase;

use function implode;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\OperationCollectionBuilder
 */
final class OperationCollectionBuilderTest extends TestCase
{
    use OperationTrait;

    private OperationCollectionBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->getOperationCollectionBuilder(__NAMESPACE__);
    }

    public function testGetOperationCollectionReturnsOperations(): void
    {
        $responses  = [$this->getResponse()];
        $operations = [
            new OperationModel(__NAMESPACE__ . '\\Foo\\Get', '/paths/foo/get', null, null, null, null, [], $responses),
            new OperationModel(__NAMESPACE__ . '\\Bar\\Get', '/paths/bar/get', null, null, null, null, [], $responses),
        ];
        $expected   = new OperationCollection();
        foreach ($operations as $operation) {
            $expected->add($operation);
        }
        $specifications = [
            $this->getNamedSpecification('foo', []),
            $this->getNamedSpecification('bar', []),
        ];

        $actual = $this->builder->getOperationCollection($specifications, []);
        self::assertEquals($expected, $actual);
    }

    private function getNamedSpecification(string $path, array $spec): NamedSpecification
    {
        $spec['responses'] = [
            'default' => [
                'description' => 'Hello world',
                'content'     => [
                    'text/plain' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];

        $operation = new Operation($spec);
        $operation->setDocumentContext(new OpenApi([]), new JsonPointer('/paths/' . $path . '/get'));
        self::assertTrue($operation->validate(), implode("\n", $operation->getErrors()));

        return new NamedSpecification("$path get", $operation);
    }
}

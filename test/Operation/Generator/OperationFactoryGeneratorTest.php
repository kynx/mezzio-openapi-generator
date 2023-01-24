<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation\Generator;

use DateTimeImmutable;
use Generator;
use Kynx\Mezzio\OpenApi\Attribute\OpenApiOperationFactory;
use Kynx\Mezzio\OpenApi\Operation\ContentTypeNegotiator;
use Kynx\Mezzio\OpenApi\Operation\OperationFactoryInterface;
use Kynx\Mezzio\OpenApi\Operation\OperationUtil;
use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ArrayProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\ClassString;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyList;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\Discriminator\PropertyValue;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyInterface;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyMetadata;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\PropertyType;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\SimpleProperty;
use Kynx\Mezzio\OpenApiGenerator\Model\Property\UnionProperty;
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\OperationFactoryGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use Nette\PhpGenerator\PhpFile;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Rize\UriTemplate;

use function array_merge;
use function trim;
use function ucfirst;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\Generator\OperationFactoryGenerator
 */
final class OperationFactoryGeneratorTest extends TestCase
{
    use GeneratorTrait;
    use OperationTrait;

    private const NAMESPACE  = __NAMESPACE__ . '\\Foo\\Get';
    private const CLASS_NAME = __NAMESPACE__ . '\\Foo\\Get\\Operation';
    private const POINTER    = '/paths/foo/get';

    private OperationFactoryGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new OperationFactoryGenerator([
            DateTimeImmutable::class => __NAMESPACE__ . '\\DateTimeImmutableHydrator',
        ]);
    }

    public function testGenerateReturnsParserFile(): void
    {
        $expected = 'return new Operation();';

        $operation = new OperationModel(self::CLASS_NAME, self::POINTER, null, null, null, null, []);

        $file = $this->generator->generate($operation, []);
        self::assertTrue($file->hasStrictTypes());

        $namespace    = $this->getNamespace($file, self::NAMESPACE);
        $expectedUses = [
            'OpenApiOperationFactory'   => OpenApiOperationFactory::class,
            'OperationFactoryInterface' => OperationFactoryInterface::class,
            'ServerRequestInterface'    => ServerRequestInterface::class,
        ];
        $uses         = $namespace->getUses();
        self::assertSame($expectedUses, $uses);

        $class = $this->getClass($namespace, 'OperationFactory');
        self::assertSame([OperationFactoryInterface::class], $class->getImplements());
        self::assertTrue($class->isFinal());

        $attributes = $class->getAttributes();
        self::assertCount(1, $attributes);
        $attribute = $attributes[0];
        self::assertSame(OpenApiOperationFactory::class, $attribute->getName());
        self::assertSame([self::POINTER], $attribute->getArguments());

        self::assertFalse($class->hasMethod('__construct'));

        $method = $this->getMethod($class, 'getOperation');
        self::assertTrue($method->isPublic());
        self::assertSame($operation->getClassName(), $method->getReturnType());

        $parameters = $method->getParameters();
        self::assertCount(1, $parameters);
        self::assertArrayHasKey('request', $parameters);
        $parameter = $parameters['request'];
        self::assertSame(ServerRequestInterface::class, $parameter->getType());

        $actual = trim($method->getBody());
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testGenerateAddsParameterGetter(OperationModel $operation, string $type, string $template): void
    {
        $var           = '$' . $type;
        $paramName     = ucfirst($type) . 'Params';
        $utilMethod    = 'get' . ucfirst($type) . 'Variables';
        $getMethod     = 'get' . $paramName;
        $paramClass    = self::NAMESPACE . '\\' . $paramName;
        $hydratorName  = $paramName . 'Hydrator';
        $hydratorClass = $paramClass . 'Hydrator';

        $expected = <<<PARAMETER_GETTER
        $var = OperationUtil::$utilMethod(\$this->uriTemplate, $template, \$request);
        return $hydratorName::hydrate($var);
        PARAMETER_GETTER;

        $file        = $this->generator->generate($operation, [$paramClass => $hydratorClass]);
        $namespace   = $this->getNamespace($file, self::NAMESPACE);
        $class       = $this->getClass($namespace, 'OperationFactory');
        $constructor = $this->getMethod($class, '__construct');
        $getter      = $this->getMethod($class, $getMethod);
        $body        = $this->getGetOperationBody($file);

        $uses = $namespace->getUses();
        self::assertArrayHasKey('UriTemplate', $uses);

        self::assertTrue($class->hasProperty('uriTemplate'));
        $property = $class->getProperty('uriTemplate');
        self::assertTrue($property->isPrivate());
        self::assertSame(UriTemplate::class, $property->getType());

        self::assertSame('$this->uriTemplate = new UriTemplate();', trim($constructor->getBody()));

        self::assertSame($paramClass, $getter->getReturnType());
        $parameters = $getter->getParameters();
        self::assertArrayHasKey('request', $parameters);
        $parameter = $parameters['request'];
        self::assertSame(ServerRequestInterface::class, $parameter->getType());

        self::assertSame("return new Operation(\$this->$getMethod(\$request));", trim($body));
        self::assertSame($expected, trim($getter->getBody()));
    }

    public function parameterProvider(): Generator
    {
        $default   = [
            'pathParams'    => null,
            'queryParams'   => null,
            'headerParams'  => null,
            'cookieParams'  => null,
            'requestBodies' => [],
        ];
        $tests     = [
            'path'   => $this->getPathParams(self::NAMESPACE),
            'query'  => $this->getQueryParams(self::NAMESPACE),
            'header' => $this->getHeaderParams(self::NAMESPACE),
            'cookie' => $this->getCookieParams(self::NAMESPACE),
        ];
        $templates = [
            'path'   => "'{foo}'",
            'query'  => "'{?bar}'",
            'header' => "['X-Foo' => '{X-Foo}']",
            'cookie' => "['cook' => '{cook}']",
        ];

        foreach ($tests as $type => $param) {
            $args = array_merge($default, [$type . 'Params' => $param]);
            /** @psalm-suppress PossiblyInvalidArgument */
            yield $type => [new OperationModel(self::CLASS_NAME, self::POINTER, ...$args), $type, $templates[$type]];
        }
    }

    /**
     * @dataProvider doesNotConvertListToArrayProvider
     */
    public function testGenerateDoesNotConvertListToArray(PropertyInterface $property, string $template): void
    {
        $model     = new ClassModel(self::NAMESPACE . '\\PathParams', '/foo', [], $property);
        $param     = new PathOrQueryParams($template, $model);
        $hydrators = [self::NAMESPACE . '\\PathParams' => self::NAMESPACE . '\\PathParmsHydrator'];
        $operation = new OperationModel(self::CLASS_NAME, self::POINTER, $param, null, null, null, []);

        $file = $this->generator->generate($operation, $hydrators);
        $body = $this->getGetOperationBody($file);

        self::assertStringNotContainsString('OperationUtil::listToAssociativeArray', $body);
    }

    public function doesNotConvertListToArrayProvider(): array
    {
        $metadata = new PropertyMetadata();
        return [
            'array_property' => [new ArrayProperty('foo', 'foo', $metadata, true, PropertyType::String), '{foo}'],
            'string_type'    => [new SimpleProperty('foo', 'foo', $metadata, PropertyType::String), '{foo}'],
            'no_template'    => [new SimpleProperty('foo', 'foo', $metadata, new ClassString('\\Foo')), ''],
        ];
    }

    public function testGenerateConvertsListToArray(): void
    {
        $expected = "\$path['foo'] = OperationUtil::listToAssociativeArray(\$path['foo']);";

        $property  = new SimpleProperty('foo', 'foo', new PropertyMetadata(), new ClassString('\\Foo'));
        $model     = new ClassModel(self::NAMESPACE . '\\PathParams', '/foo', [], $property);
        $param     = new PathOrQueryParams('{foo}', $model);
        $hydrators = [self::NAMESPACE . '\\PathParams' => self::NAMESPACE . '\\PathParmsHydrator'];
        $operation = new OperationModel(self::CLASS_NAME, self::POINTER, $param, null, null, null, []);

        $file   = $this->generator->generate($operation, $hydrators);
        $class  = $this->getClass($this->getNamespace($file, self::NAMESPACE), 'OperationFactory');
        $getter = $this->getMethod($class, 'getPathParams');

        self::assertStringContainsString($expected, trim($getter->getBody()));
    }

    public function testGenerateAddsNegotiator(): void
    {
        $expected = "\$this->negotiator = new ContentTypeNegotiator(['application/json', '*/*']);";

        $requestBodies = [
            new RequestBodyModel(
                'application/json',
                new SimpleProperty('', '', new PropertyMetadata(), new ClassString(__NAMESPACE__ . '\\Foo'))
            ),
            new RequestBodyModel(
                '*/*',
                new SimpleProperty('', '', new PropertyMetadata(), PropertyType::String)
            ),
        ];

        $operation = new OperationModel(self::CLASS_NAME, self::POINTER, null, null, null, null, $requestBodies);
        $hydrators = [__NAMESPACE__ . '\\Foo' => __NAMESPACE__ . '\\FooHydrator'];

        $file        = $this->generator->generate($operation, $hydrators);
        $namespace   = $this->getNamespace($file, self::NAMESPACE);
        $class       = $this->getClass($namespace, 'OperationFactory');
        $constructor = $this->getMethod($class, '__construct');

        $uses = $namespace->getUses();
        self::assertArrayHasKey('ContentTypeNegotiator', $uses);

        self::assertTrue($class->hasProperty('negotiator'));
        $property = $class->getProperty('negotiator');
        self::assertTrue($property->isPrivate());
        self::assertSame(ContentTypeNegotiator::class, $property->getType());

        $body = trim($constructor->getBody());
        self::assertSame($expected, $body);
    }

    /**
     * @dataProvider requestBodyParserProvider
     * @param array<string, string> $hydrators
     */
    public function testGenerateAddsRequestBodyParser(
        RequestBodyModel $requestBody,
        string $returnType,
        array $hydrators,
        string $return
    ): void {
        $mimeType = $requestBody->getMimeType();
        $expected = <<<END_OF_REQUEST_BODY_PARSER
        \$body     = \$request->getParsedBody() ?? (string) \$request->getBody();
        \$mimeType = \$this->negotiator->negotiate(\$request);
        
        return match (\$mimeType) {
            '$mimeType' => $return,
            default => throw InvalidContentTypeException::fromExpected(\$mimeType, \$this->negotiator->getMimeTypes()),
        };
        END_OF_REQUEST_BODY_PARSER;

        $operation = new OperationModel(self::CLASS_NAME, self::POINTER, null, null, null, null, [$requestBody]);

        $file      = $this->generator->generate($operation, $hydrators);
        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $class     = $this->getClass($namespace, 'OperationFactory');
        $method    = $this->getMethod($class, 'getRequestBody');

        self::assertTrue($method->isPrivate());
        self::assertSame($returnType, $method->getReturnType());
        $parameters = $method->getParameters();
        self::assertArrayHasKey('request', $parameters);
        $parameter = $parameters['request'];
        self::assertSame(ServerRequestInterface::class, $parameter->getType());

        self::assertSame($expected, trim($method->getBody()));

        $parse = $this->getGetOperationBody($file);

        self::assertSame("return new Operation(\$this->getRequestBody(\$request));", trim($parse));
    }

    public function requestBodyParserProvider(): array
    {
        $class            = 'Foo';
        $arrayRequestBody = new RequestBodyModel(
            '*/*',
            new ArrayProperty('', '', new PropertyMetadata(), true, PropertyType::String)
        );
        $arrayReturn      = '(array) $body';
        $arrayType        = 'array';

        $simpleClassRequestBody = new RequestBodyModel(
            '*/*',
            new SimpleProperty('', '', new PropertyMetadata(), new ClassString(__NAMESPACE__ . '\\Foo'))
        );
        $simpleClassReturn      = 'FooHydrator::hydrate($body)';
        $simpleClassType        = __NAMESPACE__ . '\\Foo';
        $classHydrators         = [__NAMESPACE__ . '\\' . $class => __NAMESPACE__ . '\\' . $class . 'Hydrator'];

        $simplePhpTypeRequestBody = new RequestBodyModel(
            '*/*',
            new SimpleProperty('', '', new PropertyMetadata(), PropertyType::Integer)
        );
        $simplePhpTypeReturn      = '(int) $body';
        $simplePhpType            = 'int';

        $propertyValueRequestBody = new RequestBodyModel(
            '*/*',
            new UnionProperty(
                '',
                '',
                new PropertyMetadata(),
                new PropertyValue('foo', ['a' => __NAMESPACE__ . '\\Foo', 'b' => __NAMESPACE__ . '\\Bar']),
                new ClassString(__NAMESPACE__ . '\\Foo'),
                new ClassString(__NAMESPACE__ . '\\Bar')
            )
        );
        $propertyValueReturn      = <<<PROPERTY_VALUE_CALLBACK
        HydratorUtil::hydrateDiscriminatorValue('requestBody', \$body, [
                'key' => 'foo',
                'map' => [
                    'a' => FooHydrator::class,
                    'b' => BarHydrator::class,
                ],
            ])
        PROPERTY_VALUE_CALLBACK;
        $propertyType             = __NAMESPACE__ . '\\Foo|' . __NAMESPACE__ . '\\Bar';
        $propertyHydrators        = [
            __NAMESPACE__ . '\\Foo' => __NAMESPACE__ . '\\FooHydrator',
            __NAMESPACE__ . '\\Bar' => __NAMESPACE__ . '\\BarHydrator',
        ];

        $propertyListRequestBody = new RequestBodyModel(
            '*/*',
            new UnionProperty(
                '',
                '',
                new PropertyMetadata(),
                new PropertyList([__NAMESPACE__ . '\\Foo' => ['a'], __NAMESPACE__ . '\\Bar' => ['b']]),
                new ClassString(__NAMESPACE__ . '\\Foo'),
                new ClassString(__NAMESPACE__ . '\\Bar')
            )
        );
        $propertyListReturn      = <<<PROPERTY_LIST_CALLBACK
        HydratorUtil::hydrateDiscriminatorList('requestBody', \$body, [
                FooHydrator::class => ['a'],
                BarHydrator::class => ['b'],
            ])
        PROPERTY_LIST_CALLBACK;

        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'default'              => [$arrayRequestBody, $arrayType, [], $arrayReturn],
            'simple_class'         => [$simpleClassRequestBody, $simpleClassType, $classHydrators, $simpleClassReturn],
            'simple_php_type'      => [$simplePhpTypeRequestBody, $simplePhpType, [], $simplePhpTypeReturn],
            'union_property_value' => [$propertyValueRequestBody, $propertyType, $propertyHydrators, $propertyValueReturn],
            'union_property_list'  => [$propertyListRequestBody, $propertyType, $propertyHydrators, $propertyListReturn],
        ];
        // phpcs:enable
    }

    private function getGetOperationBody(PhpFile $file): string
    {
        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $class     = $this->getClass($namespace, 'OperationFactory');
        $method    = $this->getMethod($class, 'getOperation');
        return $method->getBody();
    }
}

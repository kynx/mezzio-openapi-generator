<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation\Generator;

use DateTimeImmutable;
use Generator;
use Kynx\Mezzio\OpenApi\Attribute\OpenApiRequestParser;
use Kynx\Mezzio\OpenApi\Operation\OperationUtil;
use Kynx\Mezzio\OpenApi\Operation\RequestBody\MediaTypeMatcher;
use Kynx\Mezzio\OpenApiGenerator\GeneratorUtil;
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
use Kynx\Mezzio\OpenApiGenerator\Operation\Generator\RequestParserGenerator;
use Kynx\Mezzio\OpenApiGenerator\Operation\OperationModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use Kynx\Mezzio\OpenApiGenerator\Operation\RequestBodyModel;
use KynxTest\Mezzio\OpenApiGenerator\GeneratorTrait;
use KynxTest\Mezzio\OpenApiGenerator\Operation\OperationTrait;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PromotedParameter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

use function array_merge;
use function trim;
use function ucfirst;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\Generator\RequestParserGenerator
 */
final class RequestParserGeneratorTest extends TestCase
{
    use GeneratorTrait;
    use OperationTrait;

    private const NAMESPACE  = __NAMESPACE__ . '\\Foo\\Get';
    private const CLASS_NAME = __NAMESPACE__ . '\\Foo\\Get\\Operation';
    private const POINTER    = '/paths/foo/get';

    private RequestParserGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new RequestParserGenerator([
            DateTimeImmutable::class => __NAMESPACE__ . '\\DateTimeImmutableHydrator',
        ]);
    }

    public function testGenerateReturnsParserFile(): void
    {
        $expected = <<<PARSER_BODY
        \$params = [];
        
        return new Operation(...\$params);
        PARSER_BODY;

        $operation = new OperationModel(self::CLASS_NAME, self::POINTER, null, null, null, null, []);

        $file = $this->generator->generate($operation, []);
        self::assertTrue($file->hasStrictTypes());

        $namespace    = $this->getNamespace($file, self::NAMESPACE);
        $expectedUses = [
            'OpenApiRequestParser'   => OpenApiRequestParser::class,
            'OperationUtil'          => OperationUtil::class,
            'ServerRequestInterface' => ServerRequestInterface::class,
        ];
        $uses         = $namespace->getUses();
        self::assertSame($expectedUses, $uses);

        $class = $this->getClass($namespace, 'RequestParser');
        self::assertTrue($class->isFinal());

        $attributes = $class->getAttributes();
        self::assertCount(1, $attributes);
        $attribute = $attributes[0];
        self::assertSame(OpenApiRequestParser::class, $attribute->getName());
        self::assertSame([self::POINTER], $attribute->getArguments());

        self::assertFalse($class->hasMethod('__construct'));

        $method = $this->getMethod($class, 'parse');
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
    public function testGenerateAddsParameterHydrator(OperationModel $operation, string $type, string $template): void
    {
        $var           = '$' . $type;
        $getMethod     = 'get' . ucfirst($type) . 'Variables';
        $key           = $type . 'Params';
        $paramClass    = self::NAMESPACE . '\\' . ucfirst($type) . 'Params';
        $hydratorClass = $paramClass . 'Hydrator';
        $hydratorName  = ucfirst($type) . 'ParamsHydrator';

        $file      = $this->generator->generate($operation, [$paramClass => $hydratorClass]);
        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $body      = $this->getParseMethodBody($file);

        $uses = $namespace->getUses();
        self::assertArrayHasKey('UriTemplate', $uses);

        self::assertStringContainsString('$uriTemplate = new UriTemplate();', $body);

        $varAssignment   = "$var = OperationUtil::$getMethod(\$uriTemplate, $template, \$request);";
        $paramAssignment = "\$params['$key'] = $hydratorName::hydrate($var);";
        self::assertStringContainsString($varAssignment . "\n" . $paramAssignment, $body);
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
        $body = $this->getParseMethodBody($file);

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

        $file = $this->generator->generate($operation, $hydrators);
        $body = $this->getParseMethodBody($file);

        self::assertStringContainsString($expected, $body);
    }

    /**
     * @dataProvider requestBodyCallbackProvider
     * @param array<string, string> $hydrators
     */
    public function testGenerateAddsRequestBodyCallback(
        RequestBodyModel $requestBody,
        array $hydrators,
        string $callback
    ): void {
        $expected = <<<CONSTRUCTOR
        \$this->bodyParsers = [
        $callback
        ];
        CONSTRUCTOR;

        $operation = new OperationModel(self::CLASS_NAME, self::POINTER, null, null, null, null, [$requestBody]);

        $file        = $this->generator->generate($operation, $hydrators);
        $namespace   = $this->getNamespace($file, self::NAMESPACE);
        $class       = $this->getClass($namespace, 'RequestParser');
        $constructor = $this->getMethod($class, '__construct');

        $uses = $namespace->getUses();
        self::assertArrayHasKey('MediaTypeMatcher', $uses);
        foreach ($hydrators as $hydrator) {
            self::assertArrayHasKey(GeneratorUtil::getClassName($hydrator), $uses);
        }

        $parameters = $constructor->getParameters();
        self::assertArrayHasKey('requestBodyMatcher', $parameters);
        $parameter = $parameters['requestBodyMatcher'];
        self::assertInstanceOf(PromotedParameter::class, $parameter);
        self::assertSame(MediaTypeMatcher::class, $parameter->getType());
        self::assertTrue($parameter->isPrivate());
        self::assertTrue($parameter->isReadOnly());

        $body = trim($constructor->getBody());
        self::assertSame($expected, $body);
    }

    public function requestBodyCallbackProvider(): array
    {
        $class            = 'Foo';
        $arrayRequestBody = new RequestBodyModel(
            'default',
            new ArrayProperty('', '', new PropertyMetadata(), true, PropertyType::String)
        );
        $arrayCallback    = <<<ARRAY_CALLBACK
            'default' => function (mixed \$body): array {
                return (array) \$body;
            },
        ARRAY_CALLBACK;

        $simpleClassRequestBody = new RequestBodyModel(
            'default',
            new SimpleProperty('', '', new PropertyMetadata(), new ClassString(__NAMESPACE__ . '\\Foo'))
        );
        $simpleClassCallback    = <<<SIMPLE_CLASS_CALLBACK
            'default' => function (array \$body): Foo {
                return FooHydrator::hydrate(\$body);
            },
        SIMPLE_CLASS_CALLBACK;
        $classHydrators         = [__NAMESPACE__ . '\\' . $class => __NAMESPACE__ . '\\' . $class . 'Hydrator'];

        $simplePhpClassRequestBody = new RequestBodyModel(
            'default',
            new SimpleProperty('', '', new PropertyMetadata(), PropertyType::DateTime)
        );
        $simplePhpClassCallback    = <<<SIMPLE_PHP_CLASS_CALLBACK
            'default' => function (string \$body): DateTimeImmutable {
                return DateTimeImmutableHydrator::hydrate(\$body);
            },
        SIMPLE_PHP_CLASS_CALLBACK;
        $dateHydrators             = [DateTimeImmutable::class => __NAMESPACE__ . '\\DateTimeImmutableHydrator'];

        $simplePhpTypeRequestBody = new RequestBodyModel(
            'default',
            new SimpleProperty('', '', new PropertyMetadata(), PropertyType::Integer)
        );
        $simplePhpTypeCallback    = <<<SIMPLE_PHP_TYPE_CALLBACK
            'default' => function (string \$body): int {
                return (int) \$body;
            },
        SIMPLE_PHP_TYPE_CALLBACK;

        $propertyValueRequestBody = new RequestBodyModel(
            'default',
            new UnionProperty(
                '',
                '',
                new PropertyMetadata(),
                new PropertyValue('foo', ['a' => __NAMESPACE__ . '\\Foo', 'b' => __NAMESPACE__ . '\\Bar']),
                new ClassString(__NAMESPACE__ . '\\Foo')
            )
        );
        $propertyValueCallback    = <<<PROPERTY_VALUE_CALLBACK
            'default' => function (array \$body): Foo|Bar {
                return HydratorUtil::hydrateDiscriminatorValue('requestBody', \$body, [
                    'key' => 'foo',
                    'map' => [
                        'a' => FooHydrator::class,
                        'b' => BarHydrator::class,
                    ],
                ]);
            },
        PROPERTY_VALUE_CALLBACK;
        $propertyHydrators        = [
            __NAMESPACE__ . '\\Foo' => __NAMESPACE__ . '\\FooHydrator',
            __NAMESPACE__ . '\\Bar' => __NAMESPACE__ . '\\BarHydrator',
        ];

        $propertyListRequestBody = new RequestBodyModel(
            'default',
            new UnionProperty(
                '',
                '',
                new PropertyMetadata(),
                new PropertyList([__NAMESPACE__ . '\\Foo' => ['a'], __NAMESPACE__ . '\\Bar' => ['b']]),
                new ClassString(__NAMESPACE__ . '\\Foo')
            )
        );
        $propertyListCallback    = <<<PROPERTY_LIST_CALLBACK
            'default' => function (array \$body): Foo|Bar {
                return HydratorUtil::hydrateDiscriminatorList('requestBody', \$body, [
                    FooHydrator::class => ['a'],
                    BarHydrator::class => ['b'],
                ]);
            },
        PROPERTY_LIST_CALLBACK;

        return [
            'default'              => [$arrayRequestBody, [], $arrayCallback],
            'simple_class'         => [$simpleClassRequestBody, $classHydrators, $simpleClassCallback],
            'simple_php_class'     => [$simplePhpClassRequestBody, $dateHydrators, $simplePhpClassCallback],
            'simple_php_type'      => [$simplePhpTypeRequestBody, [], $simplePhpTypeCallback],
            'union_property_value' => [$propertyValueRequestBody, $propertyHydrators, $propertyValueCallback],
            'union_property_list'  => [$propertyListRequestBody, $propertyHydrators, $propertyListCallback],
        ];
    }

    public function testGenerateAddsRequestBodyParser(): void
    {
        $expected = <<<END_OF_REQUEST_BODY_PARSER
        \$parser   = \$this->requestBodyMatcher->getParser(\$request);
        \$body     = \$parser->parse(\$request);
        \$callback = \$this->bodyParsers[\$parser->getMimeType()] ?? null;
        assert(is_callable(\$callback));
        \$params['requestBody'] = \$callback(\$body);
        END_OF_REQUEST_BODY_PARSER;

        $property    = new SimpleProperty('foo', 'foo', new PropertyMetadata(), new ClassString('\\Foo'));
        $requestBody = new RequestBodyModel(
            'default',
            $property
        );
        $hydrators   = ['\\Foo' => '\\FooHydrator'];
        $operation   = new OperationModel(self::CLASS_NAME, self::POINTER, null, null, null, null, [$requestBody]);

        $file = $this->generator->generate($operation, $hydrators);
        $body = $this->getParseMethodBody($file);

        self::assertStringContainsString($expected, $body);
    }

    private function getParseMethodBody(PhpFile $file): string
    {
        $namespace = $this->getNamespace($file, self::NAMESPACE);
        $class     = $this->getClass($namespace, 'RequestParser');
        $method    = $this->getMethod($class, 'parse');
        return $method->getBody();
    }
}

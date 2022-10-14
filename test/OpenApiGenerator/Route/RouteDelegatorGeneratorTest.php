<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use cebe\openapi\Reader;
use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Mezzio\OpenApiGenerator\Handler\FlatNamer;
use Kynx\Mezzio\OpenApiGenerator\Handler\OpenApiLocator;
use Kynx\Mezzio\OpenApiGenerator\Route\DotSnakeCaseNamer;
use Kynx\Mezzio\OpenApiGenerator\Route\FastRouteConverter;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGenerator;
use Kynx\Mezzio\OpenApiGenerator\Stub\RouteDelegator;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Reflection\ClassReflection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGenerator
 */
final class RouteDelegatorGeneratorTest extends TestCase
{
    private const NAMESPACE = __NAMESPACE__ . '\\Asset';

    private RouteDelegatorGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new RouteDelegatorGenerator(new FastRouteConverter(), new DotSnakeCaseNamer('api'));
    }

    public function testGenerateCreatesRoutes(): void
    {
        $openApi = Reader::readFromYamlFile(__DIR__ . '/Asset/route-delegator.yaml');
        $locator = new OpenApiLocator(
            $openApi,
            new FlatNamer(self::NAMESPACE, new ClassNameNormalizer('Handler'))
        );
        $handlers = $locator->create();
        $reflection = new ClassReflection(RouteDelegator::class);

        $generator = ClassGenerator::fromReflection($reflection);
        $generator->setNamespaceName(self::NAMESPACE);
        $generator->setFinal(true);

        $expected = trim($this->getExpectedCode());
        $actual = trim($this->generator->generate($openApi, $handlers, $generator));

        self::assertSame($expected, $actual);
    }

    private function getExpectedCode(): string
    {
        return <<<EndOfExpected
        namespace KynxTest\Mezzio\OpenApiGenerator\Route\Asset;
        
        final class RouteDelegator
        {
            public function __invoke(\Psr\Container\ContainerInterface \$container, string \$serviceName, callable \$callback) : \Mezzio\Application
            {
                \$app = \$callback();
                assert(\$app instanceof \Mezzio\Application::class);
        
                \$app->get('/test-get/{testId:\d+}', \KynxTest\Mezzio\OpenApiGenerator\Route\Asset\TestGetTestIdGet::class, 'api.test-get.test_id.get')->setOptions([\Kynx\Mezzio\OpenApi\RouteOptionInterface::PATH => '/test-get/{testId}']);
                \$app->get('/test-multi', \KynxTest\Mezzio\OpenApiGenerator\Route\Asset\GetMulti::class, 'api.get_multi')->setOptions([\Kynx\Mezzio\OpenApi\RouteOptionInterface::PATH => '/test-multi']);
                \$app->post('/test-multi', \KynxTest\Mezzio\OpenApiGenerator\Route\Asset\PostMulti::class, 'api.post_multi')->setOptions([\Kynx\Mezzio\OpenApi\RouteOptionInterface::PATH => '/test-multi']);
        
                return \$app;
            }
        }
        EndOfExpected;
    }
}

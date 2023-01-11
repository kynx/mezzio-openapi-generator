<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Route;

use cebe\openapi\Reader;
use Kynx\Code\Normalizer\ClassNameNormalizer;
use Kynx\Code\Normalizer\UniqueClassLabeler;
use Kynx\Code\Normalizer\UniqueStrategy\NumberSuffix;
use Kynx\Mezzio\OpenApiGenerator\Handler\Namer\FlatNamer;
use Kynx\Mezzio\OpenApiGenerator\Handler\OpenApiParser;
use Kynx\Mezzio\OpenApiGenerator\Route\Converter\FastRouteConverter;
use Kynx\Mezzio\OpenApiGenerator\Route\Namer\DotSnakeCaseNamer;
use Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGenerator;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function trim;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Route\RouteDelegatorGenerator
 */
final class RouteDelegatorGeneratorTest extends TestCase
{
    private const NAMESPACE = __NAMESPACE__ . '\\Asset';

    private RouteDelegatorGenerator $generator;

    protected function setUp(): void
    {
        $this->markTestSkipped("Awaiting refactor");
        parent::setUp();

        $this->generator = new RouteDelegatorGenerator(new FastRouteConverter(), new DotSnakeCaseNamer('api'));
    }

    public function testGenerateCreatesRoutes(): void
    {
        $openApi = Reader::readFromYamlFile(__DIR__ . '/Asset/route-delegator.yaml');
        self::assertTrue($openApi->validate(), "Invalid openapi schema");

        $labeler  = new UniqueClassLabeler(new ClassNameNormalizer('Handler'), new NumberSuffix());
        $locator  = new OpenApiParser(
            $openApi,
            new FlatNamer(self::NAMESPACE, $labeler)
        );
        $handlers = $locator->getHandlerCollection();

        $file = PhpFile::fromCode(file_get_contents('src/OpenApiGenerator/Stub/RouteDelegator.php'));

        $expected  = trim($this->getExpectedCode());
        $generated = $this->generator->generate($handlers, $file);
        $actual    = trim((new PsrPrinter())->printFile($generated));

        self::assertSame($expected, $actual);
    }

    private function getExpectedCode(): string
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return <<<EXPECTED
        <?php
        
        declare(strict_types=1);
        
        namespace Kynx\Mezzio\OpenApiGenerator\Stub;
        
        use Kynx\Mezzio\OpenApi\RouteOptionInterface;
        use KynxTest\Mezzio\OpenApiGenerator\Route\Asset\GetMulti;
        use KynxTest\Mezzio\OpenApiGenerator\Route\Asset\PostMulti;
        use KynxTest\Mezzio\OpenApiGenerator\Route\Asset\TestGetTestIdGet;
        use Mezzio\Application;
        use Psr\Container\ContainerInterface;
        
        use function assert;
        
        final class RouteDelegator
        {
            public function __invoke(ContainerInterface \$container, string \$serviceName, callable \$callback): Application
            {
                \$app = \$callback();
                assert(\$app instanceof Application);
        
                \$app->get('/test-get/{testId:\d+}', TestGetTestIdGet::class, 'api.test-get.test_id.get')
                    ->setOptions([RouteOptionInterface::PATH => '/test-get/{testId}']);
                \$app->get('/test-multi', GetMulti::class, 'api.get_multi')
                    ->setOptions([RouteOptionInterface::PATH => '/test-multi']);
                \$app->post('/test-multi', PostMulti::class, 'api.post_multi')
                    ->setOptions([RouteOptionInterface::PATH => '/test-multi']);
        
                return \$app;
            }
        }
        EXPECTED;
        // phpcs:enable
    }
}

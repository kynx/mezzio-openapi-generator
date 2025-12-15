<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Security;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Paths;
use Kynx\Mezzio\OpenApiGenerator\Security\ApiKeySecurityModel;
use Kynx\Mezzio\OpenApiGenerator\Security\BasicSecurityModel;
use Kynx\Mezzio\OpenApiGenerator\Security\SecurityModelInterface;
use Kynx\Mezzio\OpenApiGenerator\Security\SecurityModelResolver;
use Kynx\Mezzio\OpenApiGenerator\Security\UnsupportedSecurityRequirementException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function implode;

#[CoversClass(SecurityModelResolver::class)]
final class SecurityModelResolverTest extends TestCase
{
    public function testConstructUnsupportedSchemeThrowsException(): void
    {
        $openApi = $this->getOpenApi('unsupported.yaml');
        $this->expectException(UnsupportedSecurityRequirementException::class);
        $this->expectExceptionMessage("Security requirement 'openIdConnect' is not supported");
        new SecurityModelResolver($openApi);
    }

    public function testResolveNoSecuritySchemeReturnsNull(): void
    {
        $this->assertSecuritySchemeMatches('no-global.yaml', '/none', null);
    }

    public function testResolveEmptySecuritySchemeReturnsNull(): void
    {
        $this->assertSecuritySchemeMatches('no-global.yaml', '/empty', null);
    }

    public function testResolveReturnsSecurityScheme(): void
    {
        $expected = new BasicSecurityModel('bearer');
        $this->assertSecuritySchemeMatches('no-global.yaml', '/basic', $expected);
    }

    public function testResolveMultipleSchemesThrowsException(): void
    {
        $this->expectException(UnsupportedSecurityRequirementException::class);
        $this->expectExceptionMessage('Multiple security requirements are not supported');
        $this->assertSecuritySchemeMatches('no-global.yaml', '/multiple', null);
    }

    public function testResolveMissingSchemeThrowsException(): void
    {
        $this->expectException(UnsupportedSecurityRequirementException::class);
        $this->expectExceptionMessage("Security scheme name 'oauth' does not exist");
        $this->assertSecuritySchemeMatches('no-global.yaml', '/missing-scheme', null);
    }

    public function testResolveReturnsGlobalSecurityScheme(): void
    {
        $expected = new ApiKeySecurityModel('x-api-key');
        $this->assertSecuritySchemeMatches('global.yaml', '/test', $expected);
    }

    public function testResolveWithGlobalReturnsNoSecurityScheme(): void
    {
        $this->assertSecuritySchemeMatches('global.yaml', '/none', null);
    }

    public function testResolveOverridesGlobalSecurityScheme(): void
    {
        $expected = new BasicSecurityModel('bearer', ['admin']);
        $this->assertSecuritySchemeMatches('global.yaml', '/override', $expected);
    }

    public function getOpenApi(string $file): OpenApi
    {
        $openApi = Reader::readFromYamlFile(__DIR__ . '/Asset/' . $file);
        self::assertTrue($openApi->validate(), "Invalid openapi schema: " . implode("\n", $openApi->getErrors()));
        return $openApi;
    }

    public function assertSecuritySchemeMatches(string $spec, string $path, ?SecurityModelInterface $expected): void
    {
        $openApi  = $this->getOpenApi($spec);
        $resolver = new SecurityModelResolver($openApi);
        $paths    = $openApi->paths;
        self::assertInstanceOf(Paths::class, $paths);
        $operation = $paths->getPath($path)?->getOperations()['get'];
        self::assertInstanceOf(Operation::class, $operation);
        $security = $operation->security;
        $actual   = $resolver->resolve($security);
        if ($expected === null) {
            self::assertNull($actual);
        } else {
            self::assertEquals($expected, $actual);
        }
    }
}

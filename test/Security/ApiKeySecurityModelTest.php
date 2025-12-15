<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Security;

use Kynx\Mezzio\Authentication\ApiKey\ApiKeyAuthentication;
use Kynx\Mezzio\OpenApiGenerator\Security\ApiKeySecurityModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiKeySecurityModel::class)]
final class ApiKeySecurityModelTest extends TestCase
{
    public function testConstructorSetsScopes(): void
    {
        $expected = ['foo', 'bar'];
        $model    = new ApiKeySecurityModel('api-key', $expected);
        $actual   = $model->getScopes();
        self::assertSame($expected, $actual);
    }

    public function testGetAuthenticationAdapter(): void
    {
        $model  = new ApiKeySecurityModel('api-key');
        $actual = $model->getAuthenticationAdapter();
        self::assertSame(ApiKeyAuthentication::class, $actual);
    }

    public function testWithScopesReturnsNewInstance(): void
    {
        $expected = ['foo', 'bar'];
        $model    = new ApiKeySecurityModel('api-key', []);
        $new      = $model->withScopes($expected);
        $actual   = $new->getScopes();
        self::assertNotSame($model, $new);
        self::assertSame($expected, $actual);
    }
}

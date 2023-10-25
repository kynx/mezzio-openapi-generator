<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Security;

use Kynx\Mezzio\OpenApiGenerator\Security\BasicSecurityModel;
use Mezzio\Authentication\Basic\BasicAccess;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Security\BasicSecurityModel
 */
final class BasicSecurityModelTest extends TestCase
{
    public function testConstructorSetsScopes(): void
    {
        $expected = ['foo', 'bar'];
        $model    = new BasicSecurityModel('bearer', $expected);
        $actual   = $model->getScopes();
        self::assertSame($expected, $actual);
    }

    public function testGetAuthenticationAdapter(): void
    {
        $model  = new BasicSecurityModel('bearer', []);
        $actual = $model->getAuthenticationAdapter();
        self::assertSame(BasicAccess::class, $actual);
    }

    public function testWithScopesReturnsNewInstance(): void
    {
        $expected = ['foo', 'bar'];
        $model    = new BasicSecurityModel('bearer', []);
        $new      = $model->withScopes($expected);
        $actual   = $new->getScopes();
        self::assertNotSame($model, $new);
        self::assertSame($expected, $actual);
    }
}

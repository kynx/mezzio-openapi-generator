<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParams;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kynx\Mezzio\OpenApiGenerator\Operation\CookieOrHeaderParams
 */
final class CookieOrHeaderParamsTest extends TestCase
{
    public function testConstructorSetsParams(): void
    {
        $templates = ['{foo}', '{bar*}'];
        $model     = new ClassModel('\\Foo', '/components/schemas/foo', []);

        $params = new CookieOrHeaderParams($templates, $model);
        self::assertSame($templates, $params->getTemplates());
        self::assertSame($model, $params->getModel());
    }
}

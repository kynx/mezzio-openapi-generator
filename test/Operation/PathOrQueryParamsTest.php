<?php

declare(strict_types=1);

namespace KynxTest\Mezzio\OpenApiGenerator\Operation;

use Kynx\Mezzio\OpenApiGenerator\Model\ClassModel;
use Kynx\Mezzio\OpenApiGenerator\Operation\PathOrQueryParams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathOrQueryParams::class)]
final class PathOrQueryParamsTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $template = '{foo}';
        $model    = new ClassModel('\\Foo', '/paths/foo/get/parameters/query', []);
        $params   = new PathOrQueryParams($template, $model);

        self::assertSame($template, $params->getTemplate());
        self::assertSame($model, $params->getModel());
    }
}

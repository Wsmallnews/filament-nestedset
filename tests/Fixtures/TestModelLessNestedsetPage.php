<?php

namespace Wsmallnews\FilamentNestedset\Tests\Fixtures;

use Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage;

/**
 * 未设置模型的页面夹具：mount 阶段必须被 validateModel() 拦下。
 */
class TestModelLessNestedsetPage extends NestedsetPage
{
    protected static bool $isScopedToTenant = false;
}

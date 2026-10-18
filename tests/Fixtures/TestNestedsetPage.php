<?php

namespace Wsmallnews\FilamentNestedset\Tests\Fixtures;

use Filament\Forms;
use Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage;

/**
 * NestedsetPage 测试夹具子类：演示调用方的标准接入方式
 * （静态模型 + 嵌套集 scope + schema 覆写）。
 */
class TestNestedsetPage extends NestedsetPage
{
    protected static ?string $model = TestCategory::class;

    protected static bool $isScopedToTenant = false;

    protected static ?int $level = null;

    protected static ?string $tabFieldName = null;

    protected function nestedScoped(): array
    {
        return ['scope_type' => 'test', 'scope_id' => 0];
    }

    /**
     * @return array<mixed>
     */
    protected function schema(array $arguments): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label(__('sn-filament-nestedset::nestedset.field.name'))
                ->required(),
        ];
    }
}

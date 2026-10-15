<?php

namespace Wsmallnews\FilamentNestedset\Tests\Fixtures;

use Filament\Forms;
use Wsmallnews\FilamentNestedset\Filament\Components\SidebarNestedset;

/**
 * SidebarNestedset 测试夹具子类：演示调用方的标准接入方式
 * （静态模型 + 层级 + 嵌套集 scope + schema 覆写，行操作默认收进 ActionGroup）。
 */
class TestSidebarTree extends SidebarNestedset
{
    protected static ?string $model = TestCategory::class;

    protected static ?int $level = 3;

    protected function nestedScoped(): array
    {
        return ['scope_type' => 'test', 'scope_id' => 0];
    }

    /**
     * 创建/编辑共用表单（子类必须实现，与 page 契约一致——空 schema 的 action 不以 modal 挂起）
     *
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

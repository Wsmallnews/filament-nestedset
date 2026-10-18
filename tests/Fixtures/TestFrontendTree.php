<?php

namespace Wsmallnews\FilamentNestedset\Tests\Fixtures;

use Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset;

/**
 * 前台 Nestedset 组件夹具：只声明嵌套集 scope。
 *
 * newScopedQuery() 用模型自身的 scope 属性做过滤，未声明 scope 时（属性为空）树查询恒为空，
 * 因此前台组件必须经 nestedScoped() 或 getEloquentQuery() 给出分区。
 * model 由挂载参数传入（$model 是 #[Locked]，挂载期赋值仍然有效）。
 */
class TestFrontendTree extends Nestedset
{
    protected function nestedScoped(): array
    {
        return ['scope_type' => 'test', 'scope_id' => 0];
    }
}

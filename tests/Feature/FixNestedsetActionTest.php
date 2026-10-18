<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NestedSet;
use Livewire\Livewire;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestCategory;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestNestedsetPage;

function fixNode(string $name, ?TestCategory $parent = null): TestCategory
{
    return TestCategory::create([
        'name' => $name,
        'scope_type' => 'test',
        'scope_id' => 0,
        'parent_id' => $parent?->getKey(),
    ]);
}

/**
 * 手工破坏嵌套集边界（模拟批量导入/直接 SQL 写入留下的脏数据）
 */
function corruptTree(): void
{
    DB::table('test_categories')->update([NestedSet::LFT => 0, NestedSet::RGT => 0]);
}

test('fix action repairs a broken tree', function () {
    $root = fixNode('Fix Root');
    fixNode('Fix Child', $root);
    corruptTree();

    expect(TestCategory::scoped(['scope_type' => 'test', 'scope_id' => 0])->isBroken())->toBeTrue();

    Livewire::test(TestNestedsetPage::class)
        ->callAction('fixNestedset');

    expect(TestCategory::scoped(['scope_type' => 'test', 'scope_id' => 0])->isBroken())->toBeFalse();
});

test('fix action dispatches the updated event only after the repair', function () {
    fixNode('Healthy');

    Livewire::test(TestNestedsetPage::class)
        ->callAction('fixNestedset')
        ->assertDispatched('sn-filament-nestedset-updated');
});

test('fix action does not announce an update when the repair fails', function () {
    // 只让修复动作走坏的查询：渲染走空的 getNestedset()，这样能单独观察失败分支
    $broken = new class extends TestNestedsetPage
    {
        protected function getNestedset()
        {
            return collect();
        }

        protected function getQuery(): Builder
        {
            throw new RuntimeException('fixTree exploded');
        }
    };

    Livewire::test($broken)
        ->callAction('fixNestedset')
        ->assertNotDispatched('sn-filament-nestedset-updated');
});

<?php

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\View\ViewException;
use Livewire\Livewire;
use Wsmallnews\FilamentNestedset\Filament\Components\SidebarNestedset;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestCategory;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestSidebarTree;

function sidebarTree(array $props = [])
{
    return Livewire::test(TestSidebarTree::class, $props);
}

// 带深度的记录（withDepth 的行为与组件视图一致；shouldShowCreateChildAction 依赖 depth）
function withDepthNode(int $id)
{
    return TestCategory::scoped(['scope_type' => 'test', 'scope_id' => 0])->withDepth()->findOrFail($id);
}

// ============ 渲染（循环嵌套树） ============

test('renders a nested tree with indentation containers', function () {
    $root = TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);
    $child = TestCategory::create(['name' => 'Child A1', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $root->id]);
    TestCategory::create(['name' => 'Grandchild', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $child->id]);
    TestCategory::create(['name' => 'Root B', 'scope_type' => 'test', 'scope_id' => 0]);

    // 树数据：父子层级正确（getNestedset 为 protected，按视图同语境反射调用）
    $instance = sidebarTree()->instance();
    $tree = (new ReflectionMethod($instance, 'getNestedset'))->invoke($instance);
    expect($tree)->toHaveCount(2)
        ->and($tree[0]->name)->toBe('Root A')
        ->and($tree[0]->children)->toHaveCount(1)
        ->and($tree[0]->children[0]->name)->toBe('Child A1');

    // 视图：子级嵌套容器（可折叠）+ 每祖先层一个 w-6 纯宽度占位（第二级起缩进，通栏行）
    // 与底部 createAction 按钮（小尺寸 / 描边 / 全宽）
    sidebarTree()
        ->assertSee('Root A')
        ->assertSee('Child A1')
        ->assertSee('Grandchild')
        ->assertSeeHtml('x-show="isExpanded || isSearching"')
        ->assertSeeHtml('mt-0.5 flex w-full flex-col"')
        ->assertSeeHtml("mountAction('create')")
        ->assertSeeHtml('fi-size-sm')
        ->assertSeeHtml('fi-outlined');
});

test('renders the empty state and root create button', function () {
    sidebarTree()
        ->assertSee('No data')
        ->assertSeeHtml("mountAction('create')")
        ->assertSee('New');
});

// ============ searchable / sortable 开关（仅侧栏组件的 props，不在共用 trait） ============

test('renders the search box and sort toggle by default', function () {
    TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);

    sidebarTree()
        ->assertSeeHtml('x-model.debounce.200ms="search"')
        ->assertSeeHtml('x-on:click="toggleSortMode()"')
        ->assertSeeHtml('x-on:click="exitSortMode()"')
        ->assertSeeHtml('fi-modal-slide-over-from-end')
        ->assertSeeHtml('data-sidebar-list')
        ->assertDontSeeHtml('data-sidebar-drop-list')
        ->assertDontSeeHtml('data-sidebar-drop-placeholder');
});

test('hides the search box when searchable is disabled', function () {
    TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);

    sidebarTree(['searchable' => false])
        ->assertDontSeeHtml('x-model.debounce.200ms="search"');
});

test('hides the sort toggle and banner when sortable is disabled', function () {
    TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);

    sidebarTree(['sortable' => false])
        ->assertDontSeeHtml('toggleSortMode()')
        ->assertDontSeeHtml('exitSortMode()');
});

// ============ 转义（节点名是用户输入，必须走 {{ }}） ============

test('rows escape node titles', function () {
    // 侧栏行曾一度用 {!! !!} 直出标签，是真实的存储型 XSS（name 就是普通 input 字段）
    $payload = '<img src=x onerror=alert(1)>';
    TestCategory::create(['name' => $payload, 'scope_type' => 'test', 'scope_id' => 0]);

    sidebarTree()
        ->assertDontSeeHtml($payload)
        ->assertSeeHtml(e($payload));
});

// ============ 点击事件（纯前端 $dispatch，与前台 Nestedset 同一契约） ============

test('rows dispatch leaf-click for leaves and node-click for parents', function () {
    $root = TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);
    TestCategory::create(['name' => 'Child A1', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $root->id]);

    sidebarTree()
        ->assertSeeHtml("\$dispatch('sn-filament-nestedset-node-click', { recordId: '{$root->id}', hasChild: 1 })")
        ->assertSeeHtml("\$dispatch('sn-filament-nestedset-leaf-click', { recordId: '")
        // 有子级的行派发的是 node-click，不是 leaf-click
        ->assertDontSeeHtml("\$dispatch('sn-filament-nestedset-leaf-click', { recordId: '{$root->id}'")
        // 行是 a 标签，默认无链接（仅派发事件）
        ->assertSeeHtml('href=javascript:;');
});

test('rows render links when getRecordUrl returns an attribute HtmlString', function () {
    TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);

    // 视图槽位输出的是「一整个属性」，且走 {{ }}（即 e()）；
    // 只有 Htmlable 才会被原样放行，裸字符串会被转义成一个裸词、不成为 href。
    $withUrl = new class extends TestSidebarTree
    {
        protected function getRecordUrl(Model $record): HtmlString | string | null
        {
            return new HtmlString('href="https://example.com/nodes/' . $record->getKey() . '"');
        }
    };

    Livewire::test($withUrl)
        ->assertSeeHtml('href="https://example.com/nodes/')
        ->assertDontSeeHtml('href=javascript:;');
});

// ============ 选中态（activeId 回传 + getHasActive 高亮；默认展开） ============

test('highlights the active node and renders children expanded by default', function () {
    $root = TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);
    $child = TestCategory::create(['name' => 'Child A1', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $root->id]);

    // 行高亮 class 序列（pr-2 与高亮相邻，区别于排序按钮 Alpine 表达式里的同类名）
    $rowHighlight = 'pr-2 bg-primary-50 dark:bg-primary-500/10';

    // 选中子节点：子行高亮
    sidebarTree(['activeId' => $child->id])
        ->assertSeeHtml($rowHighlight);

    // 默认展开（服务端渲染即可见，规避 morph hidden 死锁）；无选中无高亮
    sidebarTree()
        ->assertSeeHtml('$persist(true).as')
        ->assertDontSeeHtml($rowHighlight);
});

test('sort mode swaps row actions for a drag handle', function () {
    TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);

    sidebarTree()
        // 拖动把手（SortableJS handle，与主树同一契约）默认隐藏，仅排序模式显示
        ->assertSeeHtml('data-sortable-handle')
        ->assertSeeHtml(":class=\"sortMode ? '' : 'hidden'\"")
        // 行操作在排序模式下让位
        ->assertSeeHtml(":class=\"sortMode ? 'hidden' : 'opacity-0 group-hover:opacity-100 group-focus-within:opacity-100'\"");
});

// ============ 行操作 ============

test('row actions are collapsed into an action group by default', function () {
    $root = TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);

    $component = sidebarTree();
    $actions = $component->instance()->getRowActions(withDepthNode($root->id));

    expect($actions)->toHaveCount(1)
        ->and($actions[0])->toBeInstanceOf(ActionGroup::class);

    $component->assertSeeHtml("mountAction('createChild'");
});

test('leaf nodes at max depth hide the create child action', function () {
    $root = TestCategory::create(['name' => 'L1', 'scope_type' => 'test', 'scope_id' => 0]);
    $child = TestCategory::create(['name' => 'L2', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $root->id]);
    $grand = TestCategory::create(['name' => 'L3', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $child->id]);

    $component = sidebarTree();
    $show = function ($id) use ($component) {
        $method = new ReflectionMethod($component->instance(), 'shouldShowCreateChildAction');

        return $method->invoke($component->instance(), withDepthNode($id));
    };

    // level = 3：L1 / L2 可建子级，L3（第 3 层）不可
    expect($show($root->id))->toBeTrue()
        ->and($show($child->id))->toBeTrue()
        ->and($show($grand->id))->toBeFalse();
});

test('subclass can flatten row actions and append custom ones via getRowActions', function () {
    $root = TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);

    $custom = new class extends TestSidebarTree
    {
        protected static ?bool $rowActionsInGroup = false;

        public function getRowActions(Model $record): array
        {
            return array_merge(parent::getRowActions($record), [
                Action::make('customAction')->label('Custom'),
            ]);
        }
    };

    $actions = Livewire::test($custom)->instance()->getRowActions(withDepthNode($root->id));

    expect($actions)->toHaveCount(4)      // createChild + edit + delete + custom（平铺）
        ->and($actions[3]->getName())->toBe('customAction');
});

test('flat row actions are icon-only by default and labels can be re-enabled by subclass', function () {
    $root = TestCategory::create(['name' => 'Root A', 'scope_type' => 'test', 'scope_id' => 0]);

    // 默认（group 模式）：收进 ActionGroup
    $grouped = sidebarTree()->instance()->getRowActions(withDepthNode($root->id));
    expect($grouped[0])->toBeInstanceOf(ActionGroup::class);

    // 平铺默认（侧栏组件 showRowActionLabels = false）：按钮只显示图标
    $flat = new class extends TestSidebarTree
    {
        protected static ?bool $rowActionsInGroup = false;
    };
    $flatActions = Livewire::test($flat)->instance()->getRowActions(withDepthNode($root->id));
    expect($flatActions[0]->isLabelHidden())->toBeTrue()
        ->and($flatActions[1]->isLabelHidden())->toBeTrue();

    // 子类覆写 $showRowActionLabels = true：平铺按钮带文字
    $withLabels = new class extends TestSidebarTree
    {
        protected static ?bool $rowActionsInGroup = false;

        protected static ?bool $showRowActionLabels = true;
    };
    $labeledActions = Livewire::test($withLabels)->instance()->getRowActions(withDepthNode($root->id));
    expect($labeledActions[0]->isLabelHidden())->toBeFalse();
});

// ============ CRUD（继承管理页同一套 action） ============

test('creates a root node via the bottom createAction', function () {
    sidebarTree()
        ->mountAction('create')
        ->setActionData(['name' => 'Root C'])
        ->callMountedAction()
        ->assertDispatched('sn-filament-nestedset-updated');

    $node = TestCategory::where('name', 'Root C')->first();

    expect($node)->not->toBeNull()
        ->and($node->parent_id)->toBeNull()      // 未选父级 = 根节点
        ->and($node->scope_type)->toBe('test');
});

test('creates a child node via the createChild action with scope attributes merged', function () {
    $root = TestCategory::create(['name' => 'Root', 'scope_type' => 'test', 'scope_id' => 0]);

    sidebarTree()
        ->mountAction('createChild', ['parentId' => $root->id])
        ->setActionData(['name' => 'Child Node'])
        ->callMountedAction()
        ->assertDispatched('sn-filament-nestedset-updated');

    $child = TestCategory::where('name', 'Child Node')->first();

    expect($child)->not->toBeNull()
        ->and($child->parent_id)->toBe($root->id)
        ->and($child->scope_type)->toBe('test')
        ->and($child->scope_id)->toBe(0);
});

test('renames a node via the edit action', function () {
    $root = TestCategory::create(['name' => 'Old Name', 'scope_type' => 'test', 'scope_id' => 0]);

    sidebarTree()
        ->mountAction('edit', ['id' => $root->id])
        ->setActionData(['name' => 'New Name'])
        ->callMountedAction()
        ->assertDispatched('sn-filament-nestedset-updated');

    expect($root->fresh()->name)->toBe('New Name');
});

test('deletes a node via the delete action', function () {
    $root = TestCategory::create(['name' => 'Root', 'scope_type' => 'test', 'scope_id' => 0]);

    sidebarTree()
        ->mountAction('delete', ['id' => $root->id])
        ->callMountedAction()
        ->assertDispatched('sn-filament-nestedset-updated');

    expect(TestCategory::find($root->id))->toBeNull();
});

// ============ 基类约束 ============

test('mount throws when the model is not set', function () {
    Livewire::test(SidebarNestedset::class);
})->throws(ViewException::class, 'Nestedset model is not set');

<?php

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\HasNestedsetActions;
use Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestCategory;

beforeEach(function () {
    if (! app()->bound('config')) {
        $this->markTestSkipped('Requires Orchestra Testbench (run from package directory)');
    }

    Facade::setFacadeApplication(app());

    if (! app()->bound('translator')) {
        app()->singleton('translator', fn () => new Translator(new ArrayLoader, 'en'));
    }
});

// Helper: create an anonymous class using HasNestedsetActions with public accessors.
// 注意：匿名类直接 use trait（第二层），不能声明与 trait 同名的静态属性（默认值不同会 fatal），
// 配置覆盖统一走 setter（getXxx/xxx 三件套中的 setter）断言。
function makeActionsPage(?string $model = null, ?int $level = null): object
{
    return new class($model, $level)
    {
        use HasNestedsetActions;

        protected static ?string $modelClass;

        protected static ?int $levelValue;

        public function __construct(?string $model, ?int $level)
        {
            self::$modelClass = $model;
            self::$levelValue = $level;
        }

        public static function getModel(): ?string
        {
            return self::$modelClass;
        }

        public static function getModelLabel(): string
        {
            return 'Test';
        }

        public static function getLevel(): ?int
        {
            return self::$levelValue;
        }

        public function getQuery(): Builder
        {
            return TestCategory::query();
        }

        // Expose protected methods
        public function callGetParentSelect(): array | Field
        {
            return $this->getParentSelect();
        }

        public function callResolveNestedsetActionRecord(Action $action, array $arguments): ?Model
        {
            return $this->resolveNestedsetActionRecord($action, $arguments);
        }

        public function callCanBeDeleted(Model $record): bool
        {
            return $this->canBeDeleted($record);
        }

        public function callGetRowActions(Model $record): array
        {
            return $this->getRowActions($record);
        }

        public function callShouldShowCreateChildAction(Model $record): bool
        {
            return $this->shouldShowCreateChildAction($record);
        }
    };
}

// --- Trait existence and method signatures ---

test('HasNestedsetActions trait exists', function () {
    expect(trait_exists(HasNestedsetActions::class))->toBeTrue();
});

test('trait has required action methods', function () {
    expect(method_exists(HasNestedsetActions::class, 'createAction'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'createChildAction'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'editAction'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'deleteAction'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'moveNodeAction'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'fixNestedsetAction'))->toBeTrue();
});

test('trait has helper methods', function () {
    expect(method_exists(HasNestedsetActions::class, 'getHasFormParentSelect'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'hasFormParentSelect'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'getRowActionsInGroup'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'rowActionsInGroup'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'getParentSelect'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'resolveNestedsetActionRecord'))->toBeTrue();
});

// --- Return types ---

test('editAction returns EditAction instance', function () {
    $page = makeActionsPage(Model::class);
    expect($page->editAction())->toBeInstanceOf(EditAction::class);
});

test('deleteAction returns DeleteAction instance', function () {
    $page = makeActionsPage(Model::class);
    expect($page->deleteAction())->toBeInstanceOf(DeleteAction::class);
});

test('createAction returns CreateAction instance', function () {
    $page = makeActionsPage(Model::class);
    expect($page->createAction())->toBeInstanceOf(CreateAction::class);
});

test('createChildAction returns CreateAction instance', function () {
    $page = makeActionsPage(Model::class);
    expect($page->createChildAction())->toBeInstanceOf(CreateAction::class);
});

test('moveNodeAction returns Action instance', function () {
    $page = makeActionsPage(Model::class);
    expect($page->moveNodeAction())->toBeInstanceOf(Action::class);
});

test('fixNestedsetAction returns Action instance', function () {
    $page = makeActionsPage(Model::class);
    expect($page->fixNestedsetAction())->toBeInstanceOf(Action::class);
});

// --- Return type hints ---

test('editAction has Action return type', function () {
    $reflection = new ReflectionMethod(HasNestedsetActions::class, 'editAction');
    $returnType = $reflection->getReturnType();

    expect($returnType)->not->toBeNull()
        ->and($returnType->getName())->toBe(Action::class);
});

test('deleteAction has Action return type', function () {
    $reflection = new ReflectionMethod(HasNestedsetActions::class, 'deleteAction');
    $returnType = $reflection->getReturnType();

    expect($returnType)->not->toBeNull()
        ->and($returnType->getName())->toBe(Action::class);
});

// --- hasFormParentSelect ---

test('hasFormParentSelect defaults to true', function () {
    expect(makeActionsPage()::getHasFormParentSelect())->toBeTrue();
});

test('hasFormParentSelect can be disabled via setter', function () {
    $page = makeActionsPage();
    $page::hasFormParentSelect(false);

    expect($page::getHasFormParentSelect())->toBeFalse();

    $page::hasFormParentSelect(null);    // 复位，避免静态存储影响后续用例
    expect($page::getHasFormParentSelect())->toBeTrue();
});

// --- getParentSelect ---

test('getParentSelect returns KalnoyNestedsetSelectTree', function () {
    $page = makeActionsPage(TestCategory::class, 3);
    $select = $page->callGetParentSelect();

    expect($select)->toBeInstanceOf(KalnoyNestedsetSelectTree::class);
});

// --- resolveNestedsetActionRecord ---

test('resolveNestedsetActionRecord returns null when id is missing', function () {
    $page = makeActionsPage(TestCategory::class);
    $action = Action::make('test');

    $result = $page->callResolveNestedsetActionRecord($action, []);
    expect($result)->toBeNull();
});

test('resolveNestedsetActionRecord caches resolved records', function () {
    $record = TestCategory::create(['name' => 'Cached', 'scope_type' => 'test', 'scope_id' => 0]);

    $page = makeActionsPage(TestCategory::class);
    $action = Action::make('test');
    $action->nestingIndex(1);

    $result1 = $page->callResolveNestedsetActionRecord($action, ['id' => $record->id]);
    expect($result1)->not->toBeNull()
        ->and($result1->id)->toBe($record->id);

    $result2 = $page->callResolveNestedsetActionRecord($action, ['id' => $record->id]);
    expect($result2)->toBe($result1);
});

// --- show_row_action_labels（平铺行操作按钮文字显隐；ActionGroup 内 label 恒显示） ---

test('showRowActionLabels defaults to true', function () {
    expect(makeActionsPage()::getShowRowActionLabels())->toBeTrue();
});

test('showRowActionLabels can be disabled via setter', function () {
    $page = makeActionsPage();
    $page::showRowActionLabels(false);

    expect($page::getShowRowActionLabels())->toBeFalse();

    $page::showRowActionLabels(null);    // 复位
    expect($page::getShowRowActionLabels())->toBeTrue();
});

test('flat row action labels are shown by default', function () {
    $record = TestCategory::create(['name' => 'Row', 'scope_type' => 'test', 'scope_id' => 0]);
    $actions = makeActionsPage(TestCategory::class)->callGetRowActions($record);

    expect($actions[0]->isLabelHidden())->toBeFalse()
        ->and($actions[1]->isLabelHidden())->toBeFalse();
});

test('flat row action labels are icon-only when disabled via setter', function () {
    $record = TestCategory::create(['name' => 'Row', 'scope_type' => 'test', 'scope_id' => 0]);

    $page = makeActionsPage(TestCategory::class);
    $page::showRowActionLabels(false);

    $actions = $page->callGetRowActions($record);

    expect($actions[0]->isLabelHidden())->toBeTrue()
        ->and($actions[1]->isLabelHidden())->toBeTrue()
        // label 本身仍然可读（渲染为 sr-only / aria-label），只是视觉隐藏
        ->and($actions[1]->getLabel())->toBeString()
        ->and($actions[1]->getLabel())->not->toBeEmpty();

    $page::showRowActionLabels(null);    // 复位
});

// --- getRowActions（行操作集合：显示过滤 + ActionGroup 切换 + 子类覆写点） ---

test('getRowActions returns flat createChild/edit/delete by default', function () {
    $record = TestCategory::create(['name' => 'Row', 'scope_type' => 'test', 'scope_id' => 0]);
    $actions = makeActionsPage(TestCategory::class)->callGetRowActions($record);

    expect($actions)->toHaveCount(3)
        ->and($actions[0])->toBeInstanceOf(CreateAction::class)
        ->and($actions[1])->toBeInstanceOf(EditAction::class)
        ->and($actions[2])->toBeInstanceOf(DeleteAction::class);
});

test('getRowActions collapses actions into an ActionGroup when enabled via setter', function () {
    $record = TestCategory::create(['name' => 'Row', 'scope_type' => 'test', 'scope_id' => 0]);

    $page = makeActionsPage(TestCategory::class);
    $page::rowActionsInGroup(true);

    $actions = $page->callGetRowActions($record);

    expect($actions)->toHaveCount(1)
        ->and($actions[0])->toBeInstanceOf(ActionGroup::class);

    $page::rowActionsInGroup(null);    // 复位
    expect($page::getRowActionsInGroup())->toBeFalse();
});

test('getRowActions hides create child when disabled via setter', function () {
    $record = TestCategory::create(['name' => 'Row', 'scope_type' => 'test', 'scope_id' => 0]);

    $page = makeActionsPage(TestCategory::class);
    $page::showCreateChildNodeActionInRow(false);

    $actions = $page->callGetRowActions($record);

    expect($actions)->toHaveCount(2)
        ->and($actions[0])->toBeInstanceOf(EditAction::class);

    $page::showCreateChildNodeActionInRow(null);    // 复位
});

test('getRowActions hides create child when the level limit is reached', function () {
    // level = 1：根节点（depth 0）不能再建子级
    $record = TestCategory::create(['name' => 'Row', 'scope_type' => 'test', 'scope_id' => 0]);

    expect(makeActionsPage(TestCategory::class, 1)->callShouldShowCreateChildAction($record))->toBeFalse()
        ->and(makeActionsPage(TestCategory::class, 2)->callShouldShowCreateChildAction($record))->toBeTrue();
});

test('getRowActions hides delete for nodes with children when parent deletion disallowed', function () {
    $parent = TestCategory::create(['name' => 'Parent', 'scope_type' => 'test', 'scope_id' => 0]);
    TestCategory::create(['name' => 'Child', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $parent->id]);

    // 默认 $allowDeleteParent = false（有子级不可删）
    // 视图记录来自树查询（children 已加载），这里按同语境 fresh 加载
    $actions = makeActionsPage(TestCategory::class)->callGetRowActions(TestCategory::find($parent->id));

    expect($actions)->toHaveCount(2)      // createChild + edit，无 delete
        ->and($actions[1])->toBeInstanceOf(EditAction::class);
});

test('canBeDeleted respects setter over the default', function () {
    $parent = TestCategory::create(['name' => 'Parent', 'scope_type' => 'test', 'scope_id' => 0]);
    TestCategory::create(['name' => 'Child', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $parent->id]);

    $page = makeActionsPage();
    $page::allowDeleteParent(true);
    $page::allowDeleteRoot(true);

    expect($page->callCanBeDeleted(TestCategory::find($parent->id)))->toBeTrue();

    $page::allowDeleteParent(null);    // 复位
    $page::allowDeleteRoot(null);
    expect($page->callCanBeDeleted(TestCategory::find($parent->id)))->toBeFalse();
});

<?php

use Filament\Actions\Action;
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

// Helper: create an anonymous class using HasNestedsetActions with public accessors
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

        public function callShowRowActionLabels(): bool
        {
            return $this->showRowActionLabels();
        }

        public function callResolveNestedsetActionRecord(Action $action, array $arguments): ?Model
        {
            return $this->resolveNestedsetActionRecord($action, $arguments);
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
    expect(method_exists(HasNestedsetActions::class, 'hasFormParentSelect'))->toBeTrue()
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

test('hasFormParentSelect reads config value', function () {
    config()->set('sn-filament-nestedset.create_action_modal_show_parent_select', true);

    $page = makeActionsPage();
    expect($page->hasFormParentSelect())->toBeTrue();
});

test('hasFormParentSelect returns false when config is false', function () {
    config()->set('sn-filament-nestedset.create_action_modal_show_parent_select', false);

    $page = makeActionsPage();
    expect($page->hasFormParentSelect())->toBeFalse();
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

// --- show_row_action_labels（行操作按钮文字显隐） ---

test('showRowActionLabels defaults to true with shipped config', function () {
    // 包配置文件默认 show_row_action_labels = true，Testbench 启动时由服务提供者合并
    $page = makeActionsPage();
    expect($page->callShowRowActionLabels())->toBeTrue();
});

test('showRowActionLabels reads config value', function () {
    config()->set('sn-filament-nestedset.show_row_action_labels', false);

    $page = makeActionsPage();
    expect($page->callShowRowActionLabels())->toBeFalse();
});

test('row action labels are shown by default', function () {
    config()->set('sn-filament-nestedset.show_row_action_labels', true);

    $page = makeActionsPage(TestCategory::class);
    expect($page->editAction()->isLabelHidden())->toBeFalse()
        ->and($page->deleteAction()->isLabelHidden())->toBeFalse()
        ->and($page->createChildAction()->isLabelHidden())->toBeFalse();
});

test('row action labels are hidden at any width when config disabled', function () {
    config()->set('sn-filament-nestedset.show_row_action_labels', false);

    $page = makeActionsPage(TestCategory::class);
    expect($page->editAction()->isLabelHidden())->toBeTrue()
        ->and($page->deleteAction()->isLabelHidden())->toBeTrue()
        ->and($page->createChildAction()->isLabelHidden())->toBeTrue();
});

test('hidden labels keep accessible label text', function () {
    config()->set('sn-filament-nestedset.show_row_action_labels', false);

    $page = makeActionsPage(TestCategory::class);
    $action = $page->editAction();

    // label 本身仍然可读（渲染为 sr-only / aria-label），只是视觉隐藏
    expect($action->getLabel())->toBeString()
        ->and($action->getLabel())->not->toBeEmpty();
});

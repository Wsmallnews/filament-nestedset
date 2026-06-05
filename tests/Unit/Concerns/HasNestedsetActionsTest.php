<?php

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\HasNestedsetActions;
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
    return new class ($model, $level)
    {
        use HasNestedsetActions;

        protected static ?string $modelClass;
        protected static ?int $levelValue;

        public function __construct(?string $model, ?int $level)
        {
            static::$modelClass = $model;
            static::$levelValue = $level;
        }

        public static function getModel(): ?string
        {
            return static::$modelClass;
        }

        public static function getModelLabel(): string
        {
            return 'Test';
        }

        public static function getLevel(): ?int
        {
            return static::$levelValue;
        }

        public function getQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return TestCategory::query();
        }

        // Expose protected methods
        public function callGetParentSelect(): array|\Filament\Forms\Components\Field
        {
            return $this->getParentSelect();
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

    expect($select)->toBeInstanceOf(\Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree::class);
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

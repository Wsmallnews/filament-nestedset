<?php

use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Wsmallnews\FilamentNestedset\Exceptions\NestedsetException;
use Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\PlainModel;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestCategory;

// Skip tests that need the Laravel app when running without Testbench
beforeEach(function (): void {
    if (! app()->bound('config')) {
        $this->markTestSkipped('Requires Orchestra Testbench (run from package directory)');
    }
});

// Helper: create a concrete NestedsetPage subclass with public method accessors
function makeTestPage(array $overrides = []): NestedsetPage
{
    return new class($overrides) extends NestedsetPage
    {
        protected static ?string $model;

        protected static ?string $modelLabel;

        protected static ?int $level;

        protected static ?string $emptyLabel;

        protected static ?string $emptyTipLabel;

        protected static string $recordTitleAttribute;

        protected static ?string $tabFieldName;

        protected static Alignment $infolistAlignment;

        protected static string $infolistHiddenEndpoint;

        protected static bool $isScopedToTenant;

        public array $overrides;

        public function __construct(array $overrides = [])
        {
            $this->overrides = $overrides;
            self::$model = $overrides['model'] ?? null;
            self::$modelLabel = $overrides['modelLabel'] ?? null;
            self::$level = $overrides['level'] ?? null;
            self::$emptyLabel = $overrides['emptyLabel'] ?? null;
            self::$emptyTipLabel = $overrides['emptyTipLabel'] ?? null;
            self::$recordTitleAttribute = $overrides['recordTitleAttribute'] ?? 'name';
            self::$tabFieldName = $overrides['tabFieldName'] ?? null;
            self::$infolistAlignment = $overrides['infolistAlignment'] ?? Alignment::Right;
            self::$infolistHiddenEndpoint = $overrides['infolistHiddenEndpoint'] ?? 'md';
            self::$isScopedToTenant = $overrides['isScopedToTenant'] ?? true;
        }

        // Expose protected methods for testing
        public function callSchema(array $arguments): array
        {
            return $this->schema($arguments);
        }

        public function callInfolistSchema(): array
        {
            return $this->infolistSchema();
        }

        public function callNestedScoped(): array
        {
            return $this->nestedScoped();
        }

        public function callGetQuery(): Builder
        {
            return $this->getQuery();
        }

        public function callGetNestedset()
        {
            return $this->getNestedset();
        }

        public function callCanBeDeleted(Model $record): bool
        {
            return $this->canBeDeleted($record);
        }

        public function callGetEloquentQuery($query)
        {
            return $this->getEloquentQuery($query);
        }
    };
}

// --- Static getters ---

test('getModel returns model class from subclass', function () {
    $page = makeTestPage(['model' => TestCategory::class]);
    expect($page::getModel())->toBe(TestCategory::class);
});

test('getModelLabel returns custom label when set', function () {
    $page = makeTestPage(['model' => TestCategory::class, 'modelLabel' => 'Custom Label']);
    expect($page::getModelLabel())->toBe('Custom Label');
});

test('getLevel returns configured level', function () {
    $page = makeTestPage(['level' => 5]);
    expect($page::getLevel())->toBe(5);
});

test('getLevel returns null when not set', function () {
    $page = makeTestPage(['level' => null]);
    expect($page::getLevel())->toBeNull();
});

test('getEmptyLabel returns custom label when set', function () {
    $page = makeTestPage(['emptyLabel' => 'No data']);
    expect($page::getEmptyLabel())->toBe('No data');
});

test('getEmptyTipLabel returns custom tip label when set', function () {
    $page = makeTestPage(['emptyTipLabel' => 'No data available']);
    expect($page::getEmptyTipLabel())->toBe('No data available');
});

test('getRecordTitleAttribute returns configured attribute', function () {
    $page = makeTestPage(['recordTitleAttribute' => 'title']);
    expect($page::getRecordTitleAttribute())->toBe('title');
});

test('getTabFieldName returns configured field', function () {
    $page = makeTestPage(['tabFieldName' => 'type']);
    expect($page::getTabFieldName())->toBe('type');
});

test('isScopedToTenant returns configured value', function () {
    $page = makeTestPage(['isScopedToTenant' => false]);
    expect($page::isScopedToTenant())->toBeFalse();
});

test('getInfolistAlignment returns configured alignment', function () {
    $page = makeTestPage(['infolistAlignment' => Alignment::Left]);
    expect($page->getInfolistAlignment())->toBe(Alignment::Left);
});

test('getInfolistHiddenEndpoint returns configured endpoint', function () {
    $page = makeTestPage(['infolistHiddenEndpoint' => 'lg']);
    expect($page->getInfolistHiddenEndpoint())->toBe('lg');
});

// --- Default hook returns ---

test('schema returns empty array by default', function () {
    $page = makeTestPage(['model' => TestCategory::class]);
    expect($page->callSchema([]))->toBe([]);
});

test('infolistSchema returns empty array by default', function () {
    $page = makeTestPage(['model' => TestCategory::class]);
    expect($page->callInfolistSchema())->toBe([]);
});

test('nestedScoped returns empty array by default', function () {
    $page = makeTestPage(['model' => TestCategory::class]);
    expect($page->callNestedScoped())->toBe([]);
});

// --- validateModel ---

test('validateModel throws when model is not set', function () {
    $page = makeTestPage(['model' => null]);
    $page->mountInteractsWithNestedset();
})->throws(NestedsetException::class, 'Nestedset model is not set');

test('validateModel throws when model does not use NodeTrait', function () {
    $page = makeTestPage(['model' => PlainModel::class]);
    $page->mountInteractsWithNestedset();
})->throws(NestedsetException::class);

test('validateModel passes when model uses NodeTrait', function () {
    $page = makeTestPage(['model' => TestCategory::class]);
    $page->mountInteractsWithNestedset();
    expect($page::getModel())->toBe(TestCategory::class);
});

// --- getQuery ---

test('getQuery uses newScopedQuery when no scopes are set', function () {
    $page = makeTestPage([
        'model' => TestCategory::class,
        'isScopedToTenant' => false,
        'tabFieldName' => null,
    ]);

    $query = $page->callGetQuery();
    expect($query)->toBeInstanceOf(Builder::class)
        ->and($query->getModel())->toBeInstanceOf(TestCategory::class);
});

test('getQuery applies tab scope when tabFieldName and activeTab are set', function () {
    $page = makeTestPage([
        'model' => TestCategory::class,
        'isScopedToTenant' => false,
        'tabFieldName' => 'scope_type',
    ]);
    $page->activeTab = 'web';

    $query = $page->callGetQuery();
    expect($query)->toBeInstanceOf(Builder::class);
});

test('getQuery merges nestedScoped custom scopes', function () {
    $page = new class(['model' => TestCategory::class]) extends NestedsetPage
    {
        protected static ?string $model;

        protected static bool $isScopedToTenant = false;

        protected static ?string $tabFieldName = null;

        public function __construct(array $overrides = [])
        {
            self::$model = $overrides['model'] ?? null;
        }

        protected function nestedScoped(): array
        {
            return ['scope_type' => 'custom', 'scope_id' => 5];
        }

        public function callGetQuery(): Builder
        {
            return $this->getQuery();
        }
    };

    $query = $page->callGetQuery();
    expect($query)->toBeInstanceOf(Builder::class);
});

test('getEloquentQuery hook is applied', function () {
    $page = new class(['model' => TestCategory::class]) extends NestedsetPage
    {
        protected static ?string $model;

        protected static bool $isScopedToTenant = false;

        protected static ?string $tabFieldName = null;

        public function __construct(array $overrides = [])
        {
            self::$model = $overrides['model'] ?? null;
        }

        protected function nestedScoped(): array
        {
            return [];
        }

        protected function getEloquentQuery($query)
        {
            return $query->where('status', 'normal');
        }

        public function callGetQuery(): Builder
        {
            return $this->getQuery();
        }
    };

    $query = $page->callGetQuery();
    $sql = $query->toSql();
    expect($sql)->toContain('status');
});

// --- getNestedset ---

test('getNestedset returns tree collection', function () {
    TestCategory::create(['name' => 'Root 1', 'scope_type' => 'test', 'scope_id' => 0]);
    TestCategory::create(['name' => 'Root 2', 'scope_type' => 'test', 'scope_id' => 0]);

    $page = new class(['model' => TestCategory::class]) extends NestedsetPage
    {
        protected static ?string $model;

        protected static bool $isScopedToTenant = false;

        protected static ?string $tabFieldName = null;

        public function __construct(array $overrides = [])
        {
            self::$model = $overrides['model'] ?? null;
        }

        protected function nestedScoped(): array
        {
            return ['scope_type' => 'test', 'scope_id' => 0];
        }

        public function callGetNestedset()
        {
            return $this->getNestedset();
        }
    };

    $tree = $page->callGetNestedset();
    expect($tree)->toBeInstanceOf(Collection::class)
        ->and($tree)->toHaveCount(2);
});

// --- canBeDeleted ---

test('canBeDeleted returns false for parent when allow_delete_parent is false and has children', function () {
    config()->set('sn-filament-nestedset.allow_delete_parent', false);
    config()->set('sn-filament-nestedset.allow_delete_root', false);

    $parent = TestCategory::create(['name' => 'Parent', 'scope_type' => 'test', 'scope_id' => 0]);
    TestCategory::create(['name' => 'Child', 'scope_type' => 'test', 'scope_id' => 0], $parent);

    $page = makeTestPage(['model' => TestCategory::class]);
    expect($page->callCanBeDeleted($parent))->toBeFalse();
});

test('canBeDeleted returns true for leaf node even when allow_delete_parent is false', function () {
    config()->set('sn-filament-nestedset.allow_delete_parent', false);

    $leaf = TestCategory::create(['name' => 'Leaf', 'scope_type' => 'test', 'scope_id' => 0]);

    $page = makeTestPage(['model' => TestCategory::class]);
    expect($page->callCanBeDeleted($leaf))->toBeTrue();
});

test('canBeDeleted returns true for parent when allow_delete_parent is true', function () {
    config()->set('sn-filament-nestedset.allow_delete_parent', true);
    config()->set('sn-filament-nestedset.allow_delete_root', true);

    $parent = TestCategory::create(['name' => 'Parent', 'scope_type' => 'test', 'scope_id' => 0]);
    TestCategory::create(['name' => 'Child', 'scope_type' => 'test', 'scope_id' => 0], $parent);

    $page = makeTestPage(['model' => TestCategory::class]);
    expect($page->callCanBeDeleted($parent))->toBeTrue();
});

test('canBeDeleted returns false for root with children when allow_delete_root is false', function () {
    config()->set('sn-filament-nestedset.allow_delete_parent', true);
    config()->set('sn-filament-nestedset.allow_delete_root', false);

    $root = TestCategory::create(['name' => 'Root', 'scope_type' => 'test', 'scope_id' => 0]);
    TestCategory::create(['name' => 'Child', 'scope_type' => 'test', 'scope_id' => 0], $root);

    $page = makeTestPage(['model' => TestCategory::class]);
    expect($page->callCanBeDeleted($root))->toBeFalse();
});

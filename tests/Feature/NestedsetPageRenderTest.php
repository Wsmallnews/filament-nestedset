<?php

use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\View\ViewException;
use Livewire\Attributes\On;
use Livewire\Livewire;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestCategory;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestModelLessNestedsetPage;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestNestedsetPage;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestTabbedNestedsetPage;

function pageNode(string $name, ?TestCategory $parent = null, array $extra = []): TestCategory
{
    return TestCategory::create([
        'name' => $name,
        'scope_type' => 'test',
        'scope_id' => 0,
        'parent_id' => $parent?->getKey(),
        ...$extra,
    ]);
}

// ============ mount：模型守卫与默认 tab（页面路径曾完全不执行） ============

test('page mount rejects a page without a nestedset model', function () {
    // Livewire wraps mount failures raised during the first render
    expect(fn () => Livewire::test(TestModelLessNestedsetPage::class))
        ->toThrow(ViewException::class, 'Nestedset model is not set');
});

test('the nestedset mount hook runs exactly once per render', function () {
    // Guard against re-introducing a mount() that calls the trait hook a second time
    $page = new class extends TestNestedsetPage
    {
        public static int $hookCalls = 0;

        public function mountInteractsWithNestedset(): void
        {
            self::$hookCalls++;

            parent::mountInteractsWithNestedset();
        }
    };

    Livewire::test($page);

    expect($page::$hookCalls)->toBe(1);
});

test('page mount loads the default active tab', function () {
    expect(Livewire::test(TestTabbedNestedsetPage::class)->instance()->activeTab)->toBe('web');
});

test('page query is scoped by the active tab', function () {
    pageNode('Web node', null, ['scope_type' => 'web']);
    pageNode('App node', null, ['scope_type' => 'app']);

    $page = Livewire::test(TestTabbedNestedsetPage::class)->instance();
    $names = (new ReflectionMethod($page, 'getQuery'))->invoke($page)->get()->pluck('name')->all();

    expect($names)->toBe(['Web node']);
});

test('page switches tree content when the active tab changes', function () {
    pageNode('Web node', null, ['scope_type' => 'web']);
    pageNode('App node', null, ['scope_type' => 'app']);

    Livewire::test(TestTabbedNestedsetPage::class)
        ->set('activeTab', 'app')
        ->assertSee('App node')
        ->assertDontSee('Web node');
});

// ============ 渲染 ============

test('page renders the tree with row actions and the sortable container', function () {
    $root = pageNode('Page Root');
    pageNode('Page Child', $root);

    Livewire::test(TestNestedsetPage::class)
        ->assertSee('Page Root')
        ->assertSee('Page Child')
        ->assertSeeHtml('data-sortable-container')
        ->assertSeeHtml('fi-sn-nestedset-record');
});

test('page renders the Filament empty state with the configured labels', function () {
    Livewire::test(TestNestedsetPage::class)
        ->assertSee(__('sn-filament-nestedset::nestedset.nestedset.empty_label'))
        ->assertSee(__('sn-filament-nestedset::nestedset.nestedset.empty_tip_label'));
});

test('page infolist uses a container query breakpoint, not a viewport one', function () {
    pageNode('With infolist');

    $withInfolist = new class extends TestNestedsetPage
    {
        protected function infolistSchema(): array
        {
            return [TextEntry::make('status')];
        }
    };

    Livewire::test($withInfolist)
        ->assertSeeHtml('@3xl:flex!')
        ->assertDontSeeHtml('lg:flex!');
});

// ============ 行标签输出安全 ============

test('page escapes node titles', function () {
    $payload = '<script>alert(1)</script>';
    pageNode($payload);

    Livewire::test(TestNestedsetPage::class)
        ->assertDontSeeHtml($payload)
        ->assertSeeHtml(e($payload));
});

test('page keeps HtmlString labels a subclass returns on purpose', function () {
    pageNode('Raw');

    $withHtml = new class extends TestNestedsetPage
    {
        protected function getRecordLabel(Model $record): HtmlString | string
        {
            return new HtmlString('<b>bold</b>');
        }
    };

    Livewire::test($withHtml)
        ->assertSeeHtml('<b>bold</b>');
});

// ============ CRUD 与事件 ============

test('page creates a scoped root node through the header create action', function () {
    Livewire::test(TestNestedsetPage::class)
        ->mountAction('create')
        ->setActionData(['name' => 'From page'])
        ->callMountedAction()
        ->assertDispatched('sn-filament-nestedset-updated');

    $node = TestCategory::where('name', 'From page')->first();

    expect($node)->not->toBeNull()
        ->and($node->parent_id)->toBeNull()
        ->and($node->scope_type)->toBe('test');
});

test('page refresh keeps listening to the panel refresh-page event', function () {
    $events = array_map(
        fn (ReflectionAttribute $attribute): ?string => $attribute->newInstance()->event,
        (new ReflectionMethod(InteractsWithNestedset::class, 'refresh'))->getAttributes(On::class),
    );

    expect($events)->toContain('sn-filament-nestedset-updated')
        ->and($events)->toContain('refresh-page');
});

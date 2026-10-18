<?php

use Illuminate\View\ViewException;
use Livewire\Livewire;
use Wsmallnews\FilamentNestedset\Filament\Pages\Widgets\Nestedset;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestCategory;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestNestedsetWidget;

function widgetNode(string $name, ?TestCategory $parent = null): TestCategory
{
    return TestCategory::create([
        'name' => $name,
        'scope_type' => 'test',
        'scope_id' => 0,
        'parent_id' => $parent?->getKey(),
    ]);
}

test('widget renders the tree', function () {
    $root = widgetNode('Widget Root');
    widgetNode('Widget Child', $root);

    Livewire::test(TestNestedsetWidget::class)
        ->assertSee('Widget Root')
        ->assertSee('Widget Child')
        ->assertSeeHtml('data-sortable-container');
});

test('widget renders the Filament empty state when the tree is empty', function () {
    Livewire::test(TestNestedsetWidget::class)
        ->assertSee(__('sn-filament-nestedset::nestedset.nestedset.empty_label'));
});

test('widget exposes the nestedset actions as header actions', function () {
    $instance = Livewire::test(TestNestedsetWidget::class)->instance();
    $actions = (new ReflectionMethod($instance, 'getHeaderActions'))->invoke($instance);

    expect($actions)->toHaveCount(2)
        ->and($actions[0]->getName())->toBe('create')
        ->and($actions[1]->getName())->toBe('fixNestedset');
});

test('widget mount still validates the nestedset model', function () {
    $modelLess = new class extends Nestedset
    {
        protected static bool $isScopedToTenant = false;
    };

    expect(fn () => Livewire::test($modelLess))
        ->toThrow(ViewException::class, 'Nestedset model is not set');
});

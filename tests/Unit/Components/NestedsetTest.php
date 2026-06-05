<?php

use Filament\Widgets\Widget;
use Livewire\Component;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset;
use Wsmallnews\FilamentNestedset\Filament\Pages\Widgets\Nestedset as NestedsetWidget;
use Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset as NestedsetLivewire;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestCategory;

// Skip tests that need the Laravel app when running without Testbench
beforeEach(function (): void {
    if (! app()->bound('config')) {
        $this->markTestSkipped('Requires Orchestra Testbench (run from package directory)');
    }
});

// --- Filament Widget ---

test('Filament Widget extends Filament Widget class', function () {
    expect(is_subclass_of(NestedsetWidget::class, Widget::class))->toBeTrue();
});

test('Filament Widget uses InteractsWithNestedset trait', function () {
    expect(class_uses(NestedsetWidget::class))->toContain(InteractsWithNestedset::class);
});

test('Filament Widget has full column span property', function () {
    $reflection = new ReflectionClass(NestedsetWidget::class);
    $property = $reflection->getProperty('columnSpan');

    // Read default value from the class declaration
    $default = $property->getDefaultValue();
    expect($default)->toBe('full');
});

test('Filament Widget has correct view property', function () {
    $reflection = new ReflectionClass(NestedsetWidget::class);
    $property = $reflection->getProperty('view');

    $default = $property->getDefaultValue();
    expect($default)->toBe('sn-filament-nestedset::filament.pages.widgets.nestedset');
});

// --- Livewire Component ---

test('Livewire Component extends Livewire Component', function () {
    expect(is_subclass_of(NestedsetLivewire::class, Component::class))->toBeTrue();
});

test('Livewire Component has correct default public properties', function () {
    $component = new NestedsetLivewire();

    expect($component->showLevel)->toBeNull()
        ->and($component->emptyLabel)->toBe('')
        ->and($component->model)->toBeNull()
        ->and($component->recordTitleAttribute)->toBe('name')
        ->and($component->view)->toBe('sn-filament-nestedset::livewire.components.nestedset')
        ->and($component->recordView)->toBe('sn-filament-nestedset::components.nestedset-record');
});

test('Livewire Component getRecordTitleAttribute returns configured value', function () {
    $component = new NestedsetLivewire();
    $component->recordTitleAttribute = 'title';

    expect($component->getRecordTitleAttribute())->toBe('title');
});

test('Livewire Component getRecordLabel returns record attribute value', function () {
    $component = new NestedsetLivewire();
    $component->recordTitleAttribute = 'name';

    $record = TestCategory::create(['name' => 'Test Node', 'scope_type' => 'test', 'scope_id' => 0]);

    expect($component->getRecordLabel($record))->toBe('Test Node');
});

test('Livewire Component getRecordUrl returns null by default', function () {
    $component = new NestedsetLivewire();
    $record = TestCategory::create(['name' => 'Url Test', 'scope_type' => 'test', 'scope_id' => 0]);

    expect($component->getRecordUrl($record))->toBeNull();
});

test('Livewire Component getHasActive returns false by default', function () {
    $component = new NestedsetLivewire();
    $record = TestCategory::create(['name' => 'Active Test', 'scope_type' => 'test', 'scope_id' => 0]);

    expect($component->getHasActive($record))->toBeFalse();
});

test('Livewire Component getView returns configured view', function () {
    $component = new NestedsetLivewire();
    $component->view = 'custom.view';

    expect($component->getView())->toBe('custom.view');
});

test('Livewire Component getRecordView returns configured record view', function () {
    $component = new NestedsetLivewire();
    $component->recordView = 'custom.record-view';

    expect($component->getRecordView())->toBe('custom.record-view');
});

test('Livewire Component getQuery throws when model is not set', function () {
    $component = new NestedsetLivewire();
    $component->model = null;

    $component->getNestedset();
})->throws(\Exception::class, 'Please set the model or custom `getNestedset` method in the nestedset component.');

test('Livewire Component getShowLevel returns showLevel value', function () {
    $component = new NestedsetLivewire();
    $component->showLevel = '3';

    expect($component->getShowLevel())->toBe('3');
});

test('Livewire Component getEmptyLabel returns emptyLabel value', function () {
    $component = new NestedsetLivewire();
    $component->emptyLabel = 'No items';

    expect($component->getEmptyLabel())->toBe('No items');
});

<?php

use Wsmallnews\FilamentNestedset\Filament\Pages\Components\Nestedset;

test('component uses HasNestedsetActions trait', function () {
    expect(class_uses(Nestedset::class))->toContain(
        \Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\HasNestedsetActions::class
    );
});

test('component extends BasePage', function () {
    expect(is_subclass_of(Nestedset::class, \Filament\Pages\BasePage::class))->toBeTrue();
});

test('component has public properties', function () {
    $reflection = new ReflectionClass(Nestedset::class);

    expect($reflection->hasProperty('pageClass'))->toBeTrue()
        ->and($reflection->hasProperty('activeTab'))->toBeTrue()
        ->and($reflection->hasProperty('model'))->toBeTrue()
        ->and($reflection->hasProperty('recordTitleAttribute'))->toBeTrue()
        ->and($reflection->hasProperty('level'))->toBeTrue()
        ->and($reflection->hasProperty('emptyLabel'))->toBeTrue()
        ->and($reflection->hasProperty('emptyTipLabel'))->toBeTrue()
        ->and($reflection->hasProperty('isScopedToTenant'))->toBeTrue();
});

test('component has required methods', function () {
    expect(method_exists(Nestedset::class, 'getLevel'))->toBeTrue()
        ->and(method_exists(Nestedset::class, 'getEmptyLabel'))->toBeTrue()
        ->and(method_exists(Nestedset::class, 'getEmptyTipLabel'))->toBeTrue()
        ->and(method_exists(Nestedset::class, 'getRecordLabel'))->toBeTrue()
        ->and(method_exists(Nestedset::class, 'showCreateChildNodeActionInRow'))->toBeTrue()
        ->and(method_exists(Nestedset::class, 'canBeDeleted'))->toBeTrue()
        ->and(method_exists(Nestedset::class, 'getQuery'))->toBeTrue()
        ->and(method_exists(Nestedset::class, 'getViewData'))->toBeTrue();
});

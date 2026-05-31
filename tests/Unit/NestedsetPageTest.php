<?php

use Filament\Support\Enums\Alignment;
use Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage;

// Create a concrete implementation for testing
beforeEach(function () {
    $this->page = new class extends NestedsetPage
    {
        protected static ?string $model = stdClass::class;

        protected static ?string $modelLabel = 'Test Model';

        protected static ?int $level = 3;

        protected static ?string $emptyLabel = 'No data';

        protected static ?string $emptyTipLabel = 'No data available';

        protected static string $recordTitleAttribute = 'title';

        protected static ?string $tabFieldName = 'type';

        protected static Alignment $infolistAlignment = Alignment::Left;

        protected static string $infolistHiddenEndpoint = 'lg';

        protected static bool $isScopedToTenant = false;
    };
});

test('getModel returns model class', function () {
    expect($this->page::getModel())->toBe(stdClass::class);
});

test('getModelLabel returns model label', function () {
    expect($this->page::getModelLabel())->toBe('Test Model');
});

test('getLevel returns level', function () {
    expect($this->page::getLevel())->toBe(3);
});

test('getEmptyLabel returns empty label', function () {
    expect($this->page::getEmptyLabel())->toBe('No data');
});

test('getEmptyTipLabel returns empty tip label', function () {
    expect($this->page::getEmptyTipLabel())->toBe('No data available');
});

test('getRecordTitleAttribute returns title attribute', function () {
    expect($this->page::getRecordTitleAttribute())->toBe('title');
});

test('getTabFieldName returns tab field name', function () {
    expect($this->page::getTabFieldName())->toBe('type');
});

test('isScopedToTenant returns false when set to false', function () {
    expect($this->page::isScopedToTenant())->toBeFalse();
});

test('getInfolistAlignment returns alignment', function () {
    expect($this->page->getInfolistAlignment())->toBe(Alignment::Left);
});

test('getInfolistHiddenEndpoint returns endpoint', function () {
    expect($this->page->getInfolistHiddenEndpoint())->toBe('lg');
});

test('schema returns empty array by default', function () {
    expect($this->page->schema([]))->toBe([]);
});

test('infolistSchema returns empty array by default', function () {
    expect($this->page->infolistSchema())->toBe([]);
});

test('nestedScoped returns empty array by default', function () {
    expect($this->page->nestedScoped())->toBe([]);
});

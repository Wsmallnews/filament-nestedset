<?php

use Illuminate\Support\Facades\View;
use Wsmallnews\FilamentNestedset\Filament\Components\SidebarNestedset;

test('Filament internal views follow their component directories', function () {
    $component = new SidebarNestedset;

    expect($component->getContentComponent())
        ->toBe('sn-filament-nestedset::filament.components.sidebar-nestedset-content')
        ->and($component->getRecordView())
        ->toBe('sn-filament-nestedset::filament.components.sidebar-nestedset-record')
        ->and(View::exists('sn-filament-nestedset::components.filament.components.sidebar-nestedset-content'))
        ->toBeTrue()
        ->and(View::exists('sn-filament-nestedset::components.filament.components.sidebar-nestedset-record'))
        ->toBeTrue()
        ->and(View::exists('sn-filament-nestedset::components.filament.pages.nestedset'))
        ->toBeTrue()
        ->and(View::exists('sn-filament-nestedset::components.filament.pages.nestedset-record'))
        ->toBeTrue()
        ->and(View::exists('sn-filament-nestedset::components.filament.pages.nestedset-infolist'))
        ->toBeTrue()
        ->and(View::exists('sn-filament-nestedset::filament.components.sidebar-nestedset'))
        ->toBeTrue()
        ->and(View::exists('sn-filament-nestedset::filament.pages.nestedset-page'))
        ->toBeTrue()
        ->and(View::exists('sn-filament-nestedset::filament.pages.widgets.nestedset'))
        ->toBeTrue();
});

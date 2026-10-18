<?php

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\View;
use Wsmallnews\FilamentNestedset\Commands\MakeNestedsetPageCommand;
use Wsmallnews\FilamentNestedset\FilamentNestedsetServiceProvider;

function providerAssets(): array
{
    $provider = new FilamentNestedsetServiceProvider(app());

    return (new ReflectionMethod($provider, 'getAssets'))->invoke($provider);
}

test('both alpine components are always registered', function () {
    config()->set('sn-filament-nestedset.autoload_assets', false);

    $assets = providerAssets();

    expect($assets)->toHaveCount(2)
        ->and($assets[0])->toBeInstanceOf(AlpineComponent::class)
        ->and($assets[0]->getId())->toBe('filament-nestedset')
        ->and($assets[1]->getId())->toBe('sn-sidebar-nestedset');
});

test('the stylesheet is only registered while autoload_assets is on', function () {
    config()->set('sn-filament-nestedset.autoload_assets', true);

    $assets = providerAssets();

    expect($assets)->toHaveCount(3)
        ->and($assets[2])->toBeInstanceOf(Css::class)
        ->and($assets[2]->getId())->toBe('filament-nestedset-styles');
});

test('registered assets resolve to a real source url', function () {
    config()->set('sn-filament-nestedset.autoload_assets', true);

    expect(FilamentAsset::getAlpineComponentSrc('sn-sidebar-nestedset', 'wsmallnews/filament-nestedset'))->not->toBeNull()
        ->and(FilamentAsset::getAlpineComponentSrc('filament-nestedset', 'wsmallnews/filament-nestedset'))->not->toBeNull();
});

test('views, translations and config are published under the package namespace', function () {
    expect(View::exists('sn-filament-nestedset::filament.pages.nestedset-page'))->toBeTrue()
        ->and(config('sn-filament-nestedset.autoload_assets'))->not->toBeNull()
        ->and(__('sn-filament-nestedset::nestedset.nestedset.empty_label'))
        ->not->toBe('sn-filament-nestedset::nestedset.nestedset.empty_label');
});

test('the make command is registered', function () {
    expect((new ReflectionMethod(FilamentNestedsetServiceProvider::class, 'getCommands'))->invoke(new FilamentNestedsetServiceProvider(app())))
        ->toContain(MakeNestedsetPageCommand::class);
});

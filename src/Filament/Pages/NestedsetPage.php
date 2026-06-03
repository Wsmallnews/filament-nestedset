<?php

namespace Wsmallnews\FilamentNestedset\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset;

abstract class NestedsetPage extends Page
{
    use InteractsWithNestedset;

    protected static ?string $model = null;

    protected static ?string $modelLabel = null;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBars3BottomRight;

    protected static string | BackedEnum | null $activeNavigationIcon = Heroicon::Bars3BottomRight;

    protected string $view = 'sn-filament-nestedset::filament.pages.nestedset-page';
}

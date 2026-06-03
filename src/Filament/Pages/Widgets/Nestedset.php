<?php

namespace Wsmallnews\FilamentNestedset\Filament\Pages\Widgets;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset;

abstract class Nestedset extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithNestedset;
    use InteractsWithSchemas;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'sn-filament-nestedset::filament.pages.widgets.nestedset';
}

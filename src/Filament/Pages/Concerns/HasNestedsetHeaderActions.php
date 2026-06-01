<?php

namespace Wsmallnews\FilamentNestedset\Filament\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportEvents\Event;

trait HasNestedsetHeaderActions
{
    protected function getHeaderActions(): array
    {
        return [
            $this->createAction(),
            $this->fixNestedsetAction(),
        ];
    }

    protected function createAction(): Action
    {
        return Action::make('create')
            ->label(fn(): string => __('filament-actions::create.single.label', ['label' => static::getModelLabel()]))
            ->icon(Heroicon::Plus)
            ->action(fn(): Event => $this->dispatch('sn-open-create-modal'));
    }

    protected function fixNestedsetAction(): Action
    {
        return Action::make('fixNestedset')
            ->label(__('sn-filament-nestedset::nestedset.action.fix_nestedset'))
            ->icon(Heroicon::Wrench)
            ->action(fn(): Event => $this->dispatch('sn-open-fix-nestedset-modal'));
    }
}

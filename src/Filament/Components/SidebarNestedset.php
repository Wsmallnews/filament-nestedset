<?php

namespace Wsmallnews\FilamentNestedset\Filament\Components;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset;

class SidebarNestedset extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithNestedset;
    use InteractsWithSchemas;

    #[Reactive]
    public int | string | null $activeId = null;

    public bool $searchable = true;

    public bool $sortable = true;

    #[Locked]
    public ?string $view = 'sn-filament-nestedset::filament.components.sidebar-nestedset';

    #[Locked]
    public ?string $contentComponent = 'sn-filament-nestedset::filament.components.sidebar-nestedset-content';

    #[Locked]
    public ?string $recordView = 'sn-filament-nestedset::filament.components.sidebar-nestedset-record';

    public function getSearchable(): bool
    {
        return $this->searchable;
    }

    public function getSortable(): bool
    {
        return $this->sortable;
    }

    public function render(): View
    {
        return view($this->getView());
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function getRecordView(): string
    {
        return $this->recordView;
    }

    public function getContentComponent(): string
    {
        return $this->contentComponent;
    }

    /**
     * Whether the node is selected (host feeds the id back via $activeId).
     */
    protected function getHasActive(Model $record): bool
    {
        return ! blank($this->activeId) && $record->getKey() == $this->activeId;
    }

    protected function getRecordUrl(Model $record): string | HtmlString | null
    {
        return null;
    }

    /**
     * Narrow sidebar default: collapse row actions into an ActionGroup
     */
    public static function getRowActionsInGroup(): bool
    {
        return static::$rowActionsInGroup ?? true;
    }

    /**
     * Narrow sidebar default: icon-only buttons when actions are flat
     */
    public static function getShowRowActionLabels(): bool
    {
        return static::$showRowActionLabels ?? false;
    }
}

<?php

namespace Wsmallnews\FilamentNestedset\Livewire\Components;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Component;

class Nestedset extends Component
{
    public ?string $showLevel = null;

    public ?string $emptyLabel = '';

    public ?string $view = 'sn-filament-nestedset::livewire.components.nestedset';

    public ?string $recordView = 'sn-filament-nestedset::components.nestedset-record';

    public ?string $model = null;

    public string $recordTitleAttribute = 'name';

    public function getShowLevel(): ?string
    {
        return $this->showLevel;
    }

    public function getEmptyLabel(): ?string
    {
        return $this->emptyLabel;
    }

    public function getRecordTitleAttribute(): string
    {
        return $this->recordTitleAttribute;
    }

    public function getRecordLabel(Model $record): HtmlString | string
    {
        return $record->{$this->recordTitleAttribute} ?? ' ';
    }

    public function getRecordUrl(Model $record): string | HtmlString | null
    {
        return null;
    }

    public function getHasActive(Model $record): bool
    {
        return false;
    }

    public function getNestedset(): Collection
    {
        $nestedset = $this->getQuery()->withDepth()->get();

        if (! is_null($this->getShowLevel())) {
            $nestedset = $nestedset->filter(function ($record) {
                return $record->depth <= $this->getShowLevel();
            });
        }

        return $nestedset->toTree();
    }

    protected function getQuery(): Builder
    {
        $model = $this->model;
        if (is_null($model)) {
            throw new \Exception('Please set the model or custom `getNestedset` method in the nestedset component.');
        }

        $scopes = [];
        // 自定义 scope
        if (method_exists($this, 'nestedScoped')) {
            $scopes = array_merge($scopes, $this->nestedScoped());
        }

        if ($scopes) {
            $query = $model::scoped($scopes);
        } else {
            $query = (new $model)->newScopedQuery();
        }

        // 自定义条件
        if (method_exists($this, 'getEloquentQuery')) {
            $query = $this->getEloquentQuery($query);
        }

        $query = $query->defaultOrder();

        return $query;
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function getRecordView(): string
    {
        return $this->recordView;
    }

    public function render(): View
    {
        return view($this->getView());
    }
}

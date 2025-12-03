<?php

namespace Wsmallnews\FilamentNestedset\Livewire\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class Nestedset extends Component
{
    public ?string $showLevel = null;

    public ?string $emptyLabel = '';

    public string $recordTitleAttribute = 'name';

    public ?string $view = 'sn-filament-nestedset::livewire.components.categories';

    public ?string $recordView = 'sn-filament-nestedset::category';

    public ?string $style = 'simple';        // vivid=鲜明的, simple=简单的

    protected static ?string $model = null;

    protected static function getModel(): ?string
    {
        return static::$model;
    }

    public function getShowLevel(): ?string
    {
        return $this->showLevel;
    }

    public function getEmptyLabel(): ?string
    {
        return $this->emptyLabel;
    }

    public function getRecordTitleAttribute(): ?string
    {
        return $this->recordTitleAttribute;
    }

    public function getRecordLabel(Model $record): HtmlString | string
    {
        return $record->{$this->getRecordTitleAttribute()} ?? ' ';
    }

    public function getHasActive(Model $record): bool
    {
        return false;
    }

    public function getNestedset()
    {
        $nestedset = $this->getQuery()->withDepth()->get();

        if (! is_null($this->getShowLevel())) {
            $nestedset = $nestedset->filter(function ($record) {
                return $record->depth <= $this->getShowLevel();
            });
        }

        return $nestedset->toTree();
    }

    protected function getQuery()
    {
        $model = static::getModel();
        if (is_null($model)) {
            throw new \Exception('Please set the model or custom `getNesteds` method in the nestedset component.');
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

    public function getStyle(): string
    {
        return $this->style;
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function getRecordView(): string
    {
        return $this->recordView;
    }

    public function render()
    {
        return view($this->getView());
    }
}

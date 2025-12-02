<?php

namespace Wsmallnews\FilamentNestedset\Livewire\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class Nestedset extends Component
{
    public ?string $view = 'sn-filament-nestedset::livewire.components.categories';

    public ?string $itemView = 'sn-filament-nestedset::category';

    public ?string $style = 'simple';        // vivid=鲜明的, simple=简单的

    public string $recordTitleAttribute = 'name';

    protected static ?string $showLevel = null;

    protected static ?string $emptyLabel = '';

    protected static ?string $model = null;

    public function getShowLevel(): ?int
    {
        return static::$showLevel;
    }

    public function getEmptyLabel(): ?string
    {
        return static::$emptyLabel;
    }

    public static function getModel()
    {
        return static::$model;
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return static::$recordTitleAttribute;
    }

    public function getRecordLabel(Model $record): HtmlString | string
    {
        return $record->{static::getRecordTitleAttribute()} ?? ' ';
    }

    public function getItemView(): string
    {
        return $this->itemView;
    }

    protected function getQuery()
    {
        $model = static::getModel();

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

    public function getNestedset()
    {
        $nestedset = $this->getQuery()->withDepth()->get();

        if (static::getShowLevel() !== null) {
            $nestedset = $nestedset->filter(function ($record) {
                return $record->depth <= static::getShowLevel();
            });
        }

        return $nestedset->toTree();
    }

    public function render()
    {
        return view($this->view);
    }
}

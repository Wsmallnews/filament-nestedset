<?php

namespace Wsmallnews\FilamentNestedset\Filament\Pages\Components;

use Filament\Facades\Filament;
use Filament\Pages\BasePage;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Kalnoy\Nestedset\NodeTrait;
use Livewire\Attributes\On;
use Wsmallnews\FilamentNestedset\Exceptions\NestedsetException;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\HasNestedsetActions;
use Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage;

class Nestedset extends BasePage
{
    use HasNestedsetActions;

    /**
     * Page 类名，从 Page 通过 Blade 传入
     */
    public ?string $pageClass = null;

    /**
     * 当前激活的 Tab，从 Page 通过 Blade 传入
     */
    public ?string $activeTab = null;

    /**
     * 嵌套集模型类名
     */
    public ?string $model = null;

    /**
     * Tab 过滤字段名
     */
    public ?string $tabFieldName = null;

    /**
     * 节点标题属性名
     */
    public string $recordTitleAttribute = 'name';

    /**
     * 嵌套层级限制
     */
    public ?int $level = null;

    /**
     * 空状态标签
     */
    public ?string $emptyLabel = null;

    /**
     * 空状态提示标签
     */
    public ?string $emptyTipLabel = null;

    /**
     * 是否关联多租户
     */
    public bool $isScopedToTenant = true;

    protected ?NestedsetPage $nestedsetPage = null;

    protected string $view = 'sn-filament-nestedset::filament.pages.components.nestedset';

    /**
     * @throws NestedsetException
     */
    public function mount(): void
    {
        $model = $this->model;

        $concerns = class_uses($model);

        if (! \in_array(NodeTrait::class, $concerns, true)) {
            throw new NestedsetException(
                \sprintf('Model should use %s', NodeTrait::class),
            );
        }
    }

    #[On('sn-filament-nestedset-updated')]
    public function refresh(): void
    {
        // Re-render component
    }

    #[On('sn-open-create-modal')]
    public function openCreateModal(): void
    {
        $this->mountAction('create');
    }

    #[On('sn-open-fix-nestedset-modal')]
    public function openFixNestedsetModal(): void
    {
        $this->mountAction('fixNestedset');
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function getEmptyLabel(): ?string
    {
        return $this->emptyLabel ?? __('sn-filament-nestedset::nestedset.nestedset.empty_label');
    }

    public function getEmptyTipLabel(): ?string
    {
        return $this->emptyTipLabel ?? __('sn-filament-nestedset::nestedset.nestedset.empty_tip_label');
    }

    public function getRecordLabel(Model $record): HtmlString | string
    {
        return $this->getNestedsetPage()->getRecordLabel($record);
    }

    public function hasInfolist(): bool
    {
        return count($this->infolistSchema()) > 0;
    }

    public function infolistSchema(): array
    {
        return $this->getNestedsetPage()->infolistSchema();
    }

    public function getInfolistAlignment(): Alignment
    {
        return $this->getNestedsetPage()->getInfolistAlignment();
    }

    public function getInfolistHiddenEndpoint(): string
    {
        return $this->getNestedsetPage()->getInfolistHiddenEndpoint();
    }

    public function showCreateChildNodeActionInRow(): bool
    {
        return config('sn-filament-nestedset.show_create_child_node_action_in_row') ?? true;
    }

    public function canBeDeleted(Model $record): bool
    {
        if (
            config('sn-filament-nestedset.allow_delete_parent') === false
            && $record->children->isNotEmpty()
        ) {
            return false;
        }

        return ! (config('sn-filament-nestedset.allow_delete_root') === false && $record->children->isNotEmpty() && $record->isRoot());
    }

    // ========== 查询构建 ==========

    protected function getQuery(): Builder
    {
        $model = $this->model;
        $page = $this->getNestedsetPage();

        $scopes = [];
        if ($this->isScopedToTenant && ($tenant = Filament::getTenant())) {
            $scopes['team_id'] = $tenant->id;
        }

        if ($this->tabFieldName) {
            $scopes[$this->tabFieldName] = $this->activeTab;
        }

        // 自定义 scope（委托给 Page）
        $customScopes = $page->nestedScoped();
        if (! empty($customScopes)) {
            $scopes = array_merge($scopes, $customScopes);
        }

        if ($scopes) {
            $query = $model::scoped($scopes);
        } else {
            $query = (new $model)->newScopedQuery();
        }

        // 自定义条件（委托给 Page）
        $query = $page->getEloquentQuery($query);

        $query = $query->defaultOrder();

        return $query;
    }

    protected function getViewData(): array
    {
        $nestedset = $this->getQuery()->withDepth()->get()->toTree();

        return [
            'nestedset' => $nestedset,
        ];
    }

    protected function getNestedsetPage(): NestedsetPage
    {
        if ($this->nestedsetPage === null) {
            $this->nestedsetPage = app($this->pageClass);
        }

        return $this->nestedsetPage;
    }
}

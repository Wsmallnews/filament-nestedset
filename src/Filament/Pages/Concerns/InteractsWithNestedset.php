<?php

namespace Wsmallnews\FilamentNestedset\Filament\Pages\Concerns;

use Filament\Facades\Filament;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Kalnoy\Nestedset\NodeTrait;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Wsmallnews\FilamentNestedset\Exceptions\NestedsetException;

use function Filament\Support\get_model_label;

trait InteractsWithNestedset
{
    use HasNestedsetActions;
    use HasTabs;

    #[Url]
    public ?string $activeTab = null;

    protected static ?int $level = null;

    protected static ?string $model = null;

    protected static ?string $modelLabel = null;

    protected static ?string $emptyLabel;

    protected static ?string $emptyTipLabel;

    protected static string $recordTitleAttribute = 'name';

    protected static bool $isScopedToTenant = true;

    protected static ?string $tabFieldName = null;

    protected static Alignment $infolistAlignment = Alignment::Right;

    protected static string $infolistHiddenEndpoint = 'md';

    public function mountInteractsWithNestedset(): void
    {
        $this->loadDefaultActiveTab();
        $this->validateModel();
    }

    /**
     * @throws NestedsetException
     */
    protected function validateModel(): void
    {
        $model = static::getModel();

        if (! $model) {
            throw new NestedsetException('Nestedset model is not set');
        }

        if (! in_array(NodeTrait::class, class_uses_recursive($model), true)) {
            throw new NestedsetException(
                sprintf('Model should use %s', NodeTrait::class),
            );
        }
    }

    /**
     * 覆盖 hasTabs 中的 updatedActiveTab 方法
     */
    public function updatedActiveTab(): void {}

    #[On('sn-filament-nestedset-updated')]
    public function refresh(): void
    {
        // Re-render component
    }

    public static function getLevel(): ?int
    {
        return static::$level;
    }

    public static function getModel(): ?string
    {
        return static::$model;
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel ?? get_model_label(static::getModel());
    }

    public static function getEmptyLabel(): ?string
    {
        return static::$emptyLabel ?? __('sn-filament-nestedset::nestedset.nestedset.empty_label');
    }

    public static function getEmptyTipLabel(): ?string
    {
        return static::$emptyTipLabel ?? __('sn-filament-nestedset::nestedset.nestedset.empty_tip_label');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return static::$recordTitleAttribute;
    }

    public static function isScopedToTenant(): bool
    {
        return static::$isScopedToTenant;
    }

    public static function getTabFieldName(): ?string
    {
        return static::$tabFieldName;
    }

    public static function getInfolistAlignment(): Alignment
    {
        return static::$infolistAlignment;
    }

    public static function getInfolistHiddenEndpoint(): string
    {
        return static::$infolistHiddenEndpoint;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
            ]);
    }

    protected function getRecordLabel(Model $record): HtmlString | string
    {
        return $record->{static::getRecordTitleAttribute()} ?? ' ';
    }

    /**
     * 自定义 kalnoy/nestedset 的 scoped 外条件
     *
     * @return array<string, mixed>
     */
    protected function nestedScoped(): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    protected function schema(array $arguments): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    protected function infolistSchema(): array
    {
        return [];
    }

    protected function hasInfolist(): bool
    {
        return count($this->infolistSchema()) > 0;
    }

    /**
     * 自定义 Eloquent 查询条件
     */
    protected function getEloquentQuery($query)
    {
        return $query;
    }

    protected function showCreateChildNodeActionInRow(): bool
    {
        return config('sn-filament-nestedset.show_create_child_node_action_in_row') ?? true;
    }

    protected function getNestedset()
    {
        return $this->getQuery()->withDepth()->get()->toTree();
    }

    protected function canBeDeleted(Model $record): bool
    {
        if (
            config('sn-filament-nestedset.allow_delete_parent') === false
            && $record->children->isNotEmpty()
        ) {
            return false;
        }

        return ! (config('sn-filament-nestedset.allow_delete_root') === false && $record->children->isNotEmpty() && $record->isRoot());
    }

    protected function getQuery(): Builder
    {
        $model = static::getModel();

        $scopes = [];
        if (static::isScopedToTenant() && ($tenant = Filament::getTenant())) {
            $scopes['team_id'] = $tenant->id;
        }

        if (static::getTabFieldName()) {
            $scopes[static::getTabFieldName()] = $this->activeTab;
        }

        $customScopes = $this->nestedScoped();
        if (! empty($customScopes)) {
            $scopes = array_merge($scopes, $customScopes);
        }

        if ($scopes) {
            $query = $model::scoped($scopes);
        } else {
            $query = (new $model)->newScopedQuery();
        }

        return $this->getEloquentQuery($query)->defaultOrder();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'level' => static::getLevel(),
            'emptyLabel' => static::getEmptyLabel(),
            'emptyTipLabel' => static::getEmptyTipLabel(),
        ];
    }
}

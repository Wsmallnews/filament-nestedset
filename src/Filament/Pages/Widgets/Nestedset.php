<?php

namespace Wsmallnews\FilamentNestedset\Filament\Pages\Widgets;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportEvents\Event;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\HasNestedsetHeaderActions;

use function Filament\Support\get_model_label;

abstract class Nestedset extends Widget
{
    use HasNestedsetHeaderActions;
    use HasTabs;

    protected static ?string $model = null;

    protected static ?string $modelLabel = null;

    protected static ?int $level = null;

    protected static ?string $emptyLabel;

    protected static ?string $emptyTipLabel;

    protected static bool $isScopedToTenant = true;

    protected static string $recordTitleAttribute = 'name';

    protected static ?string $tabFieldName = null;

    protected static Alignment $infolistAlignment = Alignment::Right;

    protected static string $infolistHiddenEndpoint = 'md';

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'sn-filament-nestedset::filament.pages.widgets.nestedset';


    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    /**
     * 覆盖 hasTabs 中的 updatedActiveTab 方法
     */
    public function updatedActiveTab(): void {}

    public static function getModel(): ?string
    {
        return static::$model;
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel ?? get_model_label(static::getModel());
    }

    public static function getLevel(): ?int
    {
        return static::$level;
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

    public static function getRecordLabel(Model $record): HtmlString | string
    {
        return $record->{static::getRecordTitleAttribute()} ?? ' ';
    }

    public static function getInfolistAlignment(): Alignment
    {
        return static::$infolistAlignment;
    }

    public static function getInfolistHiddenEndpoint(): string
    {
        return static::$infolistHiddenEndpoint;
    }

    /**
     * 自定义 kalnoy/nestedset 的 scoped 外条件
     */
    public static function nestedScoped(): array
    {
        return [];
    }

    /**
     * 自定义 Eloquent 查询条件
     */
    public static function getEloquentQuery($query)
    {
        return $query;
    }

    public static function schema(array $arguments): array
    {
        return [];
    }

    public static function infolistSchema(): array
    {
        return [];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
            ]);
    }
}

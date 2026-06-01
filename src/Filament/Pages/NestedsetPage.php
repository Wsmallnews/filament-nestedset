<?php

namespace Wsmallnews\FilamentNestedset\Filament\Pages;

use BackedEnum;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\HasNestedsetHeaderActions;

use function Filament\Support\get_model_label;

abstract class NestedsetPage extends Page
{
    use CanUseDatabaseTransactions;
    use HasNestedsetHeaderActions;
    use HasTabs;
    use HasUnsavedDataChangesAlert;
    use InteractsWithFormActions;

    #[Url]
    public ?string $activeTab = null;

    protected static ?int $level = null;

    protected static ?string $emptyLabel;

    protected static ?string $emptyTipLabel;

    protected static ?string $model = null;

    protected static ?string $modelLabel = null;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBars3BottomRight;

    protected static string | BackedEnum | null $activeNavigationIcon = Heroicon::Bars3BottomRight;

    protected static bool $isScopedToTenant = true;

    protected static string $recordTitleAttribute = 'name';

    protected string $view = 'sn-filament-nestedset::filament.pages.nestedset-page';

    protected static ?string $tabFieldName = null;

    protected static Alignment $infolistAlignment = Alignment::Right;

    protected static string $infolistHiddenEndpoint = 'md';

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

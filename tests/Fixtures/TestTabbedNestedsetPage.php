<?php

namespace Wsmallnews\FilamentNestedset\Tests\Fixtures;

use Filament\Forms;
use Filament\Schemas\Components\Tabs\Tab;
use Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage;

/**
 * 带 Tab 分区的页面夹具：activeTab 参与 getQuery() 的嵌套集 scope。
 */
class TestTabbedNestedsetPage extends NestedsetPage
{
    protected static ?string $model = TestCategory::class;

    protected static bool $isScopedToTenant = false;

    protected static ?int $level = null;

    protected static ?string $tabFieldName = 'scope_type';

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'web' => Tab::make('Web'),
            'app' => Tab::make('App'),
        ];
    }

    protected function nestedScoped(): array
    {
        return ['scope_id' => 0];
    }

    /**
     * @return array<mixed>
     */
    protected function schema(array $arguments): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
        ];
    }
}

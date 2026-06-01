<x-filament-widgets::widget>
    @if ($headerActions = $this->getHeaderActions())
        <x-filament::section>
            <x-slot name="afterHeader">
                <x-filament::actions :actions="$headerActions" />
            </x-slot>
        </x-filament::section>
    @endif
    
    {{ $this->content }}

    <livewire:sn-filament-nestedset-fi-nestedset
        :page-class="static::class"
        :active-tab="$activeTab"
        :model="static::getModel()"
        :tab-field-name="static::getTabFieldName()"
        :record-title-attribute="static::getRecordTitleAttribute()"
        :level="static::getLevel()"
        :empty-label="static::getEmptyLabel()"
        :empty-tip-label="static::getEmptyTipLabel()"
        :is-scoped-to-tenant="static::isScopedToTenant()"
        :key="'fi-components-sn-nestedset-' . $record->id . '-' . $record->level"
    />
</x-filament-widgets::widget>

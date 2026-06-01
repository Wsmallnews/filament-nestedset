<x-filament-widgets::widget class="flex flex-col gap-4">
    @if ($headerActions = $this->getHeaderActions())
        <div class="flex justify-end items-center">
            <x-filament::actions :actions="$headerActions" />
        </div>
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

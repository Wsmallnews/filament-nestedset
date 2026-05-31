@php
    $pageClass = static::class;
@endphp

<x-filament-panels::page>
    {{ $this->content }}

    <livewire:sn-filament-nestedset-fi-nestedset
        :page-class="$pageClass"
        :active-tab="$activeTab"
        :model="static::getModel()"
        :tab-field-name="static::getTabFieldName()"
        :record-title-attribute="static::getRecordTitleAttribute()"
        :level="static::getLevel()"
        :empty-label="static::getEmptyLabel()"
        :empty-tip-label="static::getEmptyTipLabel()"
        :is-scoped-to-tenant="static::isScopedToTenant()"
        :key="'fi-components-sn-nestedset'"
    />
</x-filament-panels::page>

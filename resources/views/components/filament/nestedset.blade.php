@props([
    'nestedset',
    'level',
    'emptyLabel',
    'emptyTipLabel',
])

<div>
    <div
        class="fi-sn-nestedset-container overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        wire:key="sn-nestedset-items-wrapper"
    >
        <div
            class="fi-sn-nestedset divide-y divide-gray-200 dark:divide-white/10"
            data-id
            data-sortable-container
            @if (\Filament\Support\Facades\FilamentView::hasSpaMode())
                x-load="visible || event (ax-modal-opened)"
            @else
                x-load
            @endif
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filament-nestedset', 'wsmallnews/filament-nestedset') }}"
            x-data="nestedsetManager({})"
        >
            @forelse($nestedset as $nestedsetKey => $record)
                <x-sn-filament-nestedset::filament.nestedset-record 
                    :record="$record"
                    :level="$level"
                    key="sn-filament-nestedset-fi-record-component-{{ $record->getKey() }}" />
            @empty
                <x-filament::empty-state
                    :contained="false"
                    icon="heroicon-m-document-text"
                    icon-color="gray"
                >
                    <x-slot name="heading">
                        {{ $emptyLabel }}
                    </x-slot>

                    <x-slot name="description">
                        {{ $emptyTipLabel }}
                    </x-slot>
                </x-filament::empty-state>
            @endforelse
        </div>
    </div>
    
    <x-filament-actions::modals />
</div>

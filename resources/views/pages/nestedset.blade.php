@php
    $level = $this->getLevel();
@endphp

<x-filament-panels::page>
    {{ $this->content }}

    <div
        class="fi-sn-tree-container overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        wire:key="tree-items-wrapper"
    >
        <div
            class="fi-sn-tree divide-y divide-gray-200 dark:divide-white/10"
            data-id
            data-sortable-container
            @if (\Filament\Support\Facades\FilamentView::hasSpaMode())
                x-load="visible || event (ax-modal-opened)"
            @else
                x-load
            @endif
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filament-nestedset', 'wsmallnews/filament-nestedset') }}"
            x-data="treeManager({})"
        >
            @forelse($nestedset as $treeKey => $record)
                <x-sn-filament-nestedset::pages.nestedset-record :record="$record" key="tree-component-{{ $record->getKey() }}" :level="$level" />
            @empty
                <x-filament::empty-state
                    :contained="false"
                    icon="heroicon-m-document-text"
                    icon-color="gray"
                >
                    <x-slot name="heading">
                        {{ $this->getEmptyLabel() }}
                    </x-slot>

                    <x-slot name="description">
                        {{ $this->getEmptyTipLabel() }}
                    </x-slot>
                </x-filament::empty-state>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
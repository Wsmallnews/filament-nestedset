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
            @forelse($tree as $treeKey => $item)
                <x-sn-filament-nestedset::tree-item :item="$item" key="tree-component-{{ $item->getKey() }}" :level="$level" />
            @empty
                <div 
                    class="fi-sn-tree-empty w-full px-3 py-2 text-center"
                >
                    {{ $emptyLabel ?: __('sn-filament-nestedset::nestedset.tree.empty_label')}}
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
@props([
    'nestedset',
    'level',
    'emptyLabel',
    'emptyTipLabel',
])

<div>
    {{-- 首次加载整树入场动画（sn-nestedset-enter，纯 CSS 关键帧）：不用 x-collapse，
         避免其依赖 transitionend 的展开在加载时序不利时卡死在 height:0 并阻断
         x-load 的 visible 策略（IO 判定不可见 → 模块永不加载 → 展开折叠失效） --}}
    <div
        class="fi-sn-nestedset-container sn-nestedset-enter overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        wire:key="sn-nestedset-items-wrapper"
    >
        <div
            class="fi-sn-nestedset @container divide-y divide-gray-200 dark:divide-white/10"
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
                    :animate-load="true"
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

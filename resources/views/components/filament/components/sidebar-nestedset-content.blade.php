@props(['nestedset', 'level'])

@php
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
@endphp

<div class="flex items-center gap-1.5 p-2 pb-1">
    @if ($this->getSearchable())
        <div class="min-w-0 flex-1 [&_.fi-icon]:size-3.5 [&_.fi-input-wrp-prefix]:!ps-2 [&_.fi-input-wrp-prefix]:!pe-1">
            <x-filament::input.wrapper inline-prefix :prefix-icon="Heroicon::OutlinedMagnifyingGlass" class="h-8">
                <x-filament::input
                    type="search"
                    x-model.debounce.200ms="search"
                    :placeholder="__('sn-filament-nestedset::nestedset.sidebar.search_placeholder')"
                    class="!h-full !px-1.5 !py-1 !text-xs !leading-4"
                />
            </x-filament::input.wrapper>
        </div>
    @else
        <div class="min-w-0 flex-1"></div>
    @endif

    @if ($this->getSortable())
        <x-filament::icon-button
            :icon="Heroicon::OutlinedArrowsUpDown"
            color="gray"
            size="sm"
            x-on:click="toggleSortMode()"
            ::class="sortMode ? 'text-primary-500 dark:text-primary-600' : ''"
            :tooltip="__('sn-filament-nestedset::nestedset.sidebar.sort_toggle')"
        />
    @endif
</div>

@if ($this->getSortable())
    <div x-show="sortMode" x-cloak x-transition:opacity
        class="mx-2 mb-1 flex items-center justify-between rounded-md bg-primary-50 px-2 py-1.5 text-xs text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
        <span>{{ __('sn-filament-nestedset::nestedset.sidebar.sort_active') }}</span>
        <x-filament::icon-button
            :icon="Heroicon::Check"
            color="primary"
            size="xs"
            x-on:click="exitSortMode()"
            :tooltip="__('sn-filament-nestedset::nestedset.sidebar.sort_done')"
        />
    </div>
@endif

{{ $slot }}

<nav class="flex-1 px-2 pb-2 text-sm">
    <ul data-sidebar-list data-id class="flex w-full flex-col" role="menu">
        @forelse ($nestedset as $record)
            <x-dynamic-component
                @class(['w-full'])
                :component="$this->getRecordView()"
                :key="'sn-filament-nestedset-sidebar-record-' . $record->getKey()"
                :record="$record"
                :level="$level"
                :current-level="1"
            />
        @empty
            <x-filament::empty-state :contained="false" :icon="Heroicon::OutlinedFolderOpen" icon-color="gray">
                <x-slot name="heading">
                    {{ $this->getEmptyLabel() ?: __('sn-filament-nestedset::nestedset.nestedset.empty_label') }}
                </x-slot>
                <x-slot name="description">
                    {{ $this->getEmptyTipLabel() ?: __('sn-filament-nestedset::nestedset.nestedset.empty_tip_label') }}
                </x-slot>
            </x-filament::empty-state>
        @endforelse

    </ul>

    {{-- 搜索无结果提示：仅在搜索中且结果为空时显示（服务端默认隐藏，显隐由 applyVisibility 命令式切换） --}}
    <div data-sidebar-no-results style="display: none" class="px-2 py-4 text-center text-xs text-gray-400">
        {{ __('sn-filament-nestedset::nestedset.sidebar.search_no_results') }}
    </div>
</nav>

{{-- 底部固定创建按钮：sticky 贴滚动容器底（aside / 移动端抽屉都适用），树高度变化（拖拽/展开）
     时不再跳动；半透明 + 模糊背景让滚动内容从下方经过时被柔和遮住，不产生硬色块 --}}
<div class="sticky bottom-0 z-10 bg-white/85 px-3 pt-2 pb-1 backdrop-blur-sm dark:bg-gray-900/85">
    {{ $this->createAction()
    ->button()
    ->label(__('sn-filament-nestedset::nestedset.action.create'))
    ->size(Size::Small)
    ->outlined()
    ->extraAttributes(['class' => 'w-full']) }}
</div>

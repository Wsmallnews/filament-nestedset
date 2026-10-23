@props(['nestedset', 'level'])

@php
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
@endphp

<div class="flex items-center gap-1.5 p-2 pb-1">
    <div class="min-w-0 flex-1">
        @if ($this->getSearchable())
            <x-filament::input.wrapper inline-prefix :prefix-icon="Heroicon::OutlinedMagnifyingGlass" class="sn-nestedset-input-sm h-8">
                <x-filament::input
                    type="search"
                    x-model.debounce.200ms="search"
                    :placeholder="__('sn-filament-nestedset::nestedset.sidebar.search_placeholder')"
                />
            </x-filament::input.wrapper>
        @endif
    </div>

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

    {{-- Shown by JS only while searching with no matches (hidden on the server). --}}
    <div data-sidebar-no-results style="display: none" class="px-2 py-4 text-center text-xs text-gray-400">
        {{ __('sn-filament-nestedset::nestedset.sidebar.search_no_results') }}
    </div>
</nav>

<div class="sticky bottom-0 z-10 bg-white/85 px-3 pt-2 pb-3 backdrop-blur-sm dark:bg-gray-900/85">
    {{ $this->createAction()
    ->button()
    ->label(__('sn-filament-nestedset::nestedset.action.create'))
    ->size(Size::Small)
    ->outlined()
    ->extraAttributes(['class' => 'w-full']) }}
</div>

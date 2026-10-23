@props(['record', 'level', 'currentLevel' => 1])

@php
use Filament\Support\Icons\Heroicon;

$hasChild = $record->children->isNotEmpty();
$hasActive = $this->getHasActive($record);
// whether the level limit leaves room for child rows
$hasNextLevel = is_null($level) || $level > ((int) $record->depth + 1);
$nextLevel = $currentLevel + 1;
@endphp

<li
    class="flex w-full flex-col"
    wire:key="sn-filament-nestedset-sidebar-node-{{ $record->getKey() }}"
    data-sidebar-item
    data-id="{{ $record->getKey() }}"
    @if ($hasChild)
        x-data="{ isExpanded: $persist(true).as('sn-sidebar-tree-{{ $record->getKey() }}') }"
        :aria-expanded="(isExpanded || isSearching) ? 'true' : 'false'"
    @endif
    role="menuitem"
>
    <div
        @class([
            'group sn-nestedset-row',
            'is-active' => $hasActive,
        ])
        data-sidebar-row
        data-id="{{ $record->getKey() }}"
        data-label="{{ $record->name }}"
    >
        @for ($i = 0; $i < $currentLevel - 1; $i++)
            <div class="w-3 shrink-0"></div>
        @endfor

        @if ($hasChild)
            <button type="button" x-on:click.stop="isExpanded = !isExpanded" tabindex="-1" aria-hidden="true"
                :class="sortMode ? 'hidden' : ''"
                class="grid size-4 shrink-0 cursor-pointer place-items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-filament::icon :icon="Heroicon::ChevronRight" class="size-3.5 transition-transform duration-200" ::class="(isExpanded || isSearching) ? 'rotate-90' : ''" />
            </button>
        @else
            <span :class="sortMode ? 'hidden' : ''" class="size-4 shrink-0"></span>
        @endif

        <button
            type="button"
            data-sortable-handle
            x-cloak
            :class="sortMode ? '' : 'hidden'"
            class="grid size-4 shrink-0 cursor-grab place-items-center text-gray-400 hover:text-gray-600 active:cursor-grabbing dark:hover:text-gray-300"
            title="{{ __('sn-filament-nestedset::nestedset.sidebar.drag_handle') }}"
            tabindex="-1"
            @click.stop
        >
            <x-filament::icon :icon="Heroicon::Bars2" class="size-3.5" />
        </button>

        <a
            class="sn-nestedset-row-label"
            wire:click="$dispatch('sn-filament-nestedset-{{ $hasChild ? 'node' : 'leaf' }}-click', { recordId: '{{ $record->getKey() }}', hasChild: {{ $hasChild ? 1 : 0 }} })"
            x-bind:class="sortMode ? 'pointer-events-none' : ''"
            @if (filled($url = $this->getRecordUrl($record)))
                {{ $url }}
            @else
                href="javascript:;"
            @endif
        >
            {{ $this->getRecordLabel($record) }}
        </a>

        <div
            :class="sortMode ? 'hidden' : 'opacity-0 group-hover:opacity-100 group-focus-within:opacity-100'"
            class="flex shrink-0 items-center gap-0.5 transition"
            x-cloak
            @click.stop
        >
            @foreach ($this->getRowActions($record) as $rowAction)
                {{ $rowAction }}
            @endforeach
        </div>
    </div>

    @if ($hasNextLevel)
        <ul data-sidebar-list data-id="{{ $record->getKey() }}" @if ($hasChild) x-show="isExpanded || isSearching" x-collapse @endif class="mt-0.5 flex w-full flex-col" role="menu">
            @if ($hasChild)
                @foreach ($record->children as $child)
                    <x-dynamic-component
                        @class(['w-full'])
                        :component="$this->getRecordView()"
                        :key="'sn-filament-nestedset-sidebar-record-' . $child->getKey()"
                        :record="$child"
                        :level="$level"
                        :current-level="$nextLevel"
                    />
                @endforeach
            @endif
        </ul>
    @endif
</li>

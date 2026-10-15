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
    @if ($hasChild)
        {{-- Render expanded on the server: if initially hidden (x-cloak), a reactive host re-render
            makes Livewire morph add a `hidden` attribute on the display mismatch, which Alpine (inline styles only) cannot remove --}}
        x-data="{ isExpanded: true }"
        :aria-expanded="isExpanded ? 'true' : 'false'"
    @endif
    role="menuitem"
>
    <div @class([
        'group flex w-full items-center gap-1.5 rounded-md py-1.5 pr-2',
        'hover:bg-gray-100 dark:hover:bg-white/5' => ! $hasActive,
        'bg-primary-50 dark:bg-primary-500/10' => $hasActive,
    ])>
        @for ($i = 0; $i < ($currentLevel - 1); $i++)
            <div class="w-3 shrink-0"></div>
        @endfor

        @if ($hasChild)
            <button type="button" x-on:click="isExpanded = ! isExpanded" tabindex="-1" aria-hidden="true"
                class="grid size-4 shrink-0 cursor-pointer place-items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-filament::icon :icon="Heroicon::ChevronRight" class="size-3.5 transition-transform duration-200" ::class="isExpanded ? 'rotate-90' : ''" />
            </button>
        @else
            <span class="size-4 shrink-0"></span>
        @endif

        <a
            wire:click="$dispatch('sn-filament-nestedset-{{ $hasChild ? 'node' : 'leaf' }}-click', { recordId: '{{ $record->getKey() }}', hasChild: {{ $hasChild ? 1 : 0 }} })"
            {{ $this->getRecordUrl($record) ?? 'href=javascript:;' }}
            @class([
                'flex min-w-0 flex-1 items-center gap-1.5 text-left',
                'text-gray-700 dark:text-gray-200 hover:text-primary-500 dark:hover:text-primary-600' => ! $hasActive,
                'text-primary-500 dark:text-primary-600' => $hasActive,
            ])
        >
            {!! $this->getRecordLabel($record) !!}
        </a>

        <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100" @click.stop>
            @foreach ($this->getRowActions($record) as $rowAction)
                {{ $rowAction }}
            @endforeach
        </div>
    </div>

    @if ($hasChild && $hasNextLevel)
        <ul x-show="isExpanded" x-collapse class="mt-0.5 flex w-full flex-col" role="menu">
            @foreach ($record->children as $child)
                <x-dynamic-component
                    @class([
                        'w-full',
                    ])
                    :component="$this->getRecordView()"
                    :key="'sn-filament-nestedset-sidebar-record-' . $child->getKey()"
                    :record="$child"
                    :level="$level"
                    :current-level="$nextLevel"
                />
            @endforeach
        </ul>
    @endif
</li>

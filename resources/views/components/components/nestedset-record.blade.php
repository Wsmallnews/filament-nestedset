@props(['record', 'first', 'last', 'current-level'])

@php
    use Filament\Support\Icons\Heroicon;

    $hasChild = $record->children->count() > 0;
    $hasActive = $this->getHasActive($record);
@endphp

<li
    class="sn-nestedset-record flex flex-col"
    @if ($hasChild)
        x-data="{ isExpanded: {{ $hasActive ? 'true' : 'false' }} }"
        aria-controls="accordionItem{{$record->id}}"
        :aria-expanded="isExpanded ? 'true' : 'false'"
        aria-haspopup="true"
    @endif
    role="menuitem"
>
    <a @class([
            'flex w-full min-h-10 justify-between items-center py-1.5 px-2 gap-2 rounded-md group',
            'hover:text-primary-500 dark:hover:text-primary-600 hover:bg-gray-100 dark:hover:bg-white/5',
            'text-gray-700 dark:text-gray-200' => ! $hasActive,
            'text-primary-500 dark:text-primary-600 bg-primary-50 dark:bg-primary-500/10' => $hasActive,
        ])
        @if ($hasChild)
            @click="isExpanded = ! isExpanded"
        @endif
        wire:click="$dispatch('sn-filament-nestedset-{{ $hasChild ? 'node' : 'leaf' }}-click', { recordId: '{{ $record->getKey() }}', hasChild: {{ $hasChild ? 1 : 0 }} })"
        {{ $this->getRecordUrl($record) ?? 'href=javascript:;' }}
    >
        <div class="flex min-w-0 items-center gap-1">
            @if ($currentLevel > 2)
                @for ($i = 0; $i < ($currentLevel - 2); $i++)
                    <div class="relative flex h-12 w-6 items-center justify-center">
                        <div class="absolute h-full w-px bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                @endfor
            @endif

            @if ($currentLevel > 1)
                <div class="relative flex h-7 w-6 items-center justify-center">
                    @if (! $first)
                        <div class="absolute -top-1/2 bottom-1/2 w-px bg-gray-200 dark:bg-gray-700"></div>
                    @endif
                    @if (! $last)
                        <div class="absolute -bottom-1/2 top-1/2 w-px bg-gray-200 dark:bg-gray-700"></div>
                    @endif
                    <div @class([
                        'relative h-1.5 w-1.5 rounded-full transition-colors',
                        'bg-gray-300 group-hover:bg-primary-500 dark:bg-gray-600 dark:group-hover:bg-primary-600' => ! $hasActive,
                        'bg-primary-500 dark:bg-primary-600' => $hasActive,
                    ])>
                    </div>
                </div>
            @endif
            <span class="min-w-0 truncate">{{ $this->getRecordLabel($record) }}</span>
        </div>
        @if ($hasChild)
            <x-filament::icon :icon="Heroicon::ChevronDown" class="size-5 shrink-0 font-bold text-gray-400 transition-transform duration-300" ::class="isExpanded ? 'rotate-180' : ''" aria-hidden="true" />
        @endif
    </a>

    @if ($hasChild)
        @php
            $currentLevel++;
        @endphp
        <ul @class([
            'sn-nestedset-child w-full flex flex-col gap-0.5 pt-0.5',
        ])
            id="accordionRecordNestedset{{$record->id}}"
            x-cloak x-show="isExpanded"
            aria-labelledby="controlsAccordionRecordNestedset{{$record->id}}"
            x-collapse
            role="menu"
        >
            @foreach ($record->children as $child)
                <x-dynamic-component
                    @class([
                        'w-full',
                    ])
                    :component="$this->getRecordView()"
                    key="sn-filament-nestedset-record-component-{{ $child->getKey() }}"
                    :record="$child"
                    :first="$loop->first"
                    :last="$loop->last"
                    :current-level="$currentLevel"
                />
            @endforeach
        </ul>
    @endif
</li>

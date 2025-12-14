@props(['record', 'first', 'last', 'current-level'])

@php
    $hasChild = $record->children->count() > 0;
    $hasActive = $this->getHasActive($record);
@endphp

<li
    class="flex flex-col"
    @if ($hasChild)
        x-data="{ isExpanded: {{ $hasActive ? 'true' : 'false' }} }"
        aria-controls="accordionItem{{$record->id}}"
        :aria-expanded="isExpanded ? 'true' : 'false'"
        aria-haspopup="true"
    @endif
    role="menuitem"
>
    <a @class([
            'flex w-full h-10 justify-between items-center px-2 gap-2 rounded-md group hover:text-primary-500 dark:hover:text-primary-600 hover:bg-gray-200 dark:hover:bg-gray-800',
            'text-gray-700 dark:text-white' => !$hasActive,
            'text-primary-500 dark:text-primary-600' => $hasActive,
        ])
        @if ($hasChild)
            @click="isExpanded = ! isExpanded"
            wire:click="$dispatch('sn-filament-nestedset-node-click', { recordId: {{ $record->id }}, hasChild: {{ $hasChild ? 1 : 0 }} })"
            {{ $this->getRecordUrl($record) ?? 'href=javascript:;' }}
        @else
            wire:click="$dispatch('sn-filament-nestedset-leaf-click', { recordId: {{ $record->id }}, hasChild: {{ $hasChild ? 1 : 0 }} })"
            {{ $this->getRecordUrl($record) ?? 'href=javascript:;' }}
        @endif
    >
        <div class="flex items-center gap-1">
            @if ($currentLevel > 2)
                @for ($i = 0; $i < ($currentLevel - 2); $i++)
                    <div class="relative flex h-12 w-6 items-center justify-center">
                        <div class="absolute h-full w-px bg-gray-300 dark:bg-gray-600"></div>
                    </div>
                @endfor
            @endif

            @if ($currentLevel > 1)
                <div class="relative flex h-7 w-6 items-center justify-center">
                    @if (!$first)
                        <div class="absolute -top-1/2 bottom-1/2 w-px bg-gray-300 dark:bg-gray-600"></div>
                    @endif
                    @if (!$last)
                        <div class="absolute -bottom-1/2 top-1/2 w-px bg-gray-300 dark:bg-gray-600"></div>
                    @endif
                    <div @class([
                        'relative h-2 w-2 rounded-full',
                        'bg-gray-400 dark:bg-gray-500 group-hover:bg-primary-500' => !$hasActive,
                        'bg-primary-500' => $hasActive,
                    ])>
                    </div>
                </div>
            @endif
            {{ $this->getRecordLabel($record) }}
        </div>
        @if ($hasChild)
            <x-filament::icon icon="heroicon-m-chevron-down" class="size-6 font-bold transform transition-transform duration-300" ::class="isExpanded ? 'rotate-180' : ''" aria-hidden="true" />
        @endif
    </a>

    @if ($hasChild) 
        @php
            $currentLevel++;
        @endphp
        <ul @class([
            'w-full flex flex-col',
        ])
            id="accordionItemCategory{{$record->id}}"
            x-cloak x-show="isExpanded"
            aria-labelledby="controlsAccordionItemOne{{$record->id}}"
            x-collapse
            role="menu"
        >
            @foreach ($record->children as $child)
                <x-dynamic-component 
                    @class([
                        'w-full',
                    ]) 
                    :component="$this->getRecordView()" 
                    key="categories-component-{{ $child->getKey() }}" 
                    :record="$child" 
                    :first="$loop->first" 
                    :last="$loop->last" 
                    :current-level="$currentLevel" 
                />
            @endforeach
        </ul>
    @endif 
</li>

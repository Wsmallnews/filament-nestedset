@props(['record', 'level', 'first', 'last', 'style', 'current-level'])

@php
    $hasChild = $record->children->count() > 0;
@endphp

<li
    @if ($hasChild)
        x-data="{ isExpanded: {{ $record->has_active ? 'true' : 'false' }} }"
        aria-controls="accordionItem{{$record->id}}"
        :aria-expanded="isExpanded ? 'true' : 'false'"
        aria-haspopup="true"
    @endif
    role="menuitem"
>
    <a @class([
            'flex w-full h-14 justify-between items-center pr-4 font-bold gap-2',
            'text-white pl-' . $currentLevel * 4 => $style === 'vivid',
            'bg-primary-600' => ($style === 'vivid' && $record->has_active),
            'text-gray-800' => $style === 'simple',
            'text-primary-600' => $style === 'simple' && $record->has_active,
        ])
        @if ($hasChild)
            @click="isExpanded = ! isExpanded"
            href="javascript:;"
        @else
            {{ \Filament\Support\generate_href_html('https://www.taobao.com') }}
        @endif
    >
        <div class="flex items-center gap-1">
            @if ($style == 'simple') 
                @if ($currentLevel > 2)
                    <div class="relative flex h-14 w-6 items-center justify-center">
                        <div class="absolute h-full w-px bg-gray-300 dark:bg-gray-600"></div>
                    </div>
                @endif

                @if ($currentLevel > 1)
                    <div class="relative flex h-7 w-6 items-center justify-center">
                        @if (!$first)
                            <div class="absolute -top-1/2 bottom-1/2 w-px bg-gray-300 dark:bg-gray-600"></div>
                        @endif
                        @if (!$last)
                            <div class="absolute -bottom-1/2 top-1/2 w-px bg-gray-300 dark:bg-gray-600"></div>
                        @endif
                        <div class="relative h-2 w-2 rounded-full bg-gray-400 dark:bg-gray-500"></div>
                    </div>
                @endif
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
            'border-t border-primary-400 divide-y divide-primary-400' => $style === 'vivid',
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
                    :component="$this->getItemView()" 
                    key="categories-component-{{ $child->getKey() }}" 
                    :record="$child" 
                    :level="$level" 
                    :first="$loop->first" 
                    :last="$loop->last" 
                    :style="$style" 
                    :current-level="$currentLevel" 
                />
            @endforeach
        </ul>
    @endif 
</li>

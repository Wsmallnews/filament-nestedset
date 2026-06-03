@props(['record', 'level'])

@php
    use Filament\Support\Enums\Alignment;
    use Filament\Schemas\Schema;
    use Filament\Support\Icons\Heroicon;

    $infolistAlignment = static::getInfolistAlignment();
    $infoListHiddenEndpoint = static::getInfolistHiddenEndpoint();

    // 是否还有下级
    $hasNextLevel = is_null($level) || $level > ($record->depth + 1);

    $canCreateChildren = false;
    if ($this->showCreateChildNodeActionInRow() && $hasNextLevel) {
        $canCreateChildren = true;
    }
@endphp

<div
    x-data="{ open: $persist(true) }"
    wire:key="sn-filament-nestedset-record-{{ $record->getKey() }}"
    data-id="{{ $record->getKey() }}"
    class="fi-sn-nestedset-record"
    data-sortable-item
>
    <div class="fi-sn-nestedset-record-rowinfo flex justify-between relative group px-4 gap-4 hover:bg-gray-50 dark:hover:bg-white/5">
        <div class="flex gap-4 grow">
            <button
                class="fi-sn-nestedset-record-handle flex items-center ltr:rounded-l-lg rtl:rounded-r-lg"
                type="button"
                data-sortable-handle
            >
                <x-filament::icon 
                    class="text-gray-400 size-5 cursor-move ltr:-mr-2 rtl:-ml-2" 
                    :icon="Heroicon::Bars2"
                    aria-hidden="true" />
            </button>

            <div class="appearance-none px-3 py-4 ltr:text-left rtl:text-right inline-block">
                <span>{{ $this->getRecordLabel($record) }}</span>
            </div>

            @if($record->children->isNotEmpty())
                <button type="button" x-on:click="open = !open" title="Toggle children" class="appearance-none text-gray-500">
                    <x-filament::icon 
                        class="size-5 font-bold transform transition-transform duration-200" 
                        :icon="Heroicon::ChevronDown"
                        x-bind:class="{
                            '-rotate-90': !open,
                        }"
                        aria-hidden="true" />
                </button>
            @endif

            @if ($this->hasInfolist())
                <div @class([
                    'fi-sn-nestedset-record-infolist hidden grow gap-x-4 px-4 items-center',
                    match ($infoListHiddenEndpoint) {
                        'sm' => 'sm:flex',
                        'md' => 'md:flex',
                        'lg' => 'lg:flex',
                        'xl' => 'xl:flex',
                        '2xl' => '2xl:flex',
                    },
                    match ($infolistAlignment) {
                        Alignment::Left, Alignment::Start => 'justify-start',
                        Alignment::Center => 'justify-center',
                        Alignment::Right, Alignment::End => 'justify-end',
                    },
                ])>
                    {{ Schema::make($this)
                        ->record($record)
                        ->components($this->infolistSchema())
                        ->view('sn-filament-nestedset::components.filament.nestedset-infolist') }}
                </div>
            @endif
        </div>

        <div class="flex grow-0 gap-3">
            {{-- 一级 depth = 0 --}}
            @if($canCreateChildren)
                {{ ($this->createChildAction)(['parentId' => $record->getKey()]) }}
            @endif

            {{ ($this->editAction)(['id' => $record->getKey()]) }}

            @if($this->canBeDeleted($record))
                {{ ($this->deleteAction)(['id' => $record->getKey()]) }}
            @endif
        </div>
    </div>

    @if ($hasNextLevel)
        <div x-show="open" x-collapse class="divide-y ltr:pl-6 rtl:pr-6">
            <div
                @class([
                    'fi-sn-nestedset-record-child divide-y divide-gray-200 dark:divide-white/10',
                    'border-t border-gray-200 dark:border-white/10' => $record->children->isNotEmpty()
                ])
                wire:key="sn-filament-nestedset-record-{{ $record->getKey() }}-children"
                data-id="{{ $record->getKey() }}"
                x-data="nestedsetManager({
                    parentId: {{ $record->getKey() }}
                })"
            >
                @foreach ($record->children as $childKey => $child)
                    <x-sn-filament-nestedset::filament.nestedset-record 
                        :record="$child"
                        :level="$level"
                        key="sn-filament-nestedset-fi-record-component-{{ $child->getKey() }}" />
                @endforeach
            </div>
        </div>
    @endif
</div>

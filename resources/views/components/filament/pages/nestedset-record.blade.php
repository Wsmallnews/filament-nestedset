@props(['record', 'level', 'animateLoad' => false])

@php
    use Filament\Support\Enums\Alignment;
    use Filament\Schemas\Schema;
    use Filament\Support\Icons\Heroicon;

    $infolistAlignment = static::getInfolistAlignment();
    $infoListHiddenEndpoint = static::getInfolistHiddenEndpoint();

    // whether deeper levels remain
    $hasNextLevel = is_null($level) || $level > ($record->depth + 1);
@endphp

<div
    x-data="{
        open: $persist(true).as('sn-tree-{{ $record->getKey() }}'),
        hydrated: {{ $animateLoad ? 'false' : 'true' }},
        init () {
            if (this.hydrated) {
                return;
            }

            // Alpine skips x-show transitions on the initial render, so `hydrated` flips late:
            // $nextTick waits for the component to settle, setTimeout defers past the first paint.
            // Flipping too early fast-forwards the CSS transition and the animation mostly never plays.
            this.$nextTick(() => {
                setTimeout(() => { this.hydrated = true }, 150)
            })
        },
    }"
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
                        :icon="Heroicon::ChevronRight"
                        x-bind:class="{
                            'rotate-90': open && hydrated,
                        }"
                        aria-hidden="true" />
                </button>
            @endif

            @if ($this->hasInfolist())
                <div @class([
                    'fi-sn-nestedset-record-infolist hidden grow gap-x-4 px-4 items-center',
                    // the infolist appears once the tree container reaches the breakpoint (container query,
                    // see $infolistHiddenEndpoint); the important suffix beats the theme stylesheet's .hidden
                    match ($infoListHiddenEndpoint) {
                        '3xs' => '@3xs:flex!',
                        '2xs' => '@2xs:flex!',
                        'xs' => '@xs:flex!',
                        'sm' => '@sm:flex!',
                        'md' => '@md:flex!',
                        'lg' => '@lg:flex!',
                        'xl' => '@xl:flex!',
                        '2xl' => '@2xl:flex!',
                        '3xl' => '@3xl:flex!',
                        '4xl' => '@4xl:flex!',
                        '5xl' => '@5xl:flex!',
                        '6xl' => '@6xl:flex!',
                        '7xl' => '@7xl:flex!',
                        default => null,
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
                        ->view('sn-filament-nestedset::components.filament.pages.nestedset-infolist') }}
                </div>
            @endif
        </div>

        <div class="flex grow-0 items-center gap-3">
            {{-- row actions: built-in create-child/edit/delete filtered by condition, optionally grouped (HasNestedsetActions::getRowActions) --}}
            @foreach ($this->getRowActions($record) as $rowAction)
                {{ $rowAction }}
            @endforeach
        </div>
    </div>

    @if ($hasNextLevel)
        {{-- x-cloak hides children before Alpine boots, preventing an expand-then-collapse flash on refresh --}}
        <div x-show="open && hydrated" x-collapse x-cloak class="divide-y ltr:pl-6 rtl:pr-6">
            <div
                @class([
                    'fi-sn-nestedset-record-child divide-y divide-gray-200 dark:divide-white/10',
                    'border-t border-gray-200 dark:border-white/10' => $record->children->isNotEmpty()
                ])
                wire:key="sn-filament-nestedset-record-{{ $record->getKey() }}-children"
                data-id="{{ $record->getKey() }}"
                x-data="nestedsetManager({
                    parentId: '{{ $record->getKey() }}'
                })"
            >
                @foreach ($record->children as $childKey => $child)
                    <x-sn-filament-nestedset::filament.pages.nestedset-record 
                        :record="$child"
                        :level="$level"
                        key="sn-filament-nestedset-fi-record-component-{{ $child->getKey() }}" />
                @endforeach
            </div>
        </div>
    @endif
</div>

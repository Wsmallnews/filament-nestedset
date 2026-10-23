@php
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\Icons\Heroicon;

    $nestedset = $this->getNestedset();
    $level = static::getLevel();

    $breakpoint = $this->getBreakpoint();

    [$columnClass, $treeClass, $drawerClass, $triggerClass] = match ($breakpoint) {
        '3xs' => ['@3xs:min-h-full @3xs:flex-1', '@3xs:flex!', '@3xs:!hidden', '@3xs:hidden'],
        '2xs' => ['@2xs:min-h-full @2xs:flex-1', '@2xs:flex!', '@2xs:!hidden', '@2xs:hidden'],
        'xs' => ['@xs:min-h-full @xs:flex-1', '@xs:flex!', '@xs:!hidden', '@xs:hidden'],
        'sm' => ['@sm:min-h-full @sm:flex-1', '@sm:flex!', '@sm:!hidden', '@sm:hidden'],
        'md' => ['@md:min-h-full @md:flex-1', '@md:flex!', '@md:!hidden', '@md:hidden'],
        'lg' => ['@lg:min-h-full @lg:flex-1', '@lg:flex!', '@lg:!hidden', '@lg:hidden'],
        'xl' => ['@xl:min-h-full @xl:flex-1', '@xl:flex!', '@xl:!hidden', '@xl:hidden'],
        '2xl' => ['@2xl:min-h-full @2xl:flex-1', '@2xl:flex!', '@2xl:!hidden', '@2xl:hidden'],
        '3xl' => ['@3xl:min-h-full @3xl:flex-1', '@3xl:flex!', '@3xl:!hidden', '@3xl:hidden'],
        '4xl' => ['@4xl:min-h-full @4xl:flex-1', '@4xl:flex!', '@4xl:!hidden', '@4xl:hidden'],
        '5xl' => ['@5xl:min-h-full @5xl:flex-1', '@5xl:flex!', '@5xl:!hidden', '@5xl:hidden'],
        '6xl' => ['@6xl:min-h-full @6xl:flex-1', '@6xl:flex!', '@6xl:!hidden', '@6xl:hidden'],
        '7xl' => ['@7xl:min-h-full @7xl:flex-1', '@7xl:flex!', '@7xl:!hidden', '@7xl:hidden'],
        default => ['@3xl:min-h-full @3xl:flex-1', '@3xl:flex!', '@3xl:!hidden', '@3xl:hidden'],
    };
@endphp

<div
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('sn-sidebar-nestedset', 'wsmallnews/filament-nestedset') }}"
    x-data="snSidebarNestedset()"
    @class([
        'flex w-full flex-none flex-col',
        $columnClass,
    ])
>
    <div @class([
        'hidden min-h-full flex-col',
        $treeClass,
    ])>
        <x-dynamic-component
            :component="$this->getContentComponent()"
            :nestedset="$nestedset"
            :level="$level"
        >{{ $slot ?? '' }}</x-dynamic-component>
    </div>

    <x-filament::modal
        slide-over
        :close-by-clicking-away="true"
        :close-by-escaping="true"
        width="sm"
        @class([
            $drawerClass,
        ])
    >
        <x-slot name="trigger">
            {{-- Slots are Livewire 4 only. `isset()` keeps the component renderable on
                 Filament v4 / Livewire v3, where slot content is simply empty. --}}
            @if (isset($slot) && $slot->has('mobileTrigger'))
                <div @class([
                    'w-full',
                    $triggerClass,
                ])>
                    {{ $slot->get('mobileTrigger') }}
                </div>
            @else
                <div @class([
                    'flex items-center justify-start gap-1.5 bg-gray-100 px-3 py-2 text-left text-sm text-gray-600 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                    'w-full',
                    $triggerClass,
                ])>
                    <x-filament::icon :icon="Heroicon::OutlinedListBullet" class="size-4" />
                    <span>{{ __('sn-filament-nestedset::nestedset.sidebar.label') }}</span>
                </div>
            @endif
        </x-slot>

        <x-dynamic-component
            :component="$this->getContentComponent()"
            :nestedset="$nestedset"
            :level="$level"
        >{{ $slot ?? '' }}</x-dynamic-component>
    </x-filament::modal>

    <x-filament-actions::modals />
</div>

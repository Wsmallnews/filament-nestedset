@php
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\Icons\Heroicon;

    $nestedset = $this->getNestedset();
    $level = static::getLevel();
@endphp

<div
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('sn-sidebar-nestedset', 'wsmallnews/filament-nestedset') }}"
    x-data="snSidebarNestedset()"
    class="flex w-full flex-none flex-col md:min-h-full md:flex-1"
>
    <div class="hidden md:flex! md:min-h-full md:flex-col">
        <x-dynamic-component
            :component="$this->getContentComponent()"
            :nestedset="$nestedset"
            :level="$level"
        >{{ $slot }}</x-dynamic-component>
    </div>

    <x-filament::modal
        slide-over
        :close-by-clicking-away="true"
        :close-by-escaping="true"
        width="sm"
        class="md:!hidden"
    >
        <x-slot name="trigger">
            @if ($slot->has('mobileTrigger'))
                <div class="w-full md:hidden">
                    {{ $slot->get('mobileTrigger') }}
                </div>
            @else
                <div class="flex w-full items-center justify-start gap-1.5 bg-gray-100 px-3 py-2 text-left text-sm text-gray-600 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 md:hidden">
                    <x-filament::icon :icon="Heroicon::OutlinedListBullet" class="size-4" />
                    <span>{{ __('sn-filament-nestedset::nestedset.sidebar.label') }}</span>
                </div>
            @endif
        </x-slot>

        <x-dynamic-component
            :component="$this->getContentComponent()"
            :nestedset="$nestedset"
            :level="$level"
        >{{ $slot }}</x-dynamic-component>
    </x-filament::modal>

    <x-filament-actions::modals />
</div>

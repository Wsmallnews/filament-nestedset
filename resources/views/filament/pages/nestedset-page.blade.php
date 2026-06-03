<x-filament-panels::page>
    {{ $this->content }}

    @include('sn-filament-nestedset::filament.pages.components.nestedset', [
        'nestedset' => $this->getNestedset(),
        'level' => $this->getLevel(),
    ])
</x-filament-panels::page>

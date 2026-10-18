@php
    $nestedset = $this->getNestedset();
@endphp

<x-filament-panels::page>
    {{ $this->content }}

    <x-sn-filament-nestedset::filament.pages.nestedset 
        :nestedset="$nestedset" 
        :level="$level" 
        :empty-label="$emptyLabel" 
        :empty-tip-label="$emptyTipLabel" />
</x-filament-panels::page>

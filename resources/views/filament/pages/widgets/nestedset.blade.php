@php
    $nestedset = $this->getNestedset();
@endphp

<x-filament-widgets::widget class="flex flex-col gap-8">
    @if ($headerActions = $this->getHeaderActions())
        <div class="flex justify-end items-center">
            <x-filament::actions :actions="$headerActions" />
        </div>
    @endif

    {{ $this->content }}

    <x-sn-filament-nestedset::filament.nestedset 
        :nestedset="$nestedset" 
        :level="$level" 
        :empty-label="$emptyLabel" 
        :empty-tip-label="$emptyTipLabel" />
</x-filament-widgets::widget>

<x-filament-widgets::widget class="flex flex-col gap-4">
    @if ($headerActions = $this->getHeaderActions())
        <div class="flex justify-end items-center">
            <x-filament::actions :actions="$headerActions" />
        </div>
    @endif

    {{ $this->content }}

    @include('sn-filament-nestedset::filament.pages.components.nestedset', [
        'nestedset' => $this->getNestedset(),
        'level' => $this->getLevel(),
    ])
</x-filament-widgets::widget>

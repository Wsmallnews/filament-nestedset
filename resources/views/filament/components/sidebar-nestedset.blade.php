@php
    use Filament\Support\Enums\Size;
    use Filament\Support\Icons\Heroicon;

    $nestedset = $this->getNestedset();
    $level = static::getLevel();
@endphp

<div class="w-full">
    <ul class="w-full flex flex-col" role="menu">
        @forelse ($nestedset as $record)
            <x-dynamic-component
                @class([
                    'w-full',
                ])
                :component="$this->getRecordView()"
                :key="'sn-filament-nestedset-sidebar-record-' . $record->getKey()"
                :record="$record"
                :level="$level"
                :current-level="1"
            />
        @empty
            <x-filament::empty-state :contained="false" :icon="Heroicon::OutlinedFolderOpen" icon-color="gray">
                <x-slot name="heading">
                    {{ static::getEmptyLabel() ?: __('sn-filament-nestedset::nestedset.nestedset.empty_label') }}
                </x-slot>
                <x-slot name="description">
                    {{ static::getEmptyTipLabel() ?: __('sn-filament-nestedset::nestedset.nestedset.empty_tip_label') }}
                </x-slot>
            </x-filament::empty-state>
        @endforelse
    </ul>

    <div class="mt-1 px-1">
        {{ $this->createAction()
            ->button()
            ->label(__('sn-filament-nestedset::nestedset.action.create'))
            ->size(Size::Small)
            ->outlined()
            ->extraAttributes(['class' => 'w-full']) }}
    </div>

    <x-filament-actions::modals />
</div>

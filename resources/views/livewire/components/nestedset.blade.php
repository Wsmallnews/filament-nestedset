@php
    $nestedset = $this->getNestedset();
@endphp

<ul
    @class([
        'sn-nestedset w-full flex flex-col gap-0.5',
    ])
    role="menu"
>
    @forelse($nestedset as $treeKey => $record)
        <x-dynamic-component
            @class([
                'w-full',
            ])
            :component="$this->getRecordView()"
            key="sn-filament-nestedset-record-component-{{ $record->getKey() }}"
            :record="$record"
            :first="$loop->first"
            :last="$loop->last"
            :current-level="1"
        />
    @empty
        <li class="w-full rounded-md px-3 py-6 text-center text-sm text-gray-400 dark:text-gray-500">
            {{ $this->getEmptyLabel() ?: __('sn-filament-nestedset::nestedset.nestedset.empty_label') }}
        </li>
    @endforelse
</ul>

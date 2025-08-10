<x-filament-panels::page>
    <x-filament-panels::resources.tabs />

    <div
        class="fi-sn-tree-container overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        wire:key="tree-items-wrapper"
    >
        <div
            class="divide-y divide-gray-200 dark:divide-white/10"
            x-data="treeManager({})"
            data-sortable-container
        >
            @forelse($tree as $treeKey => $item)
                <x-sn-filament-nestedset::tree-item :item="$item" key="tree-component-{{ $item->id }}" :level="$level" />
            @empty
                <div @class([
                    'w-full bg-white rounded-lg border border-gray-300 px-3 py-2 text-center',
                    'dark:bg-gray-700 dark:border-gray-600',
                ])>
                    没有数据
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>

@assets
<script>
    function treeManager({
        parentId = null
    }) {
        return {
            parentId,
            sortable: null,
            init () {
                this.sortable = new Sortable(this.$el, {
                    group: 'nested',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.50,
                    draggable: '[data-sortable-item]',
                    handle: '[data-sortable-handle]',
                    onSort: () => {
                        this.sorted()
                    }
                })
            },
            sorted() {
                this.$wire.mountAction('moveNode', {
                    parentId: this.parentId,
                    changeIds: this.sortable.toArray()
                })
            }
        }
    }
</script>
@endassets
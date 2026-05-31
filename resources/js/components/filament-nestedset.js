import Sortable from 'sortablejs'

const getSortableContainerId = (element) => element?.dataset?.id ?? null

export default function nestedsetManager({ parentId = null }) {
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
                onEnd: (evt) => {
                    const parentContainerId = getSortableContainerId(evt.to) ?? this.parentId
                    const info = {
                        id: getSortableContainerId(evt.item),
                        ancestor: getSortableContainerId(evt.from),
                        parent: parentContainerId,
                        from: evt.oldIndex,
                        to: evt.newIndex,
                    }

                    const hasMovedParent = info.parent !== info.ancestor
                    const hasMovedIndex = info.from !== info.to

                    if (hasMovedParent || hasMovedIndex) {
                        this.$wire.mountAction('moveNode', info)
                    }
                },
            })
        },
    }
}
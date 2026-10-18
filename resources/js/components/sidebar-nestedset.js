import Sortable from 'sortablejs'

const itemSelector = '[data-sidebar-item]'

const getElementId = (element) => element?.dataset?.id || null

const getListParentId = (list) => {
    if (! list) {
        return null
    }

    if (list.hasAttribute('data-parent-id')) {
        return list.dataset.parentId || null
    }

    return getElementId(list)
}

const getDraggableItems = (list) => Array.from(list?.children || [])
    .filter((child) => child.matches(itemSelector))

const getDraggableIndex = (event, property, list) => {
    if (Number.isInteger(event[property])) {
        return event[property]
    }

    const index = getDraggableItems(list).indexOf(event.item)

    return index >= 0 ? index : 0
}

export default function snSidebarNestedset() {
    return {
        search: '',
        visibleIds: null,
        sortMode: false,
        dragging: false,
        sortableInstances: [],
        dropTargetRow: null,
        rootEl: null,
        wire: null,
        observer: null,
        refreshTimer: null,
        forcedListStyles: new Map(),

        init() {
            this.rootEl = this.$el
            this.wire = this.$wire

            this.$watch('search', () => this.applyVisibility())
            this.observeTree()
            this.applyVisibility()
        },

        destroy() {
            this.observer?.disconnect()
            this.destroySortable()
            this.clearForcedListStyles()

            if (this.refreshTimer) {
                clearTimeout(this.refreshTimer)
            }
        },

        get isSearching() {
            return this.search.trim().length > 0
        },

        observeTree() {
            if (typeof MutationObserver === 'undefined') {
                return
            }

            this.observer = new MutationObserver(() => {
                if (! this.sortMode || this.dragging) {
                    return
                }

                this.scheduleSortableRefresh()
            })

            this.observer.observe(this.rootEl, { childList: true, subtree: true })
        },

        scheduleSortableRefresh() {
            if (this.refreshTimer) {
                clearTimeout(this.refreshTimer)
            }

            this.refreshTimer = setTimeout(() => {
                this.refreshTimer = null

                if (this.sortMode && ! this.dragging) {
                    this.forceAllListsVisible()
                    this.initSortable()
                }
            }, 50)
        },

        applyVisibility() {
            const searching = this.isSearching

            this.clearForcedListStyles()

            if (! searching) {
                this.rootEl.querySelectorAll('li[data-sidebar-item]').forEach((item) => {
                    item.style.removeProperty('display')
                })
            } else {
                const query = this.search.trim().toLowerCase()
                const matches = new Set()

                this.rootEl.querySelectorAll('[data-sidebar-row]').forEach((row) => {
                    if (! (row.dataset.label || '').toLowerCase().includes(query)) {
                        return
                    }

                    const item = row.closest('li[data-sidebar-item]')
                    let current = item

                    while (current) {
                        matches.add(current.dataset.id)
                        current = current.parentElement?.closest('li[data-sidebar-item]')
                    }
                })

                this.visibleIds = matches

                this.rootEl.querySelectorAll('li[data-sidebar-item]').forEach((item) => {
                    item.style.display = matches.has(item.dataset.id) ? '' : 'none'
                })

                this.rootEl.querySelectorAll('ul[data-sidebar-list]').forEach((list) => {
                    const hasVisibleChild = Array.from(list.children).some((child) => (
                        child.matches(itemSelector) && child.style.display !== 'none'
                    ))

                    if (hasVisibleChild) {
                        this.forceListVisible(list)
                    }
                })
            }

            if (this.sortMode) {
                this.forceAllListsVisible()
            }

            const hasNoResults = searching && (! this.visibleIds || this.visibleIds.size === 0)
            this.rootEl.querySelectorAll('[data-sidebar-no-results]').forEach((element) => {
                element.style.display = hasNoResults ? '' : 'none'
            })
        },

        forceListVisible(list) {
            if (! this.forcedListStyles.has(list)) {
                this.forcedListStyles.set(list, {
                    display: [list.style.getPropertyValue('display'), list.style.getPropertyPriority('display')],
                    height: [list.style.getPropertyValue('height'), list.style.getPropertyPriority('height')],
                    overflow: [list.style.getPropertyValue('overflow'), list.style.getPropertyPriority('overflow')],
                })
            }

            list.style.setProperty('display', 'flex', 'important')
            list.style.setProperty('height', 'auto', 'important')
            list.style.setProperty('overflow', 'visible', 'important')
        },

        forceAllListsVisible() {
            this.rootEl.querySelectorAll('ul[data-sidebar-list]').forEach((list) => {
                this.forceListVisible(list)
            })
        },

        clearForcedListStyles() {
            this.forcedListStyles.forEach((styles, list) => {
                Object.entries(styles).forEach(([property, [value, priority]]) => {
                    if (value) {
                        list.style.setProperty(property, value, priority)
                    } else {
                        list.style.removeProperty(property)
                    }
                })
            })

            this.forcedListStyles.clear()
        },

        toggleSortMode() {
            this.sortMode ? this.exitSortMode() : this.enterSortMode()
        },

        enterSortMode() {
            this.sortMode = true
            this.search = ''
            this.visibleIds = null
            this.forceAllListsVisible()

            setTimeout(() => {
                if (this.sortMode) {
                    this.initSortable()
                }
            }, 0)
        },

        exitSortMode() {
            this.sortMode = false
            this.dragging = false
            this.clearDropTargets()
            this.destroySortable()
            this.clearForcedListStyles()
        },

        isInvalidTarget(item, list) {
            return ! item || ! list || item.contains(list)
        },

        clearDropTargets() {
            this.dropTargetRow?.classList.remove('sn-drop-target', 'sn-drop-invalid')
            this.dropTargetRow = null
        },

        markDropTarget(list, invalid = false) {
            if (! list) {
                this.clearDropTargets()

                return
            }

            const row = list.closest('li[data-sidebar-item]')?.querySelector(':scope > [data-sidebar-row]')

            if (row === this.dropTargetRow && row) {
                row.classList.toggle('sn-drop-target', ! invalid)
                row.classList.toggle('sn-drop-invalid', invalid)

                return
            }

            this.clearDropTargets()

            if (row) {
                row.classList.add(invalid ? 'sn-drop-invalid' : 'sn-drop-target')
                this.dropTargetRow = row
            }
        },

        initSortable() {
            this.destroySortable()

            this.rootEl.querySelectorAll('ul[data-sidebar-list]').forEach((list) => {
                const instance = new Sortable(list, {
                    group: 'sn-sidebar-nestedset',
                    animation: 150,
                    fallbackOnBody: true,
                    forceFallback: true,
                    fallbackTolerance: 4,
                    swapThreshold: 0.65,
                    emptyInsertThreshold: 24,
                    draggable: itemSelector,
                    handle: '[data-sortable-handle]',
                    onClone: ({ clone }) => {
                        clone.setAttribute('x-ignore', '')
                    },
                    onStart: () => {
                        this.dragging = true
                        Sortable.ghost?.setAttribute('x-ignore', '')
                    },
                    onMove: (event) => {
                        const invalid = this.isInvalidTarget(event.dragged, event.to)
                        this.markDropTarget(event.to, invalid)

                        return ! invalid
                    },
                    onEnd: (event) => {
                        this.dragging = false
                        const invalid = this.isInvalidTarget(event.item, event.to)

                        this.clearDropTargets()

                        if (! event.to || invalid) {
                            return
                        }

                        const ancestor = getListParentId(event.from)
                        const info = {
                            id: getElementId(event.item),
                            parent: getListParentId(event.to),
                            from: getDraggableIndex(event, 'oldDraggableIndex', event.from),
                            to: getDraggableIndex(event, 'newDraggableIndex', event.to),
                        }

                        if (info.parent !== ancestor || info.from !== info.to) {
                            this.wire?.mountAction('moveNode', info)
                        }
                    },
                })

                this.sortableInstances.push(instance)
            })
        },

        destroySortable() {
            this.sortableInstances.forEach((sortable) => sortable.destroy())
            this.sortableInstances = []
        },
    }
}

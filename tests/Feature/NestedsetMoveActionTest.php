<?php

use Livewire\Livewire;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestCategory;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestSidebarTree;

function makeMoveActionNode(string $name, ?TestCategory $parent = null): TestCategory
{
    return TestCategory::create([
        'name' => $name,
        'scope_type' => 'test',
        'scope_id' => 0,
        'parent_id' => $parent?->getKey(),
    ]);
}

test('move action reorders siblings and dispatches the updated event', function () {
    $first = makeMoveActionNode('First');
    makeMoveActionNode('Second');
    makeMoveActionNode('Third');

    Livewire::test(TestSidebarTree::class)
        ->mountAction('moveNode', [
            'id' => $first->getKey(),
            'parent' => null,
            'from' => 0,
            'to' => 2,
        ])
        ->callMountedAction()
        ->assertDispatched('sn-filament-nestedset-updated');

    expect(TestCategory::query()->defaultOrder()->pluck('name')->all())
        ->toBe(['Second', 'Third', 'First']);
});

test('move action reparents a node and dispatches the updated event', function () {
    $oldParent = makeMoveActionNode('Old parent');
    $newParent = makeMoveActionNode('New parent');
    $child = makeMoveActionNode('Child', $oldParent);

    Livewire::test(TestSidebarTree::class)
        ->mountAction('moveNode', [
            'id' => $child->getKey(),
            'parent' => $newParent->getKey(),
            'from' => 0,
            'to' => 0,
        ])
        ->callMountedAction()
        ->assertDispatched('sn-filament-nestedset-updated');

    expect($child->fresh()->parent_id)->toBe($newParent->getKey());
});

test('move action keeps the drop position at the end of the root list', function () {
    $holder = makeMoveActionNode('Holder');
    $child = makeMoveActionNode('Child', $holder);
    makeMoveActionNode('A');
    makeMoveActionNode('B');
    makeMoveActionNode('C');

    // 4 root siblings left after saveAsRoot(), so to = 4 means "stay last"
    Livewire::test(TestSidebarTree::class)
        ->mountAction('moveNode', [
            'id' => $child->getKey(),
            'parent' => null,
            'from' => 0,
            'to' => 4,
        ])
        ->callMountedAction()
        ->assertDispatched('sn-filament-nestedset-updated');

    expect(TestCategory::query()->defaultOrder()->whereNull('parent_id')->pluck('name')->all())
        ->toBe(['Holder', 'A', 'B', 'C', 'Child']);
});

test('move action drops a node at a middle root position', function () {
    $holder = makeMoveActionNode('Holder');
    $child = makeMoveActionNode('Child', $holder);
    makeMoveActionNode('A');
    makeMoveActionNode('B');
    makeMoveActionNode('C');

    Livewire::test(TestSidebarTree::class)
        ->mountAction('moveNode', [
            'id' => $child->getKey(),
            'parent' => null,
            'from' => 0,
            'to' => 2,
        ])
        ->callMountedAction()
        ->assertDispatched('sn-filament-nestedset-updated');

    expect(TestCategory::query()->defaultOrder()->whereNull('parent_id')->pluck('name')->all())
        ->toBe(['Holder', 'A', 'Child', 'B', 'C']);
});

test('move action rejects a target inside the moved subtree', function () {
    $root = makeMoveActionNode('Root');
    $child = makeMoveActionNode('Child', $root);
    makeMoveActionNode('Grandchild', $child);

    Livewire::test(TestSidebarTree::class)
        ->mountAction('moveNode', [
            'id' => $root->getKey(),
            'parent' => $child->getKey(),
            'from' => 0,
            'to' => 0,
        ])
        ->callMountedAction()
        ->assertNotDispatched('sn-filament-nestedset-updated');

    expect($root->fresh()->parent_id)->toBeNull();
});

test('move action enforces the level limit against the full moved subtree', function () {
    $root = makeMoveActionNode('Root');
    $child = makeMoveActionNode('Child', $root);
    makeMoveActionNode('Grandchild', $child);
    $target = makeMoveActionNode('Target');

    Livewire::test(TestSidebarTree::class)
        ->mountAction('moveNode', [
            'id' => $root->getKey(),
            'parent' => $target->getKey(),
            'from' => 0,
            'to' => 0,
        ])
        ->callMountedAction()
        ->assertNotDispatched('sn-filament-nestedset-updated');

    expect($root->fresh()->parent_id)->toBeNull();
});

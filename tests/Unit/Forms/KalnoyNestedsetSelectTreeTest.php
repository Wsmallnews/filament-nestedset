<?php

use Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestCategory;

/**
 * @return Collection<int, array<string, mixed>>
 */
function buildSelectTree(?int $level)
{
    $field = KalnoyNestedsetSelectTree::make('parent_id')
        ->level($level)
        ->query(fn () => TestCategory::scoped(['scope_type' => 'test', 'scope_id' => 0])->defaultOrder(), titleAttribute: 'name', parentAttribute: 'parent_id');

    return (new ReflectionMethod($field, 'buildTree'))->invoke($field);
}

beforeEach(function () {
    if (! app()->bound('config')) {
        $this->markTestSkipped('Requires Orchestra Testbench (run from package directory)');
    }
});

test('level 1 only keeps root nodes', function () {
    $root = TestCategory::create(['name' => 'Root', 'scope_type' => 'test', 'scope_id' => 0]);
    TestCategory::create(['name' => 'Child', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $root->id]);

    $tree = buildSelectTree(1);

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['name'])->toBe('Root')
        ->and($tree[0])->not->toHaveKey('children');
});

test('level 2 includes one level of children', function () {
    $root = TestCategory::create(['name' => 'Root', 'scope_type' => 'test', 'scope_id' => 0]);
    $child = TestCategory::create(['name' => 'Child', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $root->id]);
    TestCategory::create(['name' => 'Grandchild', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $child->id]);

    $tree = buildSelectTree(2);

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['name'])->toBe('Child')
        // depth 2 node (grandchild) is filtered out by level = 2
        ->and($tree[0]['children'][0])->not->toHaveKey('children');
});

test('null level keeps the whole tree', function () {
    $root = TestCategory::create(['name' => 'Root', 'scope_type' => 'test', 'scope_id' => 0]);
    $child = TestCategory::create(['name' => 'Child', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $root->id]);
    TestCategory::create(['name' => 'Grandchild', 'scope_type' => 'test', 'scope_id' => 0, 'parent_id' => $child->id]);

    $tree = buildSelectTree(null);

    expect($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['name'])->toBe('Child')
        ->and($tree[0]['children'][0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['children'][0]['name'])->toBe('Grandchild');
});

test('node structure carries name, value and parent', function () {
    $root = TestCategory::create(['name' => 'Root', 'scope_type' => 'test', 'scope_id' => 0]);

    $tree = buildSelectTree(null);

    expect($tree[0]['name'])->toBe('Root')
        ->and($tree[0]['value'])->toBe((string) $root->id)
        ->and($tree[0]['parent'])->toBe((string) $root->parent_id)
        ->and($tree[0]['disabled'])->toBeFalse()
        ->and($tree[0]['hidden'])->toBeFalse();
});

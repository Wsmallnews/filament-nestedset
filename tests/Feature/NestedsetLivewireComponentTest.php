<?php

use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Wsmallnews\FilamentNestedset\Exceptions\NestedsetException;
use Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\PlainModel;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestCategory;
use Wsmallnews\FilamentNestedset\Tests\Fixtures\TestFrontendTree;

function frontendNode(string $name, ?TestCategory $parent = null): TestCategory
{
    return TestCategory::create([
        'name' => $name,
        'scope_type' => 'test',
        'scope_id' => 0,
        'parent_id' => $parent?->getKey(),
    ]);
}

test('frontend component renders the nested tree', function () {
    $root = frontendNode('Front Root');
    frontendNode('Front Child', $root);

    Livewire::test(TestFrontendTree::class, ['model' => TestCategory::class])
        ->assertSee('Front Root')
        ->assertSee('Front Child')
        ->assertSeeHtml('sn-nestedset-record');
});

test('frontend component escapes node titles', function () {
    $payload = '<img src=x onerror=alert(1)>';
    frontendNode($payload);

    Livewire::test(TestFrontendTree::class, ['model' => TestCategory::class])
        ->assertDontSeeHtml($payload)
        ->assertSeeHtml(e($payload));
});

test('frontend component filters by showLevel', function () {
    $root = frontendNode('Level Root');
    frontendNode('Level Child', $root);

    // showLevel is compared against the 0-based `withDepth()` depth, so 0 = roots only
    Livewire::test(TestFrontendTree::class, ['model' => TestCategory::class, 'showLevel' => '0'])
        ->assertSee('Level Root')
        ->assertDontSee('Level Child');

    Livewire::test(TestFrontendTree::class, ['model' => TestCategory::class, 'showLevel' => '1'])
        ->assertSee('Level Root')
        ->assertSee('Level Child');
});

test('the locked wiring properties cannot be rewritten after mount', function () {
    Livewire::test(TestFrontendTree::class, ['model' => TestCategory::class])
        ->assertSet('model', TestCategory::class);

    expect(fn () => Livewire::test(TestFrontendTree::class, ['model' => TestCategory::class])
        ->set('model', PlainModel::class))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('a view passed at mount is kept across subsequent requests', function () {
    frontendNode('Theme Root');

    // cms 主题的用法：<livewire:... :view="..." :record-view="..." />
    // #[Locked] 只拦客户端 set，mount 参数照常生效，且 hydrate 后仍是主题视图。
    Livewire::test(TestFrontendTree::class, [
        'model' => TestCategory::class,
        'view' => 'theme-nestedset',
        'recordView' => 'theme-nestedset-record',
    ])
        ->assertSeeHtml('class="theme-nestedset"')
        ->assertSeeHtml('class="theme-record"')
        ->assertSet('view', 'theme-nestedset')
        ->assertSet('recordView', 'theme-nestedset-record')
        ->refresh()
        ->assertSeeHtml('class="theme-nestedset"')
        ->assertSeeHtml('class="theme-record"');
});

test('frontend component raises a typed exception when the model is missing', function () {
    expect(fn () => (new Nestedset)->getNestedset())
        ->toThrow(NestedsetException::class, 'Please set the model or custom `getNestedset` method in the nestedset component.');
});

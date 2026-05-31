<?php

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\HasNestedsetActions;

test('HasNestedsetActions trait exists', function () {
    expect(trait_exists(HasNestedsetActions::class))->toBeTrue();
});

test('trait has required action methods', function () {
    expect(method_exists(HasNestedsetActions::class, 'createAction'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'createChildAction'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'editAction'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'deleteAction'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'moveNodeAction'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'fixNestedsetAction'))->toBeTrue();
});

test('trait has helper methods', function () {
    expect(method_exists(HasNestedsetActions::class, 'hasFormParentSelect'))->toBeTrue()
        ->and(method_exists(HasNestedsetActions::class, 'getParentSelect'))->toBeTrue();
});

test('editAction returns Action type', function () {
    $reflection = new ReflectionMethod(HasNestedsetActions::class, 'editAction');
    $returnType = $reflection->getReturnType();

    expect($returnType)->not->toBeNull()
        ->and($returnType->getName())->toBe(Action::class);
});

test('deleteAction returns Action type', function () {
    $reflection = new ReflectionMethod(HasNestedsetActions::class, 'deleteAction');
    $returnType = $reflection->getReturnType();

    expect($returnType)->not->toBeNull()
        ->and($returnType->getName())->toBe(Action::class);
});

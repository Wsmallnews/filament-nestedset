<?php

afterEach(function () {
    $generated = app_path('Filament/Pages/GeneratedTreePage.php');

    if (file_exists($generated)) {
        unlink($generated);
    }

    $published = base_path('stubs/filament-nestedset/NestedsetPage.stub');

    if (file_exists($published)) {
        unlink($published);
    }
});

test('make command generates a nestedset page from the package stub', function () {
    $target = app_path('Filament/Pages/GeneratedTreePage.php');

    $this->artisan('make:filament-nestedset-page', [
        'name' => 'GeneratedTreePage',
        'model' => '',
        '--panel' => 'test',
        '--force' => true,
    ])->assertSuccessful();

    expect(file_exists($target))->toBeTrue();

    $contents = (string) file_get_contents($target);

    expect($contents)->toContain('extends NestedsetPage')
        ->and($contents)->toContain('class GeneratedTreePage')
        ->and($contents)->toContain('protected static ?string $model')
        ->and($contents)->toContain('function schema(array $arguments): array')
        ->and($contents)->toContain('function infolistSchema(): array')
        ->and($contents)->not->toContain('{{ ');
});

test('make command prefers a stub published to stubs/filament-nestedset', function () {
    $published = base_path('stubs/filament-nestedset/NestedsetPage.stub');

    if (! is_dir(dirname($published))) {
        mkdir(dirname($published), 0755, true);
    }

    file_put_contents($published, "<?php\n\n// published-stub-marker\nnamespace {{ namespace }};\n\nclass {{ class }}\n{\n    public string \$model = '{{ model }}';\n}\n");

    $this->artisan('make:filament-nestedset-page', [
        'name' => 'GeneratedTreePage',
        'model' => '',
        '--panel' => 'test',
        '--force' => true,
    ])->assertSuccessful();

    expect(file_get_contents(app_path('Filament/Pages/GeneratedTreePage.php')))
        ->toContain('published-stub-marker');
});

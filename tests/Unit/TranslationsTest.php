<?php

/**
 * 翻译键一致性：en/zh_CN 两套文件必须键对齐，且源码与视图里引用的键必须真实存在。
 */
function flattenedKeys(array $translations, string $prefix = ''): array
{
    $keys = [];

    foreach ($translations as $key => $value) {
        $path = $prefix === '' ? $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $keys = [...$keys, ...flattenedKeys($value, $path)];

            continue;
        }

        $keys[] = $path;
    }

    sort($keys);

    return $keys;
}

function langPath(string $locale): string
{
    return dirname(__DIR__, 2) . "/resources/lang/{$locale}/nestedset.php";
}

test('english and chinese translation files declare the same keys', function () {
    expect(flattenedKeys(require langPath('en')))
        ->toBe(flattenedKeys(require langPath('zh_CN')));
});

test('every translation key referenced in code exists', function () {
    $en = require langPath('en');

    $referenced = [];

    foreach (array_merge(
        glob(dirname(__DIR__, 2) . '/src/**/*.php') ?: [],
        glob(dirname(__DIR__, 2) . '/src/*.php') ?: [],
        glob(dirname(__DIR__, 2) . '/resources/views/**/*.blade.php') ?: [],
    ) as $file) {
        preg_match_all('/sn-filament-nestedset::([\w.\-]+)/', (string) file_get_contents($file), $matches);

        foreach ($matches[1] as $key) {
            $referenced[$key] = $file;
        }
    }

    $missing = [];

    foreach ($referenced as $key => $file) {
        $cursor = $en;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                $missing[$key] = $file;

                break;
            }

            $cursor = $cursor[$segment];
        }
    }

    expect($missing)->toBe([]);
});

test('no translation string is left empty', function () {
    foreach (['en', 'zh_CN'] as $locale) {
        $translations = require langPath($locale);

        array_walk_recursive($translations, function ($value, $key) use ($locale): void {
            expect(trim((string) $value))->not->toBeEmpty("{$locale}.{$key} is empty");
        });
    }
});

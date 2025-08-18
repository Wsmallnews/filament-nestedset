# Filament tree build on kalnoy/nestedset

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wsmallnews/filament-nestedset.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/filament-nestedset)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/filament-nestedset/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/wsmallnews/filament-nestedset/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/filament-nestedset/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/wsmallnews/filament-nestedset/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/wsmallnews/filament-nestedset.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/filament-nestedset)


Filament tree build on kalnoy/nestedset, support multi language. support Multi-tenancy


## Installation

You can install the package via composer:

```bash
composer require wsmallnews/filament-nestedset
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-nestedset-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-nestedset-views"
```

Multi language support, you can publish the language files using

```bash
php artisan vendor:publish --tag=sn-filament-nestedset-lang
```

This is the contents of the published config file:

```php
return [
    /**
     * 限制删除带有子项的节点
     */
    'allow_delete_parent' => false,

    /*
     * 限制删除根节点，即使 'allow_delete_parent' 为 true，也可以删除根节点。
     */
    'allow_delete_root' => false,
];
```

## Usage

### Please define attribute name of the nodes in your tree, eg. title or title

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    protected static string $recordTitleAttribute = 'name';
    ...

}
```

### 定义 form schema

如果 create 和 edit 的 schema 一样，可以定义 schema 方法

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    protected function schema(array $arguments): array
    {
        return [
            //
        ];
    }
    ...
}
```

如果 create 和 edit 的 schema 不一样，可以分别定义 createSchema 和 editSchema 方法


```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    protected function createSchema(array $arguments): array
    {
        return [
            //
        ];
    }
    protected function editSchema(array $arguments): array
    {
        return [
            //
        ];
    }

    ...
}
```


### 定义 tree 为空时候的提示文字

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    public string $emptyLabel = '没有导航数据';
    ...

}
```

### 其他可以自定义的属性

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    protected static ?string $model = NavigationModel::class;
    
    protected static ?string $modelLabel = '测试管理';

    protected static ?string $title = '页面标题';

    protected static ?string $navigationLabel = '导航标题';

    protected static ?string $navigationGroup = '导航分组';

    protected static ?string $slug = 'tests';

    protected static string $recordTitleAttribute = 'name';

    protected static ?string $pluralModelLabel = '测试管理';

    protected static ?int $navigationSort = 1;

    ...
}
```

### 展示额外属性

通过 infolistSchema 方法可以定义每行展示额外属性

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...
    protected function infolistSchema(): array
    {
        return [];
    }
    ...
}
```

默认情况下, infolist 将会在 md 及以上断点展示, 你可以通过设置 `$infolistHiddenEndpoint` 来改变展示断点。

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...
    protected static string $infolistHiddenEndpoint = 'lg';
    ...
}
```

默认情况下, infolist 将会右对齐, 你可以通过设置 `$infolistAlignment` 来改变对齐方式。

```php
<?php

namespace App\Filament\Pages;

use Filament\Support\Enums\Alignment;
use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...
    protected static Alignment $infolistAlignment = Alignment::Left;
    ...
}
```


## Advanced features

### Multi-tenancy support

默认支持多租户，如果你的 `filament panel` 支持多租户，你需要在 `model` 中添加 `getScopeAttributes` 方法并且添加 team_id 字段。

Multi-tenancy 是基于 kalnoy/nestedset 的 scoped 实现的，你可以点击 [查看详细文档](https://github.com/lazychaser/laravel-nestedset?tab=readme-ov-file#scoping)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
...

class YouModel extends Model
{
    ...

    public function getScopeAttributes(): array
    {
        return ['team_id', ...];
    }

    ...
}
```

如果你的 `filament panel` 支持多租户, 但是当前 page 不需要区分 tenancy, 只需要在 page 中设置 $isScopedToTenant = false 即可。


```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...
    protected static bool $isScopedToTenant = false;
    ...
}
```

### 支持 tabs

tabs 是基于 kalnoy/nestedset 的 scoped 实现的，你可以点击 [查看详细文档](https://github.com/lazychaser/laravel-nestedset?tab=readme-ov-file#scoping)

通过 tabFieldName 设置关联的 tab 字段名，设置 tabs, 不需要在 tab 上增加当前tab 条件，tab 条件会自动附加到 kalnoy/nestedset 的 scoping 参数中


```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    protected static ?string $tabFieldName = 'type';        // 关联的 tab 字段名

    public function getTabs(): array
    {
        return [
            'web' => Tab::make()->label('Website Navigation'),
            'shop' => Tab::make()->label('Shop Navigation')
        ];
    }

    ...
}
```

你需要在 `model` 中添加 `getScopeAttributes` 方法并且添加 tabFieldName 设置的字段 type。


```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
...

class YouModel extends Model
{
    ...

    public function getScopeAttributes(): array
    {
        return ['type', ...];
    }

    ...
}
```

### 如果需要设置额外的 scope kalnoy/nestedset 的 scoping 参数

定义 nestedScoped 方法

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    public function nestedScoped()
    {
        return ['category_id' => 5];
    }
    ...
}
```

### 增加自定义查询条件

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    public function getEloquentQuery($query)
    {
        return $query->where('status', 'normal');
    }
    ...
}
```


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [smallnews](https://github.com/Wsmallnews)
- [kalnoy/nestedset](https://github.com/lazychaser/laravel-nestedset)
- [15web/filament-tree](https://github.com/15web/filament-tree)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

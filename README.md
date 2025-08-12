### 支持多语言

#### 发布语言包
```
php artisan vendor:publish --tag=sn-filament-nestedset-lang
```


### 定义 tree 为空时候的提示文字

```
public string $emptyLabel = '没有导航数据';
```


### 默认支持 tenancy

需添加 tenancy field

```
public function getScopeAttributes(): array
{
    return ['team_id', ...];
}
```

如果不需要区分 tenancy, 只需要在 page 中设置

```
static::$isScopedToTenant = false       // 设置方法需要在查一下 filament 的
```


### 支持 tabs

```
protected string $tabFieldName = 'type';        // 关联的 tab 字段名
```

设置 tabs, 不需要在 tab 上增加当前tab 条件，tab 条件会自动附加到 kalnoy/nestedset 的 scoping 参数中
```
public function getTabs(): array
{
    return [
        'web' => Tab::make()->label('Website Navigation'),
        'shop' => Tab::make()->label('Shop Navigation')
    ];
}
```

model 的 getScopeAttributes 方法增加 tabField 字段

```
public function getScopeAttributes(): array
{
    return ['type', ...];
}
```


### 如果需要设置额外的 scope kalnoy/nestedset 的 scoping 参数

定义 nestedScoped 方法

```
public function nestedScoped()
{
    return ['category_id' => 5];
}
```

### 增加查询条件

```
public function getEloquentQuery($query)
{
    return $query->where('status', 'normal');
}
```



# Filament tree build on kalnoy/nestedset

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wsmallnews/filament-nestedset.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/filament-nestedset)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/filament-nestedset/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/wsmallnews/filament-nestedset/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/filament-nestedset/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/wsmallnews/filament-nestedset/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/wsmallnews/filament-nestedset.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/filament-nestedset)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require wsmallnews/filament-nestedset
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-nestedset-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-nestedset-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-nestedset-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentNestedset = new Wsmallnews\FilamentNestedset();
echo $filamentNestedset->echoPhrase('Hello, Wsmallnews!');
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
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

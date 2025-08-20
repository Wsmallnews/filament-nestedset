# Filament tree build on kalnoy/nestedset

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wsmallnews/filament-nestedset.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/filament-nestedset)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/filament-nestedset/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/wsmallnews/filament-nestedset/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/filament-nestedset/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/wsmallnews/filament-nestedset/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/wsmallnews/filament-nestedset.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/filament-nestedset)


Filament tree build on kalnoy/nestedset, support multi language. support Multi-tenancy

## Overview

* Elegant UI, consistent with the default style of the filament page
* The Filament nestedset plugin is built on [kalnoy/nestedset](https://github.com/kalnoy/nestedset)
* Support multi-tenancy, you can easily create nestedset pages among multiple tenants
* Nestedset level is unlimited by default, but you can limit the nestedset levels if you wish
* Support tabs consistent with the Listing records of the filament panel. You can switch between different nestedset data through tabs on the current page

## Screenshots

![Dark](https://raw.githubusercontent.com/Wsmallnews/filament-nestedset/main/assets/dark.jpg?raw=true)  
![Light](https://raw.githubusercontent.com/Wsmallnews/filament-nestedset/main/assets/light.jpg?raw=true)
![Create](https://raw.githubusercontent.com/Wsmallnews/filament-nestedset/main/assets/create.jpg?raw=true)

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
     * Restrict deletion of nodes with children
     */
    'allow_delete_parent' => false,

    /*
     * Restrict deletion of root nodes, even if 'allow_delete_parent' is true, root nodes can be deleted.
     */
    'allow_delete_root' => false,
];
```

## Prepare your model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
...

class YouModel extends Model
{
    use NodeTrait;

    ...
}

```

You should add fields to your model. replacing `your_model_table` with the name of your model table

Add fields in the new model

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('your_model_table', function (Blueprint $table) {
            ...
            $table->nestedSet();
            ...
        });
    }
};
```

Add fields to an existing model

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('your_model_table', function (Blueprint $table) {
            $table->nestedSet();
        });
    }
};
```

And run the migration

```bash
php artisan migrate
```


## Usage

### Create the nestedset page

```bash
php artisan make:filament-nestedset-page
```

### Please define attribute name of the nodes in your tree, eg. title or name

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

### Define form schema

If the schema for create and edit are the same, you can define the schema method.

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

If the schema for create and edit are different, you can define createSchema and editSchema methods separately.


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


### Define the prompt text when the tree is empty

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    public string $emptyLabel = 'no test data';

    ...

}
```

### Limit nestedset level

Nestedset level is unlimited by default, you can limit the nestedset levels by: 

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    public ?int $level = 3;

    ...

}
```


### Other customizable properties

```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    protected static ?string $model = NavigationModel::class;
    
    protected static ?string $modelLabel = 'Test Management';

    protected static ?string $title = 'Page Title';

    protected static ?string $navigationLabel = 'Test Navigation';

    protected static ?string $navigationGroup = 'Test Group';

    protected static ?string $slug = 'tests';

    protected static string $recordTitleAttribute = 'name';

    protected static ?string $pluralModelLabel = 'Test Management';

    protected static ?int $navigationSort = 1;

    ...
}
```

### Display additional attributes

You can define additional attributes to display in each row through the infolistSchema method

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

By default, the infolist will be displayed at the `md` breakpoint and above. You can change the display breakpoint by setting `$infolistHiddenEndpoint`.

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

By default, the infolist will be right-aligned. You can change the alignment by setting `$infolistAlignment`.

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

Multi-tenancy features is supported by default. If your filament panel supports multi-tenancy, you need to add the getScopeAttributes method to your model and add the team_id field.

Multi-tenancy features is implemented based on `kalnoy/nestedset` scoped feature. You can [view detailed documentation here](https://github.com/lazychaser/laravel-nestedset?tab=readme-ov-file#scoping)

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

If your filament panel supports multi-tenancy, but the current page doesn't need to distinguish tenancy, just set `$isScopedToTenant = false` in the page.

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

### Tabs support

Tabs are implemented based on `kalnoy/nestedset` scoped feature. You can [view detailed documentation here](https://github.com/lazychaser/laravel-nestedset?tab=readme-ov-file#scoping)

Set the associated tab field name using tabFieldName. And setting tabs array, you don't need to add the current tab condition on the tab, as the tab condition will be automatically appended to `kalnoy/nestedset` scoping parameters.


```php
<?php

namespace App\Filament\Pages;

use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class Test extends NestedsetPage
{
    ...

    protected static ?string $tabFieldName = 'type';

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

You need to add the getScopeAttributes method to your model and add the field set by tabFieldName (`type` in this case).

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

### Additional scope parameters

If you need to set additional scope parameters for `kalnoy/nestedset` scoping

Define the `nestedScoped` method

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

You need to add the getScopeAttributes method to your model and add the field set.

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
        return ['category_id', ...];
    }

    ...
}
```


### Add custom eloquent query conditions

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

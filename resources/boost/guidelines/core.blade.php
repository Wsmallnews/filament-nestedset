## Nestedset 包（wsmallnews/filament-nestedset）

`wsmallnews/filament-nestedset` 是基于 [kalnoy/nestedset](https://github.com/lazychaser/laravel-nestedset) 的 Filament 嵌套集树形管理插件，支持 Filament v4/v5、多语言、多租户和 Tabs 筛选。命名空间根为 `Wsmallnews\FilamentNestedset`，Blade 视图前缀为 `sn-filament-nestedset`，配置文件为 `config/sn-filament-nestedset.php`。

### 核心架构

依赖 `kalnoy/nestedset` 的 `NodeTrait` 实现嵌套集模型，通过 `scoped` 特性支持多租户和 Tabs 筛选。提供两种使用方式：

- **NestedsetPage**：继承 Filament `Page` 的完整嵌套集管理页面（CRUD、拖拽排序、修复树）
- **Nestedset Livewire 组件**：可嵌入任意页面的只读树形展示组件

### NestedsetPage（管理页面基类）

`Wsmallnews\FilamentNestedset\Pages\NestedsetPage` 继承 `Filament\Pages\Page`，使用 traits：`CanUseDatabaseTransactions`、`HasTabs`、`HasUnsavedDataChangesAlert`、`InteractsWithFormActions`。

#### 创建页面

```bash
php artisan make:filament-nestedset-page
```

生成的页面类继承 `NestedsetPage`，需设置 `$model` 和 `$recordTitleAttribute`。

#### 静态属性（类级别配置，通过子类覆盖）

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$model` | `?string` | `null` | 嵌套集模型类名，**必须设置** |
| `$modelLabel` | `?string` | `null` | 模型标签，为空时自动从 model 推断 |
| `$recordTitleAttribute` | `string` | `'name'` | 节点标题字段名 |
| `$level` | `?int` | `null` | 嵌套集层级限制，`null` = 不限制 |
| `$emptyLabel` | `?string` | `''` | 树为空时的提示文本 |
| `$tabFieldName` | `?string` | `null` | Tabs 筛选的字段名 |
| `$infolistAlignment` | `Alignment` | `Alignment::Right` | Infolist 对齐方式 |
| `$infolistHiddenEndpoint` | `string` | `'md'` | Infolist 显示的最小断点 |
| `$isScopedToTenant` | `bool` | `true` | 是否关联多租户 |
| `$navigationIcon` | `string\|BackedEnum\|null` | `'heroicon-o-bars-3-bottom-right'` | 导航图标（继承自 Page） |

#### 非静态属性（实例属性）

| 属性 | 类型 | 说明 |
|---|---|---|
| `$activeTab` | `?string` | 当前选中的 Tab（`#[Url]` 绑定） |
| `$view` | `string` | 页面 Blade 视图路径 |

#### 可覆盖方法

@verbatim
```php
// 自定义 schema（create 和 edit 共用）
protected function schema(array $arguments): array { return []; }

// create 和 edit 分别定义
protected function createSchema(array $arguments): array { return []; }
protected function editSchema(array $arguments): array { return []; }

// Infolist 附加属性展示
protected function infolistSchema(): array { return []; }

// 自定义节点标签，支持 HtmlString
public function getRecordLabel(Model $item): HtmlString | string { ... }

// 自定义嵌套集查询条件
public function getEloquentQuery($query) { return $query->where('status', 'normal'); }

// 额外的 scope 参数（kalnoy/nestedset scoped）
public function nestedScoped() { return ['category_id' => 5]; }

// 动态层级限制
public function getLevel(): ?int { return static::$level; }

// Tabs 配置
public function getTabs(): array
{
    return [
        'web' => Tab::make()->label('Website Navigation'),
        'shop' => Tab::make()->label('Shop Navigation'),
    ];
}
```
@endverbatim

#### 操作 Actions

页面提供以下内置 Actions：

| Action | 说明 |
|---|---|
| `createAction()` | 创建节点（header action） |
| `createChildAction()` | 创建子节点（行内） |
| `editAction()` | 编辑节点 |
| `deleteAction()` | 删除节点（受 `allow_delete_parent` / `allow_delete_root` 配置控制） |
| `moveNodeAction()` | 拖拽排序确认 |
| `fixTreeAction()` | 修复树结构（header action） |

#### 模型要求

模型必须 use `Kalnoy\Nestedset\NodeTrait`，否则 `mount()` 会抛出 `NestedsetException`。

```php
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait;

    // 多租户 / Tabs 支持：定义 scope attributes
    public function getScopeAttributes(): array
    {
        return ['team_id', 'type'];
    }
}
```

### Nestedset Livewire 组件（树形展示）

`Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset` 继承 `Livewire\Component`，提供可嵌入的树形展示。

#### 静态属性（子类覆盖）

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$model` | `?string` | `null` | 嵌套集模型类名 |
| `$recordTitleAttribute` | `string` | `'name'` | 节点标题字段名 |

#### 实例属性（可通过 Blade 属性传入）

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$showLevel` | `?string` | `null` | 显示的层级限制 |
| `$emptyLabel` | `?string` | `''` | 树为空时的提示文本 |
| `$view` | `?string` | `'sn-filament-nestedset::livewire.components.nestedset'` | 组件视图 |
| `$recordView` | `?string` | `'sn-filament-nestedset::components.nestedset-record'` | 节点记录视图 |

#### 可覆盖方法

@verbatim
```php
// 自定义节点标签
public function getRecordLabel(Model $record): HtmlString | string { ... }

// 自定义节点跳转链接
public function getRecordUrl(Model $record): string | HtmlString | null { return null; }

// 自定义节点激活状态
public function getHasActive(Model $record): bool { return false; }

// 自定义数据源（必须覆盖或设置 $model）
public function getNestedset(): Collection { ... }

// 自定义查询条件
public function getEloquentQuery($query) { return $query; }

// 额外 scope 参数
public function nestedScoped() { return []; }
```
@endverbatim

#### 事件

| 事件 | 触发时机 |
|---|---|
| `sn-filament-nestedset-leaf-click` | 点击叶子节点 |
| `sn-filament-nestedset-node-click` | 点击非叶子节点 |

#### 使用示例

@verbatim
```php
use Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset;

class Categories extends Nestedset
{
    protected static ?string $model = Category::class;

    protected static string $recordTitleAttribute = 'name_label';

    #[On('sn-filament-nestedset-leaf-click')]
    public function clickCategory($recordId)
    {
        $this->categoryId = $recordId;
    }
}
```
@endverbatim

### 表单字段

#### KalnoyNestedsetSelectTree

`Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree` 继承 `codewithdennis/filament-select-tree` 的 `SelectTree`，支持嵌套集层级限制。

```php
use Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree;

KalnoyNestedsetSelectTree::make('parent_id')
    ->level(2)              // 限制可选层级（null = 不限制）
    ->searchable()
    ->enableBranchNode()    // 允许选择非叶子节点
    ->withCount()
    ->query(fn () => Category::query(), titleAttribute: 'name', parentAttribute: 'parent_id');
```

### 配置

`config/sn-filament-nestedset.php`：

```php
return [
    'allow_delete_parent' => false,                // 是否允许删除有子节点的节点
    'allow_delete_root' => false,                   // 是否允许删除根节点
    'create_action_modal_show_parent_select' => true,  // 创建弹窗是否显示父级选择
    'show_create_child_node_action_in_row' => true,    // 行内是否显示"创建子节点"按钮
    'autoload_assets' => true,                      // 是否自动加载 CSS（自定义主题时关闭）
];
```

### 多租户支持

基于 `kalnoy/nestedset` 的 `scoped` 特性。模型需定义 `getScopeAttributes()` 返回 scope 字段数组。页面默认 `$isScopedToTenant = true`，自动将 `team_id` 加入 scope。

如果面板支持多租户但当前页面不需要，设置 `$isScopedToTenant = false`。

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| Page 基类 | `Wsmallnews\FilamentNestedset\Pages\NestedsetPage` |
| Livewire 组件 | `Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset` |
| 表单字段 | `Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree` |
| Artisan 命令 | `Wsmallnews\FilamentNestedset\Commands\MakeNestedsetPageCommand` |
| 异常 | `Wsmallnews\FilamentNestedset\Exceptions\NestedsetException` |
| ServiceProvider | `Wsmallnews\FilamentNestedset\FilamentNestedsetServiceProvider` |

### 常见错误

- **模型必须 use `NodeTrait`**，否则 `mount()` 抛出 `NestedsetException`。
- **`$level` 设置为 `1` 时只能有根节点**，至少 `2` 才能选择父级（`createAction` 中 `getLevel() >= 2` 才显示父级选择字段）。
- **`$recordTitleAttribute` 是 `protected static`**，在子类中用 `protected static string $recordTitleAttribute = 'title'` 覆盖，不要用实例属性。
- **Livewire 组件必须覆盖 `getNestedset()` 或设置 `$model`**，否则抛出异常。
- **`autoload_assets` 关闭后需在自定义主题 CSS 中手动引入**：`@import '../../../../vendor/wsmallnews/filament-nestedset/resources/css/index.css'`。
- **多租户 scope 需要模型定义 `getScopeAttributes()`**，返回的字段必须包含 `team_id`。
- **拖拽移动节点受 `$level` 限制**，超过层级限制时操作会被取消并提示。

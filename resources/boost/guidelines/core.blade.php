## Nestedset 包（wsmallnews/filament-nestedset）

`wsmallnews/filament-nestedset` 是基于 [kalnoy/nestedset](https://github.com/lazychaser/laravel-nestedset) 的 Filament 嵌套集树形管理插件，支持 Filament v4/v5、多语言、多租户和 Tabs 筛选。命名空间根为 `Wsmallnews\FilamentNestedset`，Blade 视图前缀为 `sn-filament-nestedset`，配置文件为 `config/sn-filament-nestedset.php`。

### 核心架构

依赖 `kalnoy/nestedset` 的 `NodeTrait` 实现嵌套集模型，通过 `scoped` 特性支持多租户和 Tabs 筛选。当前采用 **Page + Widget + Sidebar Component + Frontend Component** 架构（四者都是必须被继承的抽象基类，包内不注册任何组件）：

- **NestedsetPage**（`Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage`）：继承 Filament `Page` 的抽象管理页面，使用 `InteractsWithNestedset`，提供 CRUD、拖拽排序、修复树、scope 查询和默认页面视图。
- **Filament Widget**（`Wsmallnews\FilamentNestedset\Filament\Pages\Widgets\Nestedset`）：继承 `Filament\Widgets\Widget` 的抽象 Widget，同样使用 `InteractsWithNestedset`，适合嵌入自定义 Filament 页面。
- **Sidebar Nestedset Component**（`Wsmallnews\FilamentNestedset\Filament\Components\SidebarNestedset`）：继承 `Livewire\Component` 的 panel 侧栏可管理树（行内 CRUD + 事件筛选联动），同样 use `InteractsWithNestedset`。
- **Frontend Livewire Component**（`Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset`）：继承 `Livewire\Component` 的前端只读树形展示组件。

### NestedsetPage（管理页面基类）

`Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage` 继承 `Filament\Pages\Page`，使用 `Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset`。该 concern 又使用 `HasNestedsetActions` 和 Filament `HasTabs`。

#### 创建页面

```bash
php artisan make:filament-nestedset-page
```

生成的页面类继承 `NestedsetPage`，需设置 `$model`，通常也会设置 `$recordTitleAttribute`。

#### 静态属性（类级别配置，通过子类覆盖）

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$model` | `?string` | `null` | 嵌套集模型类名，**必须设置** |
| `$modelLabel` | `?string` | `null` | 模型标签，为空时自动从 model 推断 |
| `$recordTitleAttribute` | `string` | `'name'` | 节点标题字段名 |
| `$level` | `?int` | `null` | 嵌套集层级限制，`null` = 不限制 |
| `$emptyLabel` | `?string` | 翻译文本 | 树为空时的提示文本 |
| `$emptyTipLabel` | `?string` | 翻译文本 | 树为空时的辅助提示 |
| `$tabFieldName` | `?string` | `null` | Tabs 筛选的字段名 |
| `$infolistAlignment` | `Alignment` | `Alignment::Right` | Infolist 对齐方式 |
| `$infolistHiddenEndpoint` | `string` | `'3xl'` | Infolist 显示的最小**树容器宽度**断点（CSS 容器查询刻度，按树容器实际宽度而非视口判断）。允许值：`3xs` 16rem、`2xs` 18rem、`xs` 20rem、`sm` 24rem、`md` 28rem、`lg` 32rem、`xl` 36rem、`2xl` 42rem、`3xl` 48rem、`4xl` 56rem、`5xl` 64rem、`6xl` 72rem、`7xl` 80rem。完整说明见 [Tailwind 容器尺寸对照表](https://tailwindcss.com/docs/responsive-design#container-size-reference) |
| `$isScopedToTenant` | `bool` | `true` | 是否关联 Filament 当前租户 |
| `$navigationIcon` | `string\|BackedEnum\|null` | `Heroicon::OutlinedBars3BottomRight` | 导航图标（继承自 Page） |

#### 实例属性

| 属性 | 类型 | 说明 |
|---|---|---|
| `$activeTab` | `?string` | 当前选中的 Tab（`#[Url]` 绑定） |
| `$view` | `string` | 页面 Blade 视图路径，默认 `sn-filament-nestedset::filament.pages.nestedset-page` |

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
protected function getRecordLabel(Model $record): HtmlString | string { ... }

// 自定义嵌套集查询条件；用于继续收窄已经 scoped 的查询
protected function getEloquentQuery($query) { return $query->where('status', 'normal'); }

// 额外的 scope 参数（kalnoy/nestedset scoped）
protected function nestedScoped(): array { return ['category_id' => 5]; }

// 动态层级限制
protected static function getLevel(): ?int { return static::$level; }

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

### 查询与 scope 规则

`InteractsWithNestedset::getQuery()` 会按以下顺序构建查询：

1. 当 `static::isScopedToTenant()` 为 `true` 且 `Filament::getTenant()` 存在时，加入 `team_id => tenant id`。
2. 当 `static::getTabFieldName()` 非空时，加入 `tabFieldName => $this->activeTab`。
3. 合并 `nestedScoped()` 返回的自定义 scope。使用 `array_merge()`，所以自定义 scope 与租户/Tab scope 使用相同 key 时会覆盖前面的值。
4. 有任何 scope 时使用 `Model::scoped($scopes)`；没有 scope 时使用 `(new $model)->newScopedQuery()`。
5. 最后调用 `getEloquentQuery($query)->defaultOrder()`。

### 操作 Actions

页面/Widget/Sidebar 组件共用以下内置 Actions（`HasNestedsetActions`）：

| Action | 返回类型 | 说明 |
|---|---|---|
| `createAction()` | `Action`（实际为 `CreateAction`） | 创建节点（header action） |
| `createChildAction()` | `Action`（实际为 `CreateAction`） | 创建子节点（行内） |
| `editAction()` | `Action`（实际为 `EditAction`） | 编辑节点，通过 scoped query 解析记录 |
| `deleteAction()` | `Action`（实际为 `DeleteAction`） | 删除节点，受 `$allowDeleteParent` / `$allowDeleteRoot` 静态属性控制 |
| `moveNodeAction()` | `Action` | 拖拽排序确认，受 `$level` 层级限制控制 |
| `fixNestedsetAction()` | `Action` | 对当前 scoped 查询执行 `fixTree()` 修复树结构 |

`createAction()` 会把当前 scoped query model attributes 合并到提交数据中，用于自动带上 `team_id`、Tab 字段、自定义 scope 字段等；创建时会从 `parent_id` 或 `parentId` argument 中解析父节点，并在保存前移除 `parent_id`。

### 模型要求

模型必须 use `Kalnoy\Nestedset\NodeTrait`，否则 `mount()` / `mountInteractsWithNestedset()` 会抛出 `NestedsetException`。

```php
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait;

    // 多租户 / Tabs / 自定义 scope 支持：定义 scope attributes
    public function getScopeAttributes(): array
    {
        return ['team_id', 'type'];
    }
}
```

### Filament Widget（Nestedset）

`Wsmallnews\FilamentNestedset\Filament\Pages\Widgets\Nestedset` 继承 `Filament\Widgets\Widget`，实现 `HasActions` 和 `HasSchemas`，并使用：

- `Filament\Actions\Concerns\InteractsWithActions`
- `Filament\Schemas\Concerns\InteractsWithSchemas`
- `Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset`

默认 `$columnSpan = 'full'`，默认视图为 `sn-filament-nestedset::filament.pages.widgets.nestedset`。

### Sidebar Nestedset 组件（侧栏管理树）

`Wsmallnews\FilamentNestedset\Filament\Components\SidebarNestedset` 继承 `Livewire\Component`（实现 `HasActions` + `HasSchemas`，use `InteractsWithNestedset`），是与管理页/树 Widget 同构的第三个基类：面向 panel 侧栏的可管理树（内嵌行内 CRUD），**必须被继承使用，包内不注册**（由调用方包在 ServiceProvider 注册，或宿主视图用 `@livewire(子类::class, [props])` 类名实例化）。

- 视图只输出树行本身（可折叠循环嵌套树 + 底部全宽 outlined 小尺寸 createAction 按钮）——**不含外层容器、标题、哨兵行**，由宿主 blade 自行组装；空态用 `x-filament::empty-state :contained="false"`。
- 子类必须实现 `schema()`（与 page 同契约——空 schema 的 action 不会以 modal 挂起）。
- **点击事件**：行是 `<a>`，`wire:click` 纯前端 `$dispatch`（与前台组件同一契约，零服务端请求）：叶子 `sn-filament-nestedset-leaf-click`、非叶子 `sn-filament-nestedset-node-click`，payload `{ recordId, hasChild }`；宿主 `#[On]` 监听筛选。
- **选中态**：宿主唯一持有，经 `#[Reactive] $activeId` prop 回传，`getHasActive()` 钩子判断高亮（子类可覆写）。
- **节点链接**：`getRecordUrl()` 默认 null → `href=javascript:;`（仅派发）；返回完整属性 HtmlString（`generate_href_html()`）时点击走链接——普通字符串会被 `{{ }}` 转义，必须 HtmlString。
- **行操作**：`getRowActions()`（HasNestedsetActions）默认收进 ⋯ ActionGroup（`getRowActionsInGroup() ?? true`），平铺时图标态（`getShowRowActionLabels() ?? false`）——基类经 getter 覆写默认值（基类层不能声明与 trait 同名的静态属性，默认值不同会 fatal）；子类可声明属性/覆写 getter/调 setter。
- **子类默认展开陷阱**：树默认服务端渲染为展开态（`isExpanded: true`）。若改为初始隐藏（x-cloak + 折叠），宿主 reactive 重渲染时 Livewire morph 会对显示分歧加 `hidden` 属性，Alpine（只操作 inline style）无法解除 → 永久卡死。
- **类名实例化命名**：`@livewire(\App\...\Tree::class)` 的组件在 Livewire 中名为点记法（`app.filament.components.xxx`），浏览器 `Livewire.all()` 按此查找。
- 嵌套子组件视图（`$recordView`）必须位于 `resources/views/components/` 下（blade 匿名组件命名空间约定 `包名::路径` 映射到 `views/components/`）。

### Nestedset Livewire 组件（树形展示）

`Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset` 继承 `Livewire\Component`，提供可嵌入前端页面的只读树形展示。

#### 实例属性（可通过 Blade 属性传入）

| 属性 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `$model` | `?string` | `null` | 嵌套集模型类名 |
| `$recordTitleAttribute` | `string` | `'name'` | 节点标题字段名 |
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
protected function getEloquentQuery($query) { return $query; }

// 额外 scope 参数
protected function nestedScoped(): array { return []; }
```
@endverbatim

默认 `getNestedset()` 会执行 `getQuery()->withDepth()->get()`，当 `$showLevel` 非空时保留 `depth <= showLevel` 的记录，然后调用 `toTree()`。

#### 事件

| 事件 | 触发时机 |
|---|---|
| `sn-filament-nestedset-leaf-click` | 点击叶子节点 |
| `sn-filament-nestedset-node-click` | 点击非叶子节点 |

#### 使用示例

@verbatim
```php
use Livewire\Attributes\On;
use Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset;

class Categories extends Nestedset
{
    public ?string $model = Category::class;

    public string $recordTitleAttribute = 'name_label';

    #[On('sn-filament-nestedset-leaf-click')]
    public function clickCategory($recordId): void
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

`level(1)` 只加载根节点；`level(2+)` 会加载 `depth < level` 的非根节点；`level(null)` 不限制层级。

### 配置

`config/sn-filament-nestedset.php` 仅保留一项：

```php
return [
    'autoload_assets' => true,    // 是否自动加载 CSS（自定义主题时关闭）
];
```

其余行为定制全部走「子类静态属性 / 方法覆写」（包内组件必须被继承，静态属性即配置）：

| 静态属性 | 默认 | 说明 |
|---|---|---|
| `protected static ?bool $rowActionsInGroup` | false（侧栏组件 true） | 行操作是否收进 ⋯ ActionGroup（组内 label 恒显示） |
| `protected static ?bool $showRowActionLabels` | true（侧栏组件 false） | 平铺时行操作按钮图标 / 图文形态 |
| `protected static ?bool $showCreateChildNodeActionInRow` | true | 行内是否显示「创建子节点」 |
| `protected static ?bool $allowDeleteParent` | false | 是否允许删除有子级的节点 |
| `protected static ?bool $allowDeleteRoot` | false | 是否允许删除根节点 |
| `protected static ?bool $hasFormParentSelect` | true | 创建弹窗是否显示父级选择 |

### UI 行为与动画机制

- **展开/折叠持久化**：每个记录 `x-data` 中 `open: $persist(true).as('sn-tree-{id}')`（localStorage）。子级容器 `x-show + x-collapse + x-cloak`，`x-cloak` 配合包 CSS 的 `[x-cloak]{display:none!important}` 保证刷新时折叠节点不闪跳。
- **首次加载展开动画**：Alpine 初始渲染跳过 x-show 过渡，因此顶层记录经 `animateLoad` prop 门控（`hydrated` 初始 false，`init` 中 `$nextTick(() => setTimeout(() => hydrated = true, 150))` 延迟到首帧绘制后翻转才播放动画）。仅顶层门控——嵌套节点同时动画会互相测不到高度。箭头旋转与子级显隐绑定同一表达式 `open && hydrated`，同帧联动。
- **箭头图标**：`ChevronRight` 默认朝右 = 折叠朝向（与服务端渲染的初始隐藏状态天然一致，无需 x-cloak），展开时 `rotate-90`。不要改回 ChevronDown + `-rotate-90`（默认朝向是展开态，刷新会闪跳）。
- **卡片入场动画**：树卡片用纯 CSS 关键帧 `sn-nestedset-enter`（grid-template-rows 0fr→1fr）。**禁止**改用 x-collapse/x-show 做加载动画——其依赖 transitionend，时序不利时卡死在 height:0 并把树裁剪成零可见区域，IntersectionObserver 判定不可见 → JS 模块永不加载 → 展开折叠整体失效。
- **infolist 容器查询**：`@container` 在树根 `.fi-sn-nestedset`（所有层级共用同一容器宽度，不受子级 pl-6 缩进影响）；显示类用 `@{breakpoint}:flex!`（important 后缀击败主题样式表后加载的同层 `.hidden`）。
- **行操作按钮文字**：`HasNestedsetActions::getRowActions()` 平铺分支统一应用展示配置（`->link()` + `hiddenLabel(fn () => ! getShowRowActionLabels())`）；action 定义本身不带 `->link()`——显式 view 会压过 ActionGroup 的 grouped 默认，导致组内 item 渲染成裸链接（蓝色文字、无内边距）。收进 ActionGroup 时 label 恒显示；`true` 时由包 CSS 容器查询 `@container (width < 24rem)` 以 sr-only 隐藏。
- **Blade 组件标签上的 Alpine 动态属性用双冒号**：`<x-filament::icon ::class="isExpanded ? 'rotate-90' : ''" />`——组件标签上单个 `:class` 会被 Blade 当 PHP 绑定求值（Alpine 表达式报 Undefined constant）；双冒号是 Blade 的转义写法输出为 `:class`（文档 blade#escaping-attribute-rendering）。原生元素（li/div）上单冒号 `:aria-expanded` 合法（Blade 不解析原生标签属性）；`:icon="Heroicon::Xxx"` 这类 PHP 求值绑定的组件 props 仍是单冒号。
- **primary 色规范**：前台组件与侧栏组件的 primary 文字/图标一律亮色 500、暗色 600（`text-primary-500 dark:text-primary-600`、`group-hover:bg-primary-500 dark:group-hover:bg-primary-600`）；hover/选中浅色背景豁免（`hover:bg-gray-100 dark:hover:bg-white/5`、`bg-primary-50 dark:bg-primary-500/10`）。

### 开发工作流（改包内 CSS / JS 后）

```bash
cd addons/filament-nestedset && npm run build        # 或 npm run build:styles
php artisan filament:assets                           # 发布到 public/（应用根目录执行）
```

- 包资产 URL 版本号是 Composer 对无版本 path 仓库的占位符（恒定不变），重新发布后**浏览器需强刷**（Ctrl+F5）才能拿到新 CSS。
- blade 中新引入的 Tailwind 工具类（如新断点、新变体）必须重建 CSS 才会生成，只改 blade 不重建时类存在但无样式。

### Release 规范（发布流程）

1. **发布前同步**：`git fetch origin --tags`，目标分支 `git pull --ff-only` 与远程对齐。**禁止重打任何已存在的稳定版本 tag**：Packagist 稳定版本一经发布永久绑定当时的 commit（tag 即使从 GitHub 删除，Packagist 仍保留该版本）；发现目标版本号在 Packagist 已存在时（含仅 Packagist 有、远程已删除的情况），直接递增版本号发新版本，不要重打。
2. **tag 命名**：`v` + semver，与现有 tag 一致；维护线（`v2` 分支）取该分支最新 tag 的最小版本号 +1（如 v2.2.1 → v2.2.2）；主线（`v3` 分支）发布正式版（v3.0.0 → v3.1.0 / v4.0.0）。
3. **Release notes 优先用 GitHub 自动生成**：`gh release create <tag> --title <tag> --generate-notes`（自动包含 PR 作者归属与 New Contributors）；仅当版本范围内没有任何 PR 时，才手写中文变更清单（commit 按时间倒序）；范围 = 上一 tag 到当前 HEAD 的全部提交。
4. **发布顺序**：push 分支 → push tag → create release；发布后确认 Latest 标记指向主版本线（`gh release edit <tag> --latest`），并提醒 Packagist 会自动抓取新 tag（可手动点 Update 立即触发）。
5. **主应用侧注意**：宿主项目的 `vendor/wsmallnews/filament-nestedset` 是指向本目录的软链，在本仓库切过分支（如 `v2`）后必须切回 `v3`，否则宿主应用会引用旧分支代码而损坏；发布完成后宿主仓库需提交 submodule 指针变更。

### 多租户支持

基于 `kalnoy/nestedset` 的 `scoped` 特性。模型需定义 `getScopeAttributes()` 返回 scope 字段数组。页面默认 `$isScopedToTenant = true`，自动将 `team_id` 加入 scope。

如果面板支持多租户但当前页面不需要，设置 `$isScopedToTenant = false`。

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| Page 基类 | `Wsmallnews\FilamentNestedset\Filament\Pages\NestedsetPage` |
| Filament Widget | `Wsmallnews\FilamentNestedset\Filament\Pages\Widgets\Nestedset` |
| Sidebar 组件基类 | `Wsmallnews\FilamentNestedset\Filament\Components\SidebarNestedset` |
| InteractsWithNestedset | `Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\InteractsWithNestedset` |
| HasNestedsetActions | `Wsmallnews\FilamentNestedset\Filament\Pages\Concerns\HasNestedsetActions` |
| 前端 Livewire 组件 | `Wsmallnews\FilamentNestedset\Livewire\Components\Nestedset` |
| 表单字段 | `Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree` |
| Artisan 命令 | `Wsmallnews\FilamentNestedset\Commands\MakeNestedsetPageCommand` |
| 异常 | `Wsmallnews\FilamentNestedset\Exceptions\NestedsetException` |
| ServiceProvider | `Wsmallnews\FilamentNestedset\FilamentNestedsetServiceProvider` |

### 常见错误

- **模型必须 use `NodeTrait`**，否则 `mount()` / `mountInteractsWithNestedset()` 抛出 `NestedsetException`。
- **`$level` 设置为 `1` 时只能有根节点**，至少 `2` 才能选择父级（`createAction` 中 `getLevel() >= 2` 才显示父级选择字段）。
- **多租户 / Tabs / 自定义 scope 需要模型定义 `getScopeAttributes()`**，返回的字段必须包含对应 scope 字段，如 `team_id`、Tab 字段、`nestedScoped()` 字段。
- **`nestedScoped()` 与租户/Tab 使用相同 key 时会覆盖前面的 scope**，这是当前 `array_merge()` 行为。
- **`getEloquentQuery()` 应继续收窄已经 scoped 的查询**，不要绕过 `Model::scoped($scopes)`，否则多租户、Tabs 或自定义 scope 可能失效。
- **Livewire 前端组件必须覆盖 `getNestedset()` 或设置 `$model`**，否则抛出异常。
- **Sidebar 子类必须实现 `schema()`**：空 schema 的 action 不以 modal 挂起（mountAction 后 `Property [$mountedActionSchema0] not found`）。
- **直接组合 trait 的类不能声明与 trait 同名但默认值不同的静态属性**（fatal「definitions differ」）——第二层基类经 getter 覆写语义默认，第三层子类自由声明。
- **`autoload_assets` 关闭后需在自定义主题 CSS 中手动引入**：`@import '../../../../vendor/wsmallnews/filament-nestedset/resources/css/index.css'`（注意该文件包含 `[x-cloak]`、容器查询 sr-only、入场动画等规则，跳过引入会导致刷新闪跳和响应式失效）。
- **拖拽移动节点受 `$level` 限制**，超过层级限制时操作会被取消并提示。
- **改包内 CSS 或在 blade 引入新工具类后必须重建 + 发布**（见"开发工作流"），否则浏览器拿到的还是旧样式。

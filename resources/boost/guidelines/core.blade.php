## Nestedset 包（wsmallnews/filament-nestedset）

`wsmallnews/filament-nestedset` 是基于 [kalnoy/nestedset](https://github.com/lazychaser/laravel-nestedset) 的 Filament 嵌套集树形管理插件。命名空间根 `Wsmallnews\FilamentNestedset`，视图前缀 `sn-filament-nestedset`，配置 `config/sn-filament-nestedset.php`。

### 版本兼容：SidebarNestedset 仅 Filament v5+

Filament v4 绑定 Livewire v3，而 Livewire slots 是 v4 才有的特性。

| 类 | Filament v4 | Filament v5 |
|---|---|---|
| `Filament\Pages\NestedsetPage` | ✅ | ✅ |
| `Filament\Pages\Widgets\Nestedset` | ✅ | ✅ |
| `Livewire\Components\Nestedset` | ✅ | ✅ |
| `Filament\Components\SidebarNestedset` | ⚠️ 可渲染但拿不到 slot 内容 | ✅ |

`SidebarNestedset` 的视图用 `$slot` 与命名 slot `mobileTrigger`。视图已做空安全（`$slot ?? ''`、`isset($slot)`），所以在 v4 下不会报错，但宿主塞进去的自定义内容会被丢弃。**在 v4 项目里不要依赖这个组件的 slot 能力**；需要自定义内容时，v4 项目请自行覆写 `getContentComponent()` / `getRecordView()`。

### 四个基类：全部必须继承，包内零注册

- **`NestedsetPage`** —— Filament `Page` 子类，带 CRUD、拖拽排序、修树、scope 查询与默认视图。
- **`Filament\Pages\Widgets\Nestedset`** —— Filament `Widget` 子类，嵌入自定义页面。
- **`Filament\Components\SidebarNestedset`** —— Livewire 组件，panel 侧栏可管理树（行内 CRUD + 事件筛选联动）。
- **`Livewire\Components\Nestedset`** —— Livewire 组件，前台只读树。

四者共用 `InteractsWithNestedset` 与 `HasNestedsetActions`，配置面完全一致。

### NestedsetPage 静态配置

| 属性 | 默认 | 说明 |
|---|---|---|
| `$model` | `null` | 嵌套集模型，**必须设置** |
| `$recordTitleAttribute` | `'name'` | 节点标题字段 |
| `$level` | `null` | 层级上限，`null` = 不限 |
| `$tabFieldName` | `null` | Tabs 筛选字段 |
| `$isScopedToTenant` | `true` | 是否按 Filament 当前租户隔离 |
| `$infolistAlignment` | `Alignment::Right` | Infolist 对齐 |
| `$infolistHiddenEndpoint` | `'3xl'` | infolist 显示所需的最小**容器**宽度（Tailwind 容器刻度 `3xs`–`7xl`，3xl = 48rem） |

可覆写：`schema()` / `createSchema()` / `editSchema()` / `infolistSchema()` / `getRecordLabel()` / `getRecordUrl()` / `getEloquentQuery()` / `nestedScoped()` / `getLevel()` / `getTabs()`。

### 查询与 scope

`getQuery()` 按序组装：① 租户 `team_id`（`isScopedToTenant()` 且存在租户时）→ ② Tab 字段 → ③ `nestedScoped()`（`array_merge`，同 key 覆盖前两者）→ ④ `Model::scoped($scopes)`，无 scope 时用 `newScopedQuery()` → ⑤ `getEloquentQuery()->defaultOrder()`。

`getEloquentQuery()` 只能继续收窄已经 scoped 的查询，绕过 `Model::scoped()` 会让租户/Tab 隔离失效。

### Actions 与删除守卫

`createAction()` / `createChildAction()` / `editAction()` / `deleteAction()` / `moveNodeAction()` / `fixNestedsetAction()`（均在 `HasNestedsetActions`）。

- `createAction()` 会把当前 scoped query 的属性并进提交数据（自动带 `team_id`、Tab 字段、自定义 scope），保存前移除 `parent_id`。
- 删除守卫只拦**有子节点**的节点：`$allowDeleteParent` 控制能否连带子树，`$allowDeleteRoot` 额外控制这条连带是否允许从根开始。无子节点永远可删。
- 拖拽移动同样受 `$level` 限制，超限会被取消并提示。

### 模型要求

模型必须 `use Kalnoy\Nestedset\NodeTrait`，否则 `mountInteractsWithNestedset()` 抛 `NestedsetException`。

多租户 / Tabs / 自定义 scope 需要模型定义 `getScopeAttributes()`，返回对应字段：

```php
public function getScopeAttributes(): array
{
    return ['team_id', 'type'];
}
```

**初始化钩子**：`mountInteractsWithNestedset()` 由 Livewire 的 `mount{TraitBasename}()` 约定自动调用（页面 / Widget / 侧栏三条链路都是）。**不要在子类里再写 `mount()` 转发**，否则守卫与默认 Tab 跑两次。

### SidebarNestedset 契约

- 视图只输出树行 + 底部 `createAction` 按钮，**不含外层容器 / 标题 / 哨兵行**，由宿主自己组装。
- 子类必须实现 `schema()`：空 schema 的 action 不会以 modal 挂起（报 `Property [$mountedActionSchema0] not found`）。
- 行是 `<a>`，点击纯前端 `$dispatch`（零请求）：叶子 `sn-filament-nestedset-leaf-click`、非叶子 `sn-filament-nestedset-node-click`，payload `{ recordId, hasChild }`。
- 选中态由宿主唯一持有，经 `#[Reactive] $activeId` 回传，`getHasActive()` 判断高亮。
- `getRecordUrl()` 返回 `null`（仅派发事件）或**完整属性** `HtmlString`（`generate_href_html()`）。视图走 `{{ }}`（即 `e()`），裸 URL 字符串会落成标签里的裸词、静默失效。
- `$view` / `$contentComponent` / `$recordView` 是 `#[Locked]` 装配属性。**PHP attribute 不继承**：子类重声明同名属性必须自己再写一遍 `#[Locked]`。
- 行操作默认收进 ⋯ ActionGroup（`getRowActionsInGroup()` 返回 `true`），平铺时图标态（`getShowRowActionLabels()` 返回 `false`）。
- 行操作菜单**不用 teleport**：`shouldTeleportRowActions()` 返回 `false`，宿主的滚动容器必须是定位祖先（`relative`），面板宽度经 `getRowActionsDropdownWidth()` 传入且不能超过容器宽度。
- 嵌套记录视图（`$recordView`）必须放在 `resources/views/components/` 下（Blade 匿名组件命名空间约定）。

### 行视觉契约（CSS class，不是组件）

树节点与宿主自写条目一律复用 `resources/css/index.css` 的 `.sn-nestedset-row` + `.sn-nestedset-row-label`，状态类 `is-active` / `is-muted`，行容器挂 `group` 才能让 trailing 操作 `group-hover` 显隐。

- 行容器只有 `pr-2`，外层必须补 `px-2`。
- 名称里的计数、徽标由宿主 `getRecordLabel()` 自行组装并负责样式，本包只保证占满剩余宽度可截断。
- 状态优先级靠 CSS 书写顺序：`is-muted` < `:hover` < `is-active`，不要在 blade 里用 Tailwind 覆盖行背景（本包独立构建，`bg-primary-50` 未定义）。
- `data-sidebar-row` 必须是 `li[data-sidebar-item]` 的**直接子元素**：拖拽落点用 `li[data-sidebar-item] > :scope > [data-sidebar-row]` 定位，多套一层会丢失落点高亮。

### 动画与图标

- 展开/折叠状态 `$persist` 到 localStorage；子级 `x-show + x-collapse + x-cloak`。
- 首次加载用纯 CSS 关键帧 `sn-nestedset-enter`，**不要**改用 `x-collapse` / `x-show` 做入场：它依赖 transitionend，时序不利时卡在 `height:0`，IntersectionObserver 判定不可见 → JS 模块永不加载。
- 箭头固定 `ChevronRight`（默认朝右 = 折叠），展开 `rotate-90`。不要改回 `ChevronDown + -rotate-90`，刷新会闪。
- 顶层节点才播放入场动画（`animateLoad` 门控）：嵌套节点同时动画会互相测不到高度。

### 前台 Livewire 组件

| 属性 | 默认 | 说明 |
|---|---|---|
| `$model` | `null` | 模型类名 |
| `$recordTitleAttribute` | `'name'` | 标题字段 |
| `$showLevel` | `null` | 显示层级，**0-based**（`0` = 只显示根级） |
| `$emptyLabel` | `''` | 空态文案 |

可覆写：`getRecordLabel()` / `getRecordUrl()` / `getHasActive()` / `getNestedset()` / `getEloquentQuery()` / `nestedScoped()`。

默认 `getNestedset()` 执行 `getQuery()->withDepth()->get()`，`$showLevel` 非空时保留 `depth <= showLevel` 再 `toTree()`。`withDepth()` 用 `count(1) - 1` 统计祖先，所以根节点 `depth = 0`。

`$model` 未设置时 `getQuery()` 抛 `NestedsetException`。

### 表单字段

`KalnoyNestedsetSelectTree`（继承 `codewithdennis/filament-select-tree` 的 `SelectTree`）：

```php
KalnoyNestedsetSelectTree::make('parent_id')
    ->level(2)              // 可选层级上限，null = 不限
    ->searchable()
    ->enableBranchNode()
    ->query(fn () => Category::query(), titleAttribute: 'name', parentAttribute: 'parent_id');
```

`level(1)` 只加载根节点；`level(2+)` 加载 `depth < level` 的节点。

### 配置

```php
return [
    'autoload_assets' => true,   // 自定义主题时关闭，并在主题 CSS 里手动 @import 本包 index.css
];
```

关闭 `autoload_assets` 后必须手动引入包 CSS，否则 `[x-cloak]`、容器查询、入场动画全部失效（症状：刷新闪跳、响应式错乱）。

其余行为全部走「静态属性 / 方法覆写」：`$rowActionsInGroup`、`$showRowActionLabels`、`$showCreateChildNodeActionInRow`、`$allowDeleteParent`、`$allowDeleteRoot`、`$hasFormParentSelect`。

### 常见错误

- 模型没 `use NodeTrait` → `NestedsetException`。
- `$level = 1` 时只会有根节点；至少 `2` 才出现父级选择。
- `nestedScoped()` 的 key 与租户/Tab 相同会覆盖前面的 scope（`array_merge` 行为）。
- 子类重声明 `$view` 等属性却忘了 `#[Locked]` → 失去防篡改保护。
- 改包内 CSS 或在 blade 引入新 Tailwind 类后必须重建并重新发布资产，否则浏览器拿到的还是旧样式。

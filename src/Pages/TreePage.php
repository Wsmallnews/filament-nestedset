<?php

namespace Wsmallnews\FilamentNestedset\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Support\Enums\IconSize;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use Livewire\Attributes\On;
use Livewire\Features\SupportEvents\Event;
use Throwable;
use Wsmallnews\FilamentNestedset\Exceptions\NestedsetException;

use function Filament\Support\get_model_label;

abstract class TreePage extends Page
{
    use CanUseDatabaseTransactions;
    use HasTabs;
    use HasUnsavedDataChangesAlert;
    use InteractsWithFormActions;

    public $level = 2;

    protected static ?string $model = null;

    protected static ?string $modelLabel = null;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3-bottom-right';

    protected static bool $isScopedToTenant = true;

    protected static string $view = 'sn-filament-nestedset::pages.tree';

    protected string $tabFieldName = '';

    /**
     * @throws NestedsetException
     */
    public function mount(): void
    {
        $model = static::getModel();

        $concerns = class_uses($model);

        if (! \in_array(NodeTrait::class, $concerns, true)) {
            throw new NestedsetException(
                \sprintf('Model should use %s', NodeTrait::class),
            );
        }
    }

    public function getQuery()
    {
        $model = static::getModel();

        $scopes = [];
        if (static::isScopedToTenant() && ($tenant = Filament::getTenant())) {
            $scopes['team_id'] = $tenant->id;
        }

        if ($this->getTabFieldName()) {
            $scopes[$this->getTabFieldName()] = $this->activeTab;
        }

        // 自定义 scope
        if (method_exists($this, 'nestedScoped')) {
            $scopes = array_merge($scopes, $this->nestedScoped());
        }

        if ($scopes) {
            $query = $model::scoped($scopes);
        } else {
            $query = (new $model)->newScopedQuery();
        }

        // 自定义条件
        if (method_exists($this, 'getEloquentQuery')) {
            $query = $this->getEloquentQuery($query);
        }

        $query = $query->defaultOrder();

        return $query;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createAction(),
            $this->fixTreeAction(),
        ];
    }

    public function createAction(): Action
    {
        return $this->configureCreateAction(
            CreateAction::make()
                ->modelLabel(self::getModelLabel())
        );
    }

    public function createChildAction(): Action
    {
        return $this->configureCreateAction(
            CreateAction::make('createChild')
                ->label('创建子节点')
                ->link()
                ->icon('heroicon-o-plus-circle')
        );
    }

    /**
     * 配置 createAction 操作
     */
    private function configureCreateAction(CreateAction $action): Action
    {
        return $action->model(self::getModel())     // Action 需要 model attribute is a string
            ->mutateFormDataUsing(function (array $data): array {
                $model = $this->getQuery()->getModel();     // 这个获取的是包含 scopes 中的 attributes 数据的 model 实例

                return [
                    ...$data,
                    ...$model->getAttributes(),          // 这里填充 scoped 设置的数据
                ];
            })
            ->form(fn (array $arguments): array => method_exists($this, 'createSchema') ? $this->createSchema($arguments) : $this->schema($arguments))
            ->using(function (array $data, array $arguments): Model {
                // 优先使用表单中的 parent_id
                $parentId = $data['parent_id'] ?? ($arguments['parentId'] ?? 0);

                $parent = $this->getQuery()->find($parentId);
                unset($data['parent_id']);

                return $this->getModel()::create(
                    attributes: $data,
                    parent: $parent,
                );
            })
            ->after(fn (): Event => $this->dispatch('filament-tree-updated'))
            ->createAnother(false);
    }

    public function editAction(): EditAction
    {
        return EditAction::make()
            ->record(function (array $arguments) {
                $id = $arguments['id'] ?? 0;

                return $id ? $this->getQuery()->findOrFail($id) : null;
            })
            ->form(fn (array $arguments): array => method_exists($this, 'editSchema') ? $this->editSchema($arguments) : $this->schema($arguments))
            ->after(fn (): Event => $this->dispatch('filament-tree-updated'))
            ->icon('heroicon-m-pencil-square')->iconSize(IconSize::Small)
            ->link();
    }

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->requiresConfirmation()
            ->before(function (DeleteAction $action, Model $record): void {
                if (! $this->canBeDeleted($record)) {
                    Notification::make()
                        ->danger()
                        ->title('删除失败')
                        ->body('存在子级节点，无法删除。')
                        ->send();

                    $action->cancel();
                    $action->halt();
                }
            })
            ->record(function (array $arguments) {
                $id = $arguments['id'] ?? 0;

                return $id ? $this->getQuery()->find($id) : null;
            })
            ->after(fn (): Event => $this->dispatch('filament-tree-updated'))
            ->icon('heroicon-m-trash')->iconSize(IconSize::Small)
            ->link();
    }

    /**
     * 排序确认操作
     */
    public function moveNodeAction(): Action
    {
        return Action::make('moveNode')
            ->label('移动节点')
            ->action(function (array $arguments) {
                // parentId 和 changeIds 内容
                // 1. 当同级节点之间调整顺序时，parentId 是自己的parentId, changeIds 是 parentId 下的所有节点的 id 数组
                // 2. 当将节点移动到上级时，parentId 为要移动到的上级的 id, changeIds 为移动后的 parentId 下的所有节点的 id 数组
                // 3. 当将节点移动到其他节点的下级时， parentId 为 要移动到节点的 id，changeIds 为移动后的 parentId 下的所有节点的 id 数组

                $parentId = $arguments['parentId'] ?? null;
                $changeIds = $arguments['changeIds'] ?? [];
                $changeIdsOrder = array_flip($changeIds);

                // 查询
                $changeNodes = $this->getQuery()->whereIn('id', $changeIds)->orderByRaw('FIELD(id, ' . implode($changeIds) . ')')->get();
                // 使用 sortBy 方法对 Collection 进行排序
                $changeNodes = $changeNodes->sortBy(function ($node) use ($changeIdsOrder) {
                    return $changeIdsOrder[$node->id] ?? PHP_INT_MAX; // 如果 id 不在数组中，将其放在最后
                });
                $changeNodes = $changeNodes->values();

                $parent = $parentId ? $this->getQuery()->find($parentId) : null;
                if (! $parent) {
                    $previous = null;
                    $changeNodes->map(function ($node, $key) use (&$previous) {
                        if (is_null($previous)) {
                            // 获取当前树第一个节点
                            $firstRoot = $this->getQuery()->roots()->orderBy('_lft')->first(); // 获取当前第一个根节点
                            $node->makeRoot()->beforeNode($firstRoot)->save();          // 设置为根节点，并且设置到 firstRoot 之前
                        } else {
                            // 设为根节点，并且移动到指定节点之后
                            $node->makeRoot()->afterNode($previous)->save();
                        }
                        $previous = $node;
                    });
                } else {
                    $previous = null;
                    $changeNodes->map(function ($node, $key) use ($parent, &$previous) {

                        if (is_null($previous)) {
                            // parent 的第一个节点
                            $node->prependToNode($parent)->save();
                        } else {
                            // parent 下的节点，移动到指定节点之后
                            $node->appendToNode($parent)->afterNode($previous)->save();
                        }

                        dd($node);

                        $previous = $node;

                        // if (is_null($previous)) {
                        //     $parent->prependNode($node);        // 将 node 作为 parent 的第一个节点
                        // } else {
                        //     // parent 下的节点，移动到指定节点之后
                        //     $parent->appendNode($node);

                        //     dd($previous, $node);
                        //     $node->afterNode($previous)->save();
                        // }

                        // $previous = $node;
                    });
                    dd(11);
                }
            })
            ->color('danger');
    }

    public function fixTreeAction(): Action
    {
        return Action::make('fixTree')
            ->label('修复树')
            ->icon('heroicon-s-wrench')
            ->action(function (Action $action): void {
                $this->dispatch('filament-tree-updated');

                try {
                    $this->getQuery()->fixTree();
                } catch (Throwable $e) {
                    report($e);             // 记录错误，但不终止程序

                    Notification::make()
                        ->danger()
                        ->title($e->getMessage())
                        ->send();

                    $action->failure();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('修复成功')
                    ->send();

                $action->success();
            });
    }

    protected function schema(array $arguments): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        $tree = $this->getQuery()->withDepth()->get()->toTree();

        return [
            'tree' => $tree,
        ];
    }

    #[On('filament-tree-updated')]
    public function refresh(): void
    {
        // Re-render component
    }

    public function canBeDeleted(Model $record): bool
    {
        if (
            config('sn-filament-nestedset.allow-delete-parent') === false
            && $record->children->isNotEmpty()
        ) {
            return false;
        }

        return ! (config('sn-filament-nestedset.allow-delete-root') === false && $record->children->isNotEmpty() && $record->isRoot());
    }

    public function tabFieldName($tabFieldName): self
    {
        $this->tabFieldName = $tabFieldName;

        return $this;
    }

    public function getTabFieldName(): string
    {
        return $this->tabFieldName;
    }

    public static function scopeToTenant(bool $condition = true): void
    {
        static::$isScopedToTenant = $condition;
    }

    public static function isScopedToTenant(): bool
    {
        return static::$isScopedToTenant;
    }

    public static function getModel()
    {
        return static::$model;
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel ?? get_model_label(static::getModel());
    }
}

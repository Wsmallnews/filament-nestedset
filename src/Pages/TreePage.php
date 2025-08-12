<?php

namespace Wsmallnews\FilamentNestedset\Pages;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Support\Enums\IconSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Kalnoy\Nestedset\NestedSet;
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
                ->icon('heroicon-o-plus-circle'),
            'createChild'
        );
    }

    /**
     * 配置 createAction 操作
     */
    private function configureCreateAction(CreateAction $action, $type = 'create'): Action
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

            // ->form(function (array $arguments) use ($type) {
            //     $schema = method_exists($this, 'createSchema') ? $this->createSchema($arguments) : $this->schema($arguments);

            //     if ($type == 'create' && $this->hasFormParentSelect()) {
            //         $parentSelect = Arr::wrap($this->getParentSelect());

            //         $schema = array_merge([
            //             ...$parentSelect
            //         ], $schema);
            //     }

            //     return $schema;
            // })
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
            ->action(function (Action $action, array $arguments) {
                // 当前节点 id
                $id = $arguments['id'] ?? 0;
                // 移动到的 父节点 id
                $parent = !isset($arguments['parent']) || empty($arguments['parent']) ? null : $arguments['parent'];
                // 移动前的父节点 id
                $ancestor = !isset($arguments['ancestor']) || empty($arguments['ancestor']) ? null : $arguments['ancestor'];
                // 从哪里移动的索引
                $from = $arguments['from'] ?? 0;
                // 移动到的索引
                $to = $arguments['to'] ?? 0;

                // 当前节点
                $node = $this->getQuery()->findOrFail($id);
                
                if ($parent == $node->getAttribute(NestedSet::PARENT_ID)) {
                    // 父级未改变，仅移动顺序
                    if ($from == $to) return;

                    $shift = $from - $to;
                    $shift > 0 ? $node->up($shift) : $node->down(abs($shift));
                } else {
                    if (is_null($parent)) {
                        // 移动到根节点，并且调整顺序
                        $node->saveAsRoot();
    
                        $siblingsCount = $node->refresh()->siblings()->count();
                        $shift = $siblingsCount - $to;
    
                        $node->up($shift);
                    } else {
                        // 插入指定父级, 并调整顺序
                        $parentNode = $node->query()->findOrFail($parent);
                        $parentNode->prependNode($node);
                        if ($to > 0) {
                            $node->down($to);
                        }
                    }
                }

                Notification::make()
                    ->success()
                    ->title('节点移动成功')
                    ->send();

                $action->success();
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
            config('sn-filament-nestedset.allow_delete_parent') === false
            && $record->children->isNotEmpty()
        ) {
            return false;
        }

        return ! (config('sn-filament-nestedset.allow_delete_root') === false && $record->children->isNotEmpty() && $record->isRoot());
    }



    // protected function hasFormParentSelect(): bool
    // {
    //     $childrenAddMethod = config('sn-filament-nestedset.children_add_method') ?? 'both';

    //     return in_array($childrenAddMethod, ['both', 'form']);
    // }


    // protected function getParentSelect(): array | Field
    // {
    //     return SelectTree::make('parent_id')->label('父节点')
    //         ->relationship(relationship: 'parent', titleAttribute: 'name', parentAttribute: NestedSet::PARENT_ID)
    //         ->searchable()
    //         ->enableBranchNode()     // 可以选择非根节点
    //         ->withCount()
    //         ->required()
    //         ->placeholder('请选择父节点')
    //         ->emptyLabel('未搜索到父节点')
    //         ->treeKey('NestedParentId');
    // }


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

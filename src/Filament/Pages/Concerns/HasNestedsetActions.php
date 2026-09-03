<?php

namespace Wsmallnews\FilamentNestedset\Filament\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Kalnoy\Nestedset\NestedSet;
use Livewire\Features\SupportEvents\Event;
use Throwable;
use Wsmallnews\FilamentNestedset\Forms\Fields\KalnoyNestedsetSelectTree;

trait HasNestedsetActions
{
    /**
     * @var array<string, Model>
     */
    protected array $nestedsetActionRecords = [];

    protected function getHeaderActions(): array
    {
        return [
            $this->createAction(),
            $this->fixNestedsetAction(),
        ];
    }

    public function createAction(): Action
    {
        return $this->configureCreateAction(
            CreateAction::make()
                ->modelLabel(static::getModelLabel())
        );
    }

    public function createChildAction(): Action
    {
        return $this->configureCreateAction(
            CreateAction::make('createChild')
                ->label(__('sn-filament-nestedset::nestedset.action.create_child_node'))
                ->link()
                ->icon(Heroicon::PlusCircle),
            'createChild'
        )->hiddenLabel(fn (): bool => ! $this->showRowActionLabels());
    }

    /**
     * 配置 createAction 操作
     */
    private function configureCreateAction(CreateAction $action, string $type = 'create'): Action
    {
        return $action->model(static::getModel())
            ->mutateDataUsing(function (array $data): array {
                $queryModel = $this->getQuery()->getModel();

                return [
                    ...$data,
                    ...$queryModel->getAttributes(),
                ];
            })
            ->schema(function (array $arguments) use ($type): array {
                $schema = method_exists($this, 'createSchema')
                    ? $this->createSchema($arguments)
                    : $this->schema($arguments);

                if ($type === 'create' && (is_null(static::getLevel()) || static::getLevel() >= 2) && $this->hasFormParentSelect()) {
                    $parentSelect = Arr::wrap($this->getParentSelect());

                    $schema = array_merge([
                        ...$parentSelect,
                    ], $schema);
                }

                return $schema;
            })
            ->using(function (array $data, array $arguments): Model {
                $parentId = $data['parent_id'] ?? ($arguments['parentId'] ?? 0);

                $parent = $this->getQuery()->find($parentId);
                unset($data['parent_id']);

                return static::getModel()::create(
                    attributes: $data,
                    parent: $parent,
                );
            })
            ->after(fn (): Event => $this->dispatch('sn-filament-nestedset-updated'))
            ->createAnother(false);
    }

    public function editAction(): Action
    {
        return EditAction::make()
            ->record(fn (Action $action, array $arguments): ?Model => $this->resolveNestedsetActionRecord($action, $arguments))
            ->schema(fn (array $arguments): array => method_exists($this, 'editSchema') ? $this->editSchema($arguments) : $this->schema($arguments))
            ->after(fn (): Event => $this->dispatch('sn-filament-nestedset-updated'))
            ->icon(Heroicon::PencilSquare)
            ->link()
            ->hiddenLabel(fn (): bool => ! $this->showRowActionLabels());
    }

    public function deleteAction(): Action
    {
        return DeleteAction::make()
            ->record(fn (Action $action, array $arguments): ?Model => $this->resolveNestedsetActionRecord($action, $arguments))
            ->before(function (Action $action, Model $record): void {
                if ($this->canBeDeleted($record)) {
                    return;
                }

                Notification::make()
                    ->danger()
                    ->title(__('sn-filament-nestedset::nestedset.action.delete_failed_title'))
                    ->body(__('sn-filament-nestedset::nestedset.action.delete_failed_body_has_child'))
                    ->send();

                $action->cancel();
                $action->halt();
            })
            ->after(fn (): Event => $this->dispatch('sn-filament-nestedset-updated'))
            ->icon(Heroicon::Trash)
            ->link()
            ->hiddenLabel(fn (): bool => ! $this->showRowActionLabels());
    }

    /**
     * 行操作按钮文字显示开关（sn-filament-nestedset.show_row_action_labels）：
     * 关闭时操作按钮只保留图标，label 由 Filament 以 sr-only + aria-label 渲染（无障碍名称保留）；
     * 开启时按树容器宽度响应式显隐（见包 CSS 的容器查询规则）
     */
    protected function showRowActionLabels(): bool
    {
        return (bool) config('sn-filament-nestedset.show_row_action_labels', true);
    }

    protected function resolveNestedsetActionRecord(Action $action, array $arguments): ?Model
    {
        $id = $arguments['id'] ?? null;

        if (blank($id) || is_null($action->getNestingIndex())) {
            return null;
        }

        return $this->nestedsetActionRecords[$id] ??= $this->getQuery()->findOrFail($id);
    }

    /**
     * 排序确认操作
     */
    public function moveNodeAction(): Action
    {
        return Action::make('moveNode')
            ->label(__('sn-filament-nestedset::nestedset.action.move_node'))
            ->action(function (Action $action, array $arguments): void {
                // 当前节点 id
                $id = $arguments['id'] ?? 0;
                // 移动到的 父节点 id
                $parent = ! isset($arguments['parent']) || empty($arguments['parent']) ? null : $arguments['parent'];
                // 移动前的父节点 id
                $ancestor = ! isset($arguments['ancestor']) || empty($arguments['ancestor']) ? null : $arguments['ancestor'];
                // 从哪里移动的索引
                $from = $arguments['from'] ?? 0;
                // 移动到的索引
                $to = $arguments['to'] ?? 0;

                // 当前节点
                $node = $this->getQuery()->findOrFail($id);

                if ($parent == $node->getAttribute(NestedSet::PARENT_ID)) {
                    // 父级未改变，仅移动顺序
                    if ($from == $to) {
                        return;
                    }

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
                        $parentNode = $this->getQuery()->withDepth()->findOrFail($parent);
                        if (! is_null(static::getLevel()) && $parentNode->depth >= static::getLevel() - 1) {
                            Notification::make()
                                ->danger()
                                ->title(__('sn-filament-nestedset::nestedset.action.move_node_failed'))
                                ->body(__('sn-filament-nestedset::nestedset.action.move_node_failed_body_depth', ['level' => static::getLevel()]))
                                ->send();

                            $action->cancel();
                            $action->halt();
                        }
                        $parentNode->prependNode($node);
                        if ($to > 0) {
                            $node->down($to);
                        }
                    }
                }

                Notification::make()
                    ->success()
                    ->title(__('sn-filament-nestedset::nestedset.action.move_node_success'))
                    ->send();

                $action->success();
            })
            ->color('danger');
    }

    public function fixNestedsetAction(): Action
    {
        return Action::make('fixNestedset')
            ->label(__('sn-filament-nestedset::nestedset.action.fix_nestedset'))
            ->icon(Heroicon::Wrench)
            ->action(function (Action $action): void {
                $this->dispatch('sn-filament-nestedset-updated');

                try {
                    $this->getQuery()->fixTree();
                } catch (Throwable $e) {
                    report($e);            // 记录错误，但不终止程序

                    Notification::make()
                        ->danger()
                        ->title($e->getMessage())
                        ->send();

                    $action->failure();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__('sn-filament-nestedset::nestedset.action.fix_nestedset_success'))
                    ->send();

                $action->success();
            });
    }

    public function hasFormParentSelect(): bool
    {
        return config('sn-filament-nestedset.create_action_modal_show_parent_select') ?? false;
    }

    protected function getParentSelect(): array | Field
    {
        return KalnoyNestedsetSelectTree::make('parent_id')->label(__('sn-filament-nestedset::nestedset.field.parent_select_field'))
            ->level(is_null(static::getLevel()) ? null : (static::getLevel() - 1))      // 能让用户选择的层级，需要 -1,level = null 不限制
            ->searchable()
            ->query(function () {
                return $this->getQuery();
            }, titleAttribute: 'name', parentAttribute: NestedSet::PARENT_ID)
            ->enableBranchNode()
            ->withCount()
            ->placeholder(__('sn-filament-nestedset::nestedset.field.parent_select_field_placeholder'))
            ->emptyLabel(__('sn-filament-nestedset::nestedset.field.parent_select_field_empty_label'))
            ->treeKey('SnNestedParentId');
    }
}

<?php

namespace Wsmallnews\FilamentNestedset\Filament\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
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

    /** Collapse row actions into an ActionGroup */
    protected static ?bool $rowActionsInGroup = null;

    /** Show text labels on flat row actions */
    protected static ?bool $showRowActionLabels = null;

    /** Show the create-child action in each row */
    protected static ?bool $showCreateChildNodeActionInRow = null;

    /** Allow deleting nodes that have children */
    protected static ?bool $allowDeleteParent = null;

    /** Allow deleting root nodes */
    protected static ?bool $allowDeleteRoot = null;

    /** Show the parent select in the create modal */
    protected static ?bool $hasFormParentSelect = null;

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
                ->icon(Heroicon::PlusCircle)
                ->color('primary'),
            'createChild'
        );
    }

    /**
     * Configure the create actions
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

                if ($type === 'create' && (is_null(static::getLevel()) || static::getLevel() >= 2) && static::getHasFormParentSelect()) {
                    $parentSelect = Arr::wrap($this->getParentSelect());

                    $schema = array_merge([
                        ...$parentSelect,
                    ], $schema);
                }

                return $schema;
            })
            ->using(function (array $data, array $arguments): Model {
                $parentId = $data['parent_id'] ?? ($arguments['parentId'] ?? 0);

                $parent = $this->getQuery()->withDepth()->find($parentId);
                unset($data['parent_id']);

                // Depth guard: kalnoy's create() has no level awareness, block
                // children beyond static::getLevel() with a notification.
                if ($parent && ! is_null(static::getLevel()) && $parent->depth + 1 >= static::getLevel()) {
                    Notification::make()
                        ->danger()
                        ->title(__('sn-filament-nestedset::nestedset.action.create_failed_title'))
                        ->body(__('sn-filament-nestedset::nestedset.action.create_failed_body_depth', ['level' => static::getLevel()]))
                        ->send();

                    throw new Halt;
                }

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
            ->icon(Heroicon::PencilSquare);
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
            ->icon(Heroicon::Trash);
    }

    /**
     * Row actions of a tree node: create child / edit / delete, filtered by their
     * display conditions. Collapsed into an "..." ActionGroup (labels always shown)
     * when getRowActionsInGroup() is true; flat buttons follow getShowRowActionLabels().
     *
     * @return array<Action | ActionGroup>
     */
    public function getRowActions(Model $record): array
    {
        $actions = array_values(array_filter([
            $this->shouldShowCreateChildAction($record) ? ($this->createChildAction())(['parentId' => $record->getKey()]) : null,
            ($this->editAction())(['id' => $record->getKey()])->record($record),
            $this->canBeDeleted($record) ? ($this->deleteAction())(['id' => $record->getKey()])->record($record) : null,
        ]));

        if (static::getRowActionsInGroup()) {
            return [$this->wrapRowActionsInGroup($actions)];
        }

        return array_map(
            fn (Action $action): Action => $action
                ->link()
                ->hiddenLabel(fn (): bool => ! static::getShowRowActionLabels()),
            $actions,
        );
    }

    public static function getRowActionsInGroup(): bool
    {
        return static::$rowActionsInGroup ?? false;
    }

    public static function rowActionsInGroup(?bool $condition): void
    {
        static::$rowActionsInGroup = $condition;
    }

    /**
     * @param  array<Action>  $actions
     */
    protected function wrapRowActionsInGroup(array $actions): ActionGroup
    {
        return ActionGroup::make($actions)
            ->iconButton()
            ->icon(Heroicon::OutlinedEllipsisVertical)
            ->color('gray')
            ->tooltip(__('sn-filament-nestedset::nestedset.action.more_actions'))
            ->dropdownTeleport()
            ->dropdownPlacement('bottom-end');
    }

    /**
     * Whether the create-child action shows in this row: toggle × level headroom.
     */
    protected function shouldShowCreateChildAction(Model $record): bool
    {
        $hasNextLevel = is_null(static::getLevel()) || static::getLevel() > ((int) $record->depth + 1);

        return static::getShowCreateChildNodeActionInRow() && $hasNextLevel;
    }

    /**
     * Label visibility for flat row actions (labels always show inside an ActionGroup).
     * When false, actions render icon-only with the accessible name kept via aria-label.
     */
    public static function getShowRowActionLabels(): bool
    {
        return static::$showRowActionLabels ?? true;
    }

    public static function showRowActionLabels(?bool $condition): void
    {
        static::$showRowActionLabels = $condition;
    }

    public static function getShowCreateChildNodeActionInRow(): bool
    {
        return static::$showCreateChildNodeActionInRow ?? true;
    }

    public static function showCreateChildNodeActionInRow(?bool $condition): void
    {
        static::$showCreateChildNodeActionInRow = $condition;
    }

    public static function getAllowDeleteParent(): bool
    {
        return static::$allowDeleteParent ?? false;
    }

    public static function allowDeleteParent(?bool $condition): void
    {
        static::$allowDeleteParent = $condition;
    }

    public static function getAllowDeleteRoot(): bool
    {
        return static::$allowDeleteRoot ?? false;
    }

    public static function allowDeleteRoot(?bool $condition): void
    {
        static::$allowDeleteRoot = $condition;
    }

    /**
     * Whether the record may be deleted (children / root guards).
     */
    protected function canBeDeleted(Model $record): bool
    {
        if (static::getAllowDeleteParent() === false && $record->children->isNotEmpty()) {
            return false;
        }

        return ! (static::getAllowDeleteRoot() === false && $record->children->isNotEmpty() && $record->isRoot());
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
     * Drag-and-drop reorder confirmation.
     */
    public function moveNodeAction(): Action
    {
        return Action::make('moveNode')
            ->label(__('sn-filament-nestedset::nestedset.action.move_node'))
            ->action(function (Action $action, array $arguments): void {
                $id = $arguments['id'] ?? 0;
                $parent = ! isset($arguments['parent']) || empty($arguments['parent']) ? null : $arguments['parent'];
                $ancestor = ! isset($arguments['ancestor']) || empty($arguments['ancestor']) ? null : $arguments['ancestor'];
                $from = $arguments['from'] ?? 0;
                $to = $arguments['to'] ?? 0;

                $node = $this->getQuery()->findOrFail($id);

                if ($parent == $node->getAttribute(NestedSet::PARENT_ID)) {
                    // Parent unchanged: reorder among siblings only
                    if ($from == $to) {
                        return;
                    }

                    $shift = $from - $to;
                    $shift > 0 ? $node->up($shift) : $node->down(abs($shift));
                } else {
                    if (is_null($parent)) {
                        // Moved to the root level, then adjust position
                        $node->saveAsRoot();

                        $siblingsCount = $node->refresh()->siblings()->count();
                        $shift = $siblingsCount - $to;

                        $node->up($shift);
                    } else {
                        // Prepended to the target parent, then adjust position
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
                    report($e);            // log the error but keep going

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

    /**
     * Whether the create modal shows the parent select.
     */
    public static function getHasFormParentSelect(): bool
    {
        return static::$hasFormParentSelect ?? true;
    }

    public static function hasFormParentSelect(?bool $condition): void
    {
        static::$hasFormParentSelect = $condition;
    }

    protected function getParentSelect(): array | Field
    {
        return KalnoyNestedsetSelectTree::make('parent_id')->label(__('sn-filament-nestedset::nestedset.field.parent_select_field'))
            // selectable levels are one below the page level limit; null = unlimited
            ->level(is_null(static::getLevel()) ? null : (static::getLevel() - 1))
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

@props(['record', 'level', 'animateLoad' => false])

@php
    use Filament\Support\Enums\Alignment;
    use Filament\Schemas\Schema;
    use Filament\Support\Icons\Heroicon;

    $infolistAlignment = static::getInfolistAlignment();
    $infoListHiddenEndpoint = static::getInfolistHiddenEndpoint();

    // 是否还有下级
    $hasNextLevel = is_null($level) || $level > ($record->depth + 1);

    $canCreateChildren = false;
    if ($this->showCreateChildNodeActionInRow() && $hasNextLevel) {
        $canCreateChildren = true;
    }
@endphp

<div
    x-data="{
        open: $persist(true).as('sn-tree-{{ $record->getKey() }}'),
        hydrated: {{ $animateLoad ? 'false' : 'true' }},
        init () {
            if (this.hydrated) {
                return;
            }

            // Alpine 初始渲染跳过 x-show 过渡动画，这里延迟翻转 hydrated，
            // 让持久化为展开的节点在首次加载时播放 x-collapse 展开动画。
            // 先 $nextTick 等组件初始渲染完全就绪，再用 setTimeout 延迟到浏览器首帧绘制之后翻转：
            // 翻转过早（首帧绘制前）CSS 过渡会被快进，动画大概率不出现
            this.$nextTick(() => {
                setTimeout(() => { this.hydrated = true }, 150)
            })
        },
    }"
    wire:key="sn-filament-nestedset-record-{{ $record->getKey() }}"
    data-id="{{ $record->getKey() }}"
    class="fi-sn-nestedset-record"
    data-sortable-item
>
    <div class="fi-sn-nestedset-record-rowinfo flex justify-between relative group px-4 gap-4 hover:bg-gray-50 dark:hover:bg-white/5">
        <div class="flex gap-4 grow">
            <button
                class="fi-sn-nestedset-record-handle flex items-center ltr:rounded-l-lg rtl:rounded-r-lg"
                type="button"
                data-sortable-handle
            >
                <x-filament::icon 
                    class="text-gray-400 size-5 cursor-move ltr:-mr-2 rtl:-ml-2" 
                    :icon="Heroicon::Bars2"
                    aria-hidden="true" />
            </button>

            <div class="appearance-none px-3 py-4 ltr:text-left rtl:text-right inline-block">
                <span>{{ $this->getRecordLabel($record) }}</span>
            </div>

            @if($record->children->isNotEmpty())
                <button type="button" x-on:click="open = !open" title="Toggle children" class="appearance-none text-gray-500">
                    {{-- 图标默认朝右 = 折叠朝向，与 Alpine 初始化前的子级隐藏状态天然一致，无需 x-cloak；
                        展开时 rotate-90 转为朝下。旋转与子级显隐绑定同一表达式（open && hydrated），
                        顶层节点在子级开始展开的同一帧一起转为展开朝向 --}}
                    <x-filament::icon
                        class="size-5 font-bold transform transition-transform duration-200"
                        :icon="Heroicon::ChevronRight"
                        x-bind:class="{
                            'rotate-90': open && hydrated,
                        }"
                        aria-hidden="true" />
                </button>
            @endif

            @if ($this->hasInfolist())
                <div @class([
                    'fi-sn-nestedset-record-infolist hidden grow gap-x-4 px-4 items-center',
                    // 树容器宽度达到指定断点后显示 infolist（容器查询刻度见 $infolistHiddenEndpoint 文档）；
                    // important 后缀用于击败主题样式表中后加载的同层 .hidden（跨样式表同名 utilities 层按文档顺序合并）
                    match ($infoListHiddenEndpoint) {
                        '3xs' => '@3xs:flex!',
                        '2xs' => '@2xs:flex!',
                        'xs' => '@xs:flex!',
                        'sm' => '@sm:flex!',
                        'md' => '@md:flex!',
                        'lg' => '@lg:flex!',
                        'xl' => '@xl:flex!',
                        '2xl' => '@2xl:flex!',
                        '3xl' => '@3xl:flex!',
                        '4xl' => '@4xl:flex!',
                        '5xl' => '@5xl:flex!',
                        '6xl' => '@6xl:flex!',
                        '7xl' => '@7xl:flex!',
                        default => null,
                    },
                    match ($infolistAlignment) {
                        Alignment::Left, Alignment::Start => 'justify-start',
                        Alignment::Center => 'justify-center',
                        Alignment::Right, Alignment::End => 'justify-end',
                    },
                ])>
                    {{ Schema::make($this)
                        ->record($record)
                        ->components($this->infolistSchema())
                        ->view('sn-filament-nestedset::components.filament.nestedset-infolist') }}
                </div>
            @endif
        </div>

        <div class="flex grow-0 items-center gap-3">
            {{-- 一级 depth = 0 --}}
            @if($canCreateChildren)
                {{ ($this->createChildAction)(['parentId' => $record->getKey()]) }}
            @endif

            {{ ($this->editAction)(['id' => $record->getKey()])->record($record) }}

            @if($this->canBeDeleted($record))
                {{ ($this->deleteAction)(['id' => $record->getKey()])->record($record) }}
            @endif
        </div>
    </div>

    @if ($hasNextLevel)
        {{-- x-cloak：Alpine 初始化前先隐藏子节点，避免持久化为折叠状态时刷新出现“先展开再折叠”的跳动 --}}
        <div x-show="open && hydrated" x-collapse x-cloak class="divide-y ltr:pl-6 rtl:pr-6">
            <div
                @class([
                    'fi-sn-nestedset-record-child divide-y divide-gray-200 dark:divide-white/10',
                    'border-t border-gray-200 dark:border-white/10' => $record->children->isNotEmpty()
                ])
                wire:key="sn-filament-nestedset-record-{{ $record->getKey() }}-children"
                data-id="{{ $record->getKey() }}"
                x-data="nestedsetManager({
                    parentId: '{{ $record->getKey() }}'
                })"
            >
                @foreach ($record->children as $childKey => $child)
                    <x-sn-filament-nestedset::filament.nestedset-record 
                        :record="$child"
                        :level="$level"
                        key="sn-filament-nestedset-fi-record-component-{{ $child->getKey() }}" />
                @endforeach
            </div>
        </div>
    @endif
</div>

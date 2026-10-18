{{-- 模拟宿主主题视图：由 mount 参数 :view 传入（cms posts.blade.php 的用法） --}}
<ul class="theme-nestedset" role="menu">
    @foreach ($this->getNestedset() as $record)
        <x-dynamic-component
            :component="$this->getRecordView()"
            key="theme-record-{{ $record->getKey() }}"
            :record="$record"
            :first="$loop->first"
            :last="$loop->last"
            :current-level="1"
        />
    @endforeach
</ul>

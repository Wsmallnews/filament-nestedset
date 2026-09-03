<?php

namespace Wsmallnews\FilamentNestedset\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class TestCategory extends Model
{
    use NodeTrait;

    protected $table = 'test_categories';

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
    ];

    public function getScopeAttributes(): array
    {
        $scopes = ['scope_type', 'scope_id'];

        // team_id 为 null 时不能纳入 scope：嵌套集关联会生成 WHERE team_id = NULL，永远匹配不到行
        if (! is_null($this->attributes['team_id'] ?? null)) {
            $scopes[] = 'team_id';
        }

        return $scopes;
    }
}

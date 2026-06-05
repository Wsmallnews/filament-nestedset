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

        if (isset($this->attributes['team_id']) || array_key_exists('team_id', $this->attributes)) {
            $scopes[] = 'team_id';
        }

        return $scopes;
    }
}

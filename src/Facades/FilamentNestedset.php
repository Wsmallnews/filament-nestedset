<?php

namespace Wsmallnews\FilamentNestedset\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Wsmallnews\FilamentNestedset\FilamentNestedset
 */
class FilamentNestedset extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Wsmallnews\FilamentNestedset\FilamentNestedset::class;
    }
}

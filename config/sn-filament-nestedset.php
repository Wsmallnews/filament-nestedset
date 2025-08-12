<?php

// config for Wsmallnews/FilamentNestedset
return [
    /**
     * 限制删除带有子项的节点
     */
    'allow_delete_parent' => false,

    /*
     * 限制删除根节点，即使 'allow_delete_parent' 为 true，也可以删除根节点。
     */
    'allow_delete_root' => false,


    /**
     * 添加子节点的方式
     * 
     * form: 添加时，通过 select 选择父级节点
     * node: 在节点上点击 添加子节点 action
     * both: 两种方式同时存在
     */
    // 'children_add_method' => 'form'


    /*
     * If you want to see edit form as compact one,
     * you able to remove parent's select from it.
     * You still can drag'n'drop the nodes.
     */
    // 'show-parent-select-while-edit' => true,
];

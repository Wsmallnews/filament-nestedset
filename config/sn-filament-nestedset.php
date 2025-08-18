<?php

// config for Wsmallnews/FilamentNestedset
return [
    /**
     * Restrict deletion of nodes with children.
     */
    'allow_delete_parent' => false,

    /*
     * Restrict deletion of root nodes, even if 'allow_delete_parent' is true, root nodes can be deleted.
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

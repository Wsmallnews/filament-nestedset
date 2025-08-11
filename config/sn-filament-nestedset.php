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


    

    /*
     * If you want to see edit form as compact one,
     * you able to remove parent's select from it.
     * You still can drag'n'drop the nodes.
     */
    // 'show-parent-select-while-edit' => true,
];

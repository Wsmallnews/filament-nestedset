<?php

return [
    'nestedset' => [
        'empty_label' => 'No data',
        'empty_tip_label' => 'No data available',
    ],

    'action' => [
        'create_child_node' => 'Create child node',
        'create' => 'New',
        'more_actions' => 'More actions',

        'create_failed_title' => 'Creation failed',
        'create_failed_body_depth' => 'Creation failed, the node level cannot exceed :level levels.',

        'delete_failed_title' => 'Deletion failed',
        'delete_failed_body_has_child' => 'There are child nodes, Please delete child nodes first.',

        'move_node' => 'Move node',
        'move_node_success' => 'Node moved successfully',
        'move_node_failed' => 'Move node failed',
        'move_node_failed_body_depth' => 'Move node failed, the target node level cannot exceed the supported node level :level.',
        'move_node_failed_body_cycle' => 'A node cannot be moved inside itself or one of its descendants.',

        'fix_nestedset' => 'Fix nested set',
        'fix_nestedset_success' => 'Nested set fixed successfully',
    ],

    'field' => [
        'parent_select_field' => 'Parent node',
        'parent_select_field_placeholder' => 'Please select a parent node',
        'parent_select_field_empty_label' => 'No parent node found',
        'name' => 'Name',
    ],

    'sidebar' => [
        'label' => 'Sidebar',
        'search_placeholder' => 'Search nodes…',
        'search_no_results' => 'No matching nodes found',
        'sort_toggle' => 'Sort mode',
        'drag_handle' => 'Drag to reorder',
        'sort_active' => 'Sort mode: drag nodes to reorder',
        'sort_done' => 'Done',
    ],
];

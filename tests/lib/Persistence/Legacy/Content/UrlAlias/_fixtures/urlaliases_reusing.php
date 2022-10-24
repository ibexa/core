<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
return [
    'ezurlalias_ml' => [
        0 => [
            'action' => 'eznode:2',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '1',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '1',
            'parent' => '0',
            'text' => '',
            'text_md5' => 'd41d8cd98f00b204e9800998ecf8427e',
        ],
        1 => [
            'action' => 'nop:',
            'action_type' => 'nop',
            'alias_redirects' => '1',
            'id' => '2',
            'is_alias' => '0',
            'is_original' => '0',
            'lang_mask' => '1',
            'link' => '2',
            'parent' => '0',
            'text' => 'nop-element',
            'text_md5' => 'de55c2fff721217cc4cb67b58dc35f85',
        ],
        2 => [
            'action' => 'module:content/search',
            'action_type' => 'module',
            'alias_redirects' => '0',
            'id' => '3',
            'is_alias' => '1',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '3',
            'parent' => '2',
            'text' => 'search',
            'text_md5' => '06a943c59f33a34bb5924aaf72cd2995',
        ],
        3 => [
            'action' => 'eznode:314',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '5',
            'is_alias' => '0',
            'is_original' => '0',
            'lang_mask' => '4',
            'link' => '4',
            'parent' => '0',
            'text' => 'history-hello',
            'text_md5' => 'da94285592c46d4396d3ca6904a4aa8f',
        ],
        4 => [
            'action' => 'eznode:314',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '4',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '4',
            'parent' => '0',
            'text' => 'autogenerated-hello',
            'text_md5' => '2eb35041e168cb62fe790b7555a0e90d',
        ],
        5 => [
            'action' => 'eznode:314',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '6',
            'is_alias' => '1',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '6',
            'parent' => '0',
            'text' => 'custom-hello',
            'text_md5' => 'c0254f50ddbeea89a6523d79c330cd57',
        ],
    ],
    'ezcontentobject_tree' => [
        0 => [
            'node_id' => 1,
            'parent_node_id' => 1,
            'path_string' => '',
            'remote_id' => '',
        ],
        1 => [
            'node_id' => 2,
            'parent_node_id' => 1,
            'path_string' => '',
            'remote_id' => '',
        ],
        2 => [
            'node_id' => 314,
            'parent_node_id' => 2,
            'path_string' => '',
            'remote_id' => '',
        ],
        3 => [
            'node_id' => 315,
            'parent_node_id' => 2,
            'path_string' => '',
            'remote_id' => '',
        ],
    ],
    'ezcontent_language' => [
        0 => [
            'disabled' => 0,
            'id' => 2,
            'locale' => 'cro-HR',
            'name' => 'Croatian (Hrvatski)',
        ],
        1 => [
            'disabled' => 0,
            'id' => 4,
            'locale' => 'eng-GB',
            'name' => 'English (United Kingdom)',
        ],
    ],
    'ezurlalias_ml_incr' => [
        0 => [
            'id' => '1',
        ],
        1 => [
            'id' => '2',
        ],
        2 => [
            'id' => '3',
        ],
        3 => [
            'id' => '4',
        ],
        4 => [
            'id' => '5',
        ],
        5 => [
            'id' => '6',
        ],
    ],
];
<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
return [
    \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway::TABLE => [
        0 => [
            'action' => 'eznode:2',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '1',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '3',
            'link' => '1',
            'parent' => '0',
            'text' => '',
            'text_md5' => 'd41d8cd98f00b204e9800998ecf8427e',
        ],
        1 => [
            'action' => 'eznode:314',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '2',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '3',
            'link' => '2',
            'parent' => '0',
            'text' => 'path314',
            'text_md5' => 'fdbbfa1e24e78ef56cb16ba4482c7771',
        ],
        2 => [
            'action' => 'eznode:315',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '3',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '3',
            'parent' => '2',
            'text' => 'path315',
            'text_md5' => 'afbe70de5f03a22e867723655a995279',
        ],
    ],
    \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway::INCR_TABLE => [
        0 => [
            'id' => '1',
        ],
        1 => [
            'id' => '2',
        ],
        2 => [
            'id' => '3',
        ],
    ],
    \Ibexa\Core\Persistence\Legacy\Content\Language\Gateway::CONTENT_LANGUAGE_TABLE => [
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
        2 => [
            'disabled' => 0,
            'id' => 8,
            'locale' => 'ger-DE',
            'name' => 'German',
        ],
        3 => [
            'disabled' => 0,
            'id' => 16,
            'locale' => 'kli-KR',
            'name' => 'Klingon (Kronos)',
        ],
    ],
    'ibexa_content_tree' => [
        0 => [
            'node_id' => 1,
            'parent_node_id' => 1,
            'path_string' => '',
            'path_identification_string' => '',
            'remote_id' => '',
        ],
        1 => [
            'node_id' => 2,
            'parent_node_id' => 1,
            'path_string' => '',
            'path_identification_string' => '',
            'remote_id' => '',
        ],
        2 => [
            'node_id' => 314,
            'parent_node_id' => 2,
            'path_string' => '',
            'path_identification_string' => 'path314',
            'remote_id' => '',
        ],
        3 => [
            'node_id' => 315,
            'parent_node_id' => 314,
            'path_string' => '',
            'path_identification_string' => 'path314/path315',
            'remote_id' => '',
        ],
        4 => [
            'node_id' => 316,
            'parent_node_id' => 315,
            'path_string' => '',
            'path_identification_string' => 'path314/path315/path316',
            'remote_id' => '',
        ],
    ],
];

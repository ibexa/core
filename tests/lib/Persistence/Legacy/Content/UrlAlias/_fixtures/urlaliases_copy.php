<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway as LanguageGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway;

return [
    Gateway::TABLE => [
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
            'action' => 'eznode:3',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '2',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '2',
            'parent' => '0',
            'text' => 'move-here',
            'text_md5' => '8c09d75fa9c06724b51b2f837107a5ca',
        ],
        2 => [
            'action' => 'eznode:4',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '3',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '3',
            'parent' => '0',
            'text' => 'move-this',
            'text_md5' => '93dc83851ede7c440fe00c29e7487d1b',
        ],
        4 => [
            'action' => 'eznode:4',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '4',
            'is_alias' => '0',
            'is_original' => '0',
            'lang_mask' => '4',
            'link' => '3',
            'parent' => '0',
            'text' => 'move-this-history',
            'text_md5' => '869f933f715cc635b70923256fa04033',
        ],
        5 => [
            'action' => 'eznode:5',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '5',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '5',
            'parent' => '3',
            'text' => 'sub1',
            'text_md5' => '1b52eb8ef2c1875cfdf3ffbe9e3c05da',
        ],
        6 => [
            'action' => 'eznode:6',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '7',
            'is_alias' => '0',
            'is_original' => '0',
            'lang_mask' => '4',
            'link' => '6',
            'parent' => '5',
            'text' => 'sub2-history',
            'text_md5' => 'be302a8ff37091d2b3bc31f2b8f95207',
        ],
        7 => [
            'action' => 'eznode:6',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '8',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '8',
            'parent' => '5',
            'text' => 'sub2',
            'text_md5' => '5fbef65269a99bddc2106251dd89b1dc',
        ],
        8 => [
            'action' => 'eznode:400',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '9',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '9',
            'parent' => '2',
            'text' => 'move-this',
            'text_md5' => '93dc83851ede7c440fe00c29e7487d1b',
        ],
    ],
    Gateway::INCR_TABLE => [
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
        6 => [
            'id' => '7',
        ],
        7 => [
            'id' => '8',
        ],
        8 => [
            'id' => '9',
        ],
    ],
    LanguageGateway::CONTENT_LANGUAGE_TABLE => [
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
    LocationGateway::CONTENT_TREE_TABLE => [
        0 => [
            'node_id' => 1,
            'parent_node_id' => 1,
            'path_string' => '/1/',
            'remote_id' => '',
        ],
        1 => [
            'node_id' => 2,
            'parent_node_id' => 1,
            'path_string' => '/1/2/',
            'remote_id' => '',
        ],
        2 => [
            'node_id' => 3,
            'parent_node_id' => 2,
            'path_string' => '/1/2/3/',
            'remote_id' => '',
        ],
        3 => [
            'node_id' => 4,
            'parent_node_id' => 2,
            'path_string' => '/1/2/4/',
            'remote_id' => '',
        ],
        4 => [
            'node_id' => 5,
            'parent_node_id' => 4,
            'path_string' => '/1/2/4/5/',
            'remote_id' => '',
        ],
        5 => [
            'node_id' => 6,
            'parent_node_id' => 5,
            'path_string' => '/1/2/4/5/6/',
            'remote_id' => '',
        ],
        6 => [
            'node_id' => 400,
            'parent_node_id' => 3,
            'path_string' => '/1/2/3/400/',
            'remote_id' => '',
        ],
        7 => [
            'node_id' => 500,
            'parent_node_id' => 400,
            'path_string' => '/1/2/3/400/500/',
            'remote_id' => '',
        ],
        8 => [
            'node_id' => 600,
            'parent_node_id' => 500,
            'path_string' => '/1/2/3/400/500/600/',
            'remote_id' => '',
        ],
    ],
];

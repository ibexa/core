<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
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
            'action' => 'eznode:314',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '2',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '2',
            'link' => '2',
            'parent' => '0',
            'text' => 'swap-hr',
            'text_md5' => 'b0a33436ea51b6cc92f20b7d5be52cf6',
        ],
        2 => [
            'action' => 'eznode:314',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '2',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '2',
            'parent' => '0',
            'text' => 'swap-en2',
            'text_md5' => '75d19c821c6535b5e038219f07dbb03b',
        ],
        3 => [
            'action' => 'eznode:315',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '3',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '2',
            'link' => '3',
            'parent' => '0',
            'text' => 'swap-hr2',
            'text_md5' => '176417c6cd9900cd485342858b8e3efa',
        ],
        4 => [
            'action' => 'eznode:315',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '3',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '3',
            'parent' => '0',
            'text' => 'swap-en',
            'text_md5' => '5a1cafd1fc29c227c11c751d79b0c155',
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
    ],
    LocationGateway::CONTENT_TREE_TABLE => [
        0 => [
            'node_id' => 2,
            'main_node_id' => 2,
            'parent_node_id' => 1,
            'path_string' => '',
            'path_identification_string' => '',
            'remote_id' => '',
            'contentobject_id' => 1,
        ],
        1 => [
            'node_id' => 314,
            'main_node_id' => 314,
            'parent_node_id' => 2,
            'path_string' => '',
            'path_identification_string' => '',
            'remote_id' => '',
            'contentobject_id' => 2,
        ],
        2 => [
            'node_id' => 315,
            'main_node_id' => 315,
            'parent_node_id' => 2,
            'path_string' => '',
            'path_identification_string' => '',
            'remote_id' => '',
            'contentobject_id' => 3,
        ],
    ],
    ContentGateway::CONTENT_ITEM_TABLE => [
        0 => [
            'id' => 2,
            'initial_language_id' => 2,
            'current_version' => 1,
        ],
        1 => [
            'id' => 3,
            'initial_language_id' => 2,
            'current_version' => 1,
        ],
    ],
    ContentGateway::CONTENT_NAME_TABLE => [
        0 => [
            'contentobject_id' => 2,
            'content_version' => 1,
            'name' => 'swap hr',
            'content_translation' => 'cro-HR',
        ],
        1 => [
            'contentobject_id' => 2,
            'content_version' => 1,
            'name' => 'swap en',
            'content_translation' => 'eng-GB',
        ],
        2 => [
            'contentobject_id' => 3,
            'content_version' => 1,
            'name' => 'swap hr',
            'content_translation' => 'cro-HR',
        ],
        3 => [
            'contentobject_id' => 3,
            'content_version' => 1,
            'name' => 'swap en',
            'content_translation' => 'eng-GB',
        ],
    ],
];

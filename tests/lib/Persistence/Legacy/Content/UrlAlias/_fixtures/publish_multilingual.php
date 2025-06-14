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
        [
            'action' => 'eznode:2',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '1',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '7',
            'link' => '1',
            'parent' => '0',
            'text' => 'Content1',
            'text_md5' => '7e55db001d319a94b0b713529a756623',
        ],
        [
            'action' => 'eznode:3',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '2',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '2',
            'link' => '1',
            'parent' => '0',
            'text' => 'Content2EN',
            'text_md5' => '59cebf370b48bf85ebf73a8370c177b4',
        ],
        [
            'action' => 'eznode:3',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '3',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '1',
            'parent' => '0',
            'text' => 'Content2PL',
            'text_md5' => '86a2d16f3631635b29bcf0adc1546a9d',
        ],
        [
            'action' => 'eznode:3',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '3',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '8',
            'link' => '1',
            'parent' => '0',
            'text' => 'Content2NO',
            'text_md5' => 'f67a51e1a7085ae8b69d08f0697d9092',
        ],
    ],
    Gateway::INCR_TABLE => [
        ['id' => '1'],
        ['id' => '2'],
        ['id' => '3'],
        ['id' => '4'],
    ],
    LanguageGateway::CONTENT_LANGUAGE_TABLE => [
        [
            'disabled' => 0,
            'id' => 2,
            'locale' => 'eng-GB',
            'name' => 'English (United Kingdom)',
        ],
        [
            'disabled' => 0,
            'id' => 4,
            'locale' => 'pol-PL',
            'name' => 'Polish',
        ],
        [
            'disabled' => 0,
            'id' => 8,
            'locale' => 'nor-NO',
            'name' => 'Norwegian',
        ],
    ],
    LocationGateway::CONTENT_TREE_TABLE => [
        [
            'node_id' => 1,
            'parent_node_id' => 1,
            'path_string' => '',
            'path_identification_string' => '',
            'remote_id' => '',
        ],
        [
            'node_id' => 2,
            'parent_node_id' => 1,
            'path_string' => '',
            'path_identification_string' => '',
            'remote_id' => '',
        ],
        [
            'node_id' => 3,
            'parent_node_id' => 1,
            'path_string' => '',
            'path_identification_string' => '',
            'remote_id' => '',
        ],
    ],
];

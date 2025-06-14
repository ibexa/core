<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway as LanguageGateway;
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
            'lang_mask' => '3',
            'link' => '2',
            'parent' => '0',
            'text' => 'jedan',
            'text_md5' => '6896260129051a949051c3847c34466f',
        ],
        3 => [
            'action' => 'eznode:315',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '3',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '6',
            'link' => '3',
            'parent' => '2',
            'text' => 'dva',
            'text_md5' => 'c67ed9a09ab136fae610b6a087d82e21',
        ],
        4 => [
            'action' => 'eznode:316',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '4',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '4',
            'parent' => '3',
            'text' => 'three',
            'text_md5' => '35d6d33467aae9a2e3dccb4b6b027878',
        ],
        5 => [
            'action' => 'eznode:316',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '4',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '2',
            'link' => '4',
            'parent' => '3',
            'text' => 'tri',
            'text_md5' => 'd2cfe69af2d64330670e08efb2c86df7',
        ],
        6 => [
            'action' => 'eznode:317',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '5',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '15',
            'link' => '4',
            'parent' => '3',
            'text' => 'multi-composite-always-available',
            'text_md5' => '33e6762b930e3a428a9f0e94907a8eaa',
        ],
        7 => [
            'action' => 'eznode:318',
            'action_type' => 'eznode',
            'alias_redirects' => '0',
            'id' => '6',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '14',
            'link' => '4',
            'parent' => '3',
            'text' => 'multi-composite',
            'text_md5' => 'bc3644bfb4f44ebbd4dd2de0de2ce6c2',
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
        2 => [
            'disabled' => 0,
            'id' => 8,
            'locale' => 'pol-PL',
            'name' => 'Polish (polski)',
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
    ],
];

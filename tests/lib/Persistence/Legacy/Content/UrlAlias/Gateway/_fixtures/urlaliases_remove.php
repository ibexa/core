<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
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
            'id' => '3',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '2',
            'link' => '3',
            'parent' => '0',
            'text' => 'location',
            'text_md5' => 'd5189de027922f81005951e6efe0efd5',
        ],
        2 => [
            'action' => 'eznode:314',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '4',
            'is_alias' => '0',
            'is_original' => '0',
            'lang_mask' => '2',
            'link' => '3',
            'parent' => '0',
            'text' => 'location-history',
            'text_md5' => 'a59d9f07e3d5fcf77911155650956a73',
        ],
        3 => [
            'action' => 'eznode:314',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '5',
            'is_alias' => '1',
            'is_original' => '1',
            'lang_mask' => '2',
            'link' => '5',
            'parent' => '0',
            'text' => 'location-custom',
            'text_md5' => '6449cba11bb134a57af94c8cb7f6c99c',
        ],
        4 => [
            'action' => 'eznode:315',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '6',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '2',
            'link' => '6',
            'parent' => '0',
            'text' => 'location2',
            'text_md5' => '0a06c09b6dd9a4606b4eb6d60ab188f0',
        ],
        5 => [
            'action' => 'eznode:315',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '6',
            'is_alias' => '0',
            'is_original' => '1',
            'lang_mask' => '4',
            'link' => '6',
            'parent' => '0',
            'text' => 'location2-translation',
            'text_md5' => '82f2bce3283a0806a398fe78beda17d9',
        ],
        6 => [
            'action' => 'eznode:315',
            'action_type' => 'eznode',
            'alias_redirects' => '1',
            'id' => '7',
            'is_alias' => '1',
            'is_original' => '1',
            'lang_mask' => '2',
            'link' => '7',
            'parent' => '0',
            'text' => 'location2-custom',
            'text_md5' => '863d659d9fec68e5ab117b5f585a4ee7',
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
    ],
];

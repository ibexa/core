<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Core\Persistence\Legacy\Content\Section\Gateway;

return [
    Gateway::CONTENT_SECTION_TABLE => [
        [
            'id' => 1,
            'identifier' => 'standard',
            'locale' => '',
            'name' => 'Standard',
            'navigation_part_identifier' => 'ezcontentnavigationpart',
        ],

        [
            'id' => 2,
            'identifier' => 'users',
            'locale' => '',
            'name' => 'Users',
            'navigation_part_identifier' => 'ezusernavigationpart',
        ],

        [
            'id' => 3,
            'identifier' => 'media',
            'locale' => '',
            'name' => 'Media',
            'navigation_part_identifier' => 'ezmedianavigationpart',
        ],

        [
            'id' => 4,
            'identifier' => 'setup',
            'locale' => '',
            'name' => 'Setup',
            'navigation_part_identifier' => 'ezsetupnavigationpart',
        ],

        [
            'id' => 5,
            'identifier' => 'design',
            'locale' => '',
            'name' => 'Design',
            'navigation_part_identifier' => 'ezvisualnavigationpart',
        ],

        [
            'id' => 6,
            'identifier' => '',
            'locale' => '',
            'name' => 'Restricted',
            'navigation_part_identifier' => 'ezcontentnavigationpart',
        ],
    ],
];

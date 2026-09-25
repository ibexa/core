<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Ibexa\Contracts\Core\Repository\Values\Content\Language;

return [
    [
        2 => new Language(
            [
                'id' => 2,
                'name' => 'English (United Kingdom)',
                'enabled' => true,
                'languageCode' => 'eng-GB',
            ]
        ),
        4 => new Language(
            [
                'id' => 4,
                'name' => 'German',
                'enabled' => true,
                'languageCode' => 'ger-DE',
            ]
        ),
        8 => new Language(
            [
                'id' => 8,
                'name' => 'English (American)',
                'enabled' => true,
                'languageCode' => 'eng-US',
            ]
        ),
    ],
    [
        'eng-GB' => 2,
        'ger-DE' => 4,
        'eng-US' => 8,
    ],
    8,
];

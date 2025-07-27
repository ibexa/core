<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
return [
    'endline_search_normalize' => [
        0 => [
            'type' => 11,
            'data' => [
                'src' => 'U+000A',
                'dest' => 'U+0020',
            ],
        ],
        1 => [
            'type' => 11,
            'data' => [
                'src' => 'U+000D',
                'dest' => 'U+0020',
            ],
        ],
    ],
    'tab_search_normalize' => [
        0 => [
            'type' => 11,
            'data' => [
                'src' => 'U+0008',
                'dest' => 'U+0020',
            ],
        ],
    ],
    'specialwords_search_normalize' => [
        0 => [
            'type' => 11,
            'data' => [
                'src' => 'U+00AA',
                'dest' => '"a"',
            ],
        ],
        1 => [
            'type' => 11,
            'data' => [
                'src' => 'U+00BA',
                'dest' => '"o"',
            ],
        ],
    ],
    'punctuation_normalize' => [
        0 => [
            'type' => 11,
            'data' => [
                'src' => 'U+0021',
                'dest' => 'U+002E',
            ],
        ],
        1 => [
            'type' => 11,
            'data' => [
                'src' => 'U+003F',
                'dest' => 'U+002E',
            ],
        ],
        2 => [
            'type' => 11,
            'data' => [
                'src' => 'U+002C',
                'dest' => 'U+002E',
            ],
        ],
    ],
    'latin_search_decompose' => [
        0 => [
            'type' => 11,
            'data' => [
                'src' => 'U+00B1',
                'dest' => '"+-"',
            ],
        ],
        1 => [
            'type' => 11,
            'data' => [
                'src' => 'U+00B2',
                'dest' => '"2"',
            ],
        ],
        2 => [
            'type' => 11,
            'data' => [
                'src' => 'U+00B3',
                'dest' => '"3"',
            ],
        ],
        3 => [
            'type' => 11,
            'data' => [
                'src' => 'U+00B9',
                'dest' => '"1"',
            ],
        ],
        4 => [
            'type' => 11,
            'data' => [
                'src' => 'U+00BA',
                'dest' => '"1"',
            ],
        ],
        5 => [
            'type' => 11,
            'data' => [
                'src' => 'U+00BC',
                'dest' => '"1/4"',
            ],
        ],
        6 => [
            'type' => 11,
            'data' => [
                'src' => 'U+00BD',
                'dest' => '"1/2"',
            ],
        ],
        7 => [
            'type' => 11,
            'data' => [
                'src' => 'U+00BE',
                'dest' => '"3/4"',
            ],
        ],
    ],
];

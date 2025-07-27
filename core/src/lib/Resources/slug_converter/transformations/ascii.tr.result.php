<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
return [
    'ascii_lowercase' => [
        0 => [
            'type' => 13,
            'data' => [
                'srcStart' => 'U+0041',
                'srcEnd' => 'U+005A',
                'op' => '+',
                'dest' => '20',
            ],
        ],
    ],
    'ascii_uppercase' => [
        0 => [
            'type' => 13,
            'data' => [
                'srcStart' => 'U+0061',
                'srcEnd' => 'U+007A',
                'op' => '-',
                'dest' => '20',
            ],
        ],
    ],
    'ascii_search_cleanup' => [
        0 => [
            'type' => 11,
            'data' => [
                'src' => 'U+0023',
                'dest' => 'U+0020',
            ],
        ],
        1 => [
            'type' => 11,
            'data' => [
                'src' => 'U+0024',
                'dest' => 'U+0020',
            ],
        ],
        2 => [
            'type' => 11,
            'data' => [
                'src' => 'U+0026',
                'dest' => 'U+0020',
            ],
        ],
        3 => [
            'type' => 11,
            'data' => [
                'src' => 'U+005E',
                'dest' => 'U+0020',
            ],
        ],
        4 => [
            'type' => 11,
            'data' => [
                'src' => 'U+007B',
                'dest' => 'U+0020',
            ],
        ],
        5 => [
            'type' => 11,
            'data' => [
                'src' => 'U+007D',
                'dest' => 'U+0020',
            ],
        ],
        6 => [
            'type' => 11,
            'data' => [
                'src' => 'U+007C',
                'dest' => 'U+0020',
            ],
        ],
        7 => [
            'type' => 11,
            'data' => [
                'src' => 'U+0040',
                'dest' => 'U+0020',
            ],
        ],
        8 => [
            'type' => 11,
            'data' => [
                'src' => 'U+003A',
                'dest' => 'U+0020',
            ],
        ],
        9 => [
            'type' => 11,
            'data' => [
                'src' => 'U+003B',
                'dest' => 'U+0020',
            ],
        ],
        10 => [
            'type' => 11,
            'data' => [
                'src' => 'U+002C',
                'dest' => 'U+0020',
            ],
        ],
        11 => [
            'type' => 11,
            'data' => [
                'src' => 'U+0028',
                'dest' => 'U+0020',
            ],
        ],
        12 => [
            'type' => 11,
            'data' => [
                'src' => 'U+0029',
                'dest' => 'U+0020',
            ],
        ],
        13 => [
            'type' => 11,
            'data' => [
                'src' => 'U+002D',
                'dest' => 'U+0020',
            ],
        ],
        14 => [
            'type' => 11,
            'data' => [
                'src' => 'U+002B',
                'dest' => 'U+0020',
            ],
        ],
        15 => [
            'type' => 11,
            'data' => [
                'src' => 'U+002F',
                'dest' => 'U+0020',
            ],
        ],
        16 => [
            'type' => 11,
            'data' => [
                'src' => 'U+005B',
                'dest' => 'U+0020',
            ],
        ],
        17 => [
            'type' => 11,
            'data' => [
                'src' => 'U+005D',
                'dest' => 'U+0020',
            ],
        ],
        18 => [
            'type' => 11,
            'data' => [
                'src' => 'U+005C',
                'dest' => 'U+0020',
            ],
        ],
        19 => [
            'type' => 11,
            'data' => [
                'src' => 'U+003C',
                'dest' => 'U+0020',
            ],
        ],
        20 => [
            'type' => 11,
            'data' => [
                'src' => 'U+003E',
                'dest' => 'U+0020',
            ],
        ],
        21 => [
            'type' => 11,
            'data' => [
                'src' => 'U+003D',
                'dest' => 'U+0020',
            ],
        ],
        22 => [
            'type' => 11,
            'data' => [
                'src' => 'U+0027',
                'dest' => 'U+0020',
            ],
        ],
        23 => [
            'type' => 11,
            'data' => [
                'src' => 'U+0060',
                'dest' => 'U+0020',
            ],
        ],
        24 => [
            'type' => 12,
            'data' => [
                'srcStart' => 'U+0000',
                'srcEnd' => 'U+0009',
                'dest' => 'U+0020',
            ],
        ],
        25 => [
            'type' => 12,
            'data' => [
                'srcStart' => 'U+000B',
                'srcEnd' => 'U+000C',
                'dest' => 'U+0020',
            ],
        ],
        26 => [
            'type' => 12,
            'data' => [
                'srcStart' => 'U+000E',
                'srcEnd' => 'U+001F',
                'dest' => 'U+0020',
            ],
        ],
    ],
];

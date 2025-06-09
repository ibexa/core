<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
return  [
  'space_normalize' => [
    0 => [
      'type' => 11,
      'data' => [
        'src' => 'U+202F',
        'dest' => 'U+00A0',
      ],
    ],
    1 => [
      'type' => 11,
      'data' => [
        'src' => 'U+FEFF',
        'dest' => 'U+00A0',
      ],
    ],
    2 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00A0',
        'dest' => 'U+0020',
      ],
    ],
  ],
  'hyphen_normalize' => [
    0 => [
      'type' => 11,
      'data' => [
        'src' => 'U+007E',
        'dest' => 'U+002D',
      ],
    ],
    1 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00AD',
        'dest' => 'remove',
      ],
    ],
  ],
  'apostrophe_normalize' => [
    0 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0060',
        'dest' => 'U+0027',
      ],
    ],
    1 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00B4',
        'dest' => 'U+0027',
      ],
    ],
    2 => [
      'type' => 11,
      'data' => [
        'src' => 'U+02CA',
        'dest' => 'U+0027',
      ],
    ],
    3 => [
      'type' => 11,
      'data' => [
        'src' => 'U+02CB',
        'dest' => 'U+0027',
      ],
    ],
    4 => [
      'type' => 11,
      'data' => [
        'src' => 'U+02CF',
        'dest' => 'U+0027',
      ],
    ],
    5 => [
      'type' => 11,
      'data' => [
        'src' => 'U+02CE',
        'dest' => 'U+0027',
      ],
    ],
  ],
  'doublequote_normalize' => [
    0 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00AB',
        'dest' => 'U+0022',
      ],
    ],
    1 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00BB',
        'dest' => 'U+0022',
      ],
    ],
    2 => [
      'type' => 11,
      'data' => [
        'src' => 'U+02DD',
        'dest' => 'U+0027',
      ],
    ],
  ],
  'apostrophe_to_doublequote' => [
    0 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0027',
        'dest' => 'U+0022',
      ],
    ],
  ],
  'special_decompose' => [
    0 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00A9',
        'dest' => '"(C)"',
      ],
    ],
    1 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00AE',
        'dest' => '"(R)"',
      ],
    ],
    2 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00B1',
        'dest' => '"+-"',
      ],
    ],
    3 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00B2',
        'dest' => '"2"',
      ],
    ],
    4 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00B3',
        'dest' => '"3"',
      ],
    ],
    5 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00B9',
        'dest' => '"1"',
      ],
    ],
    6 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00BA',
        'dest' => '"1"',
      ],
    ],
    7 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00BC',
        'dest' => '"1/4"',
      ],
    ],
    8 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00BD',
        'dest' => '"1/2"',
      ],
    ],
    9 => [
      'type' => 11,
      'data' => [
        'src' => 'U+00BE',
        'dest' => '"3/4"',
      ],
    ],
  ],
];

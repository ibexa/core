<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
return  [
  'cyrillic_uppercase' => [
    0 => [
      'type' => 13,
      'data' => [
        'srcStart' => 'U+0450',
        'srcEnd' => 'U+045F',
        'op' => '-',
        'dest' => 'U+0050',
      ],
    ],
    1 => [
      'type' => 13,
      'data' => [
        'srcStart' => 'U+0430',
        'srcEnd' => 'U+044F',
        'op' => '-',
        'dest' => 'U+0020',
      ],
    ],
    2 => [
      'type' => 14,
      'data' => [
        'srcStart' => 'U+0461',
        'srcEnd' => 'U+0481',
        'modulo' => '02',
        'op' => '-',
        'dest' => 'U+0001',
      ],
    ],
    3 => [
      'type' => 14,
      'data' => [
        'srcStart' => 'U+048B',
        'srcEnd' => 'U+04BF',
        'modulo' => '02',
        'op' => '-',
        'dest' => 'U+0001',
      ],
    ],
    4 => [
      'type' => 14,
      'data' => [
        'srcStart' => 'U+04C2',
        'srcEnd' => 'U+04CC',
        'modulo' => '02',
        'op' => '-',
        'dest' => 'U+0001',
      ],
    ],
    5 => [
      'type' => 14,
      'data' => [
        'srcStart' => 'U+04D1',
        'srcEnd' => 'U+04F5',
        'modulo' => '02',
        'op' => '-',
        'dest' => 'U+0001',
      ],
    ],
    6 => [
      'type' => 14,
      'data' => [
        'srcStart' => 'U+0501',
        'srcEnd' => 'U+050F',
        'modulo' => '02',
        'op' => '-',
        'dest' => 'U+0001',
      ],
    ],
  ],
  'cyrillic_lowercase' => [
    0 => [
      'type' => 13,
      'data' => [
        'srcStart' => 'U+0400',
        'srcEnd' => 'U+040F',
        'op' => '+',
        'dest' => 'U+0050',
      ],
    ],
    1 => [
      'type' => 13,
      'data' => [
        'srcStart' => 'U+0410',
        'srcEnd' => 'U+042F',
        'op' => '+',
        'dest' => 'U+0020',
      ],
    ],
    2 => [
      'type' => 14,
      'data' => [
        'srcStart' => 'U+0460',
        'srcEnd' => 'U+0480',
        'modulo' => '02',
        'op' => '+',
        'dest' => 'U+0001',
      ],
    ],
    3 => [
      'type' => 14,
      'data' => [
        'srcStart' => 'U+048A',
        'srcEnd' => 'U+04BE',
        'modulo' => '02',
        'op' => '+',
        'dest' => 'U+0001',
      ],
    ],
    4 => [
      'type' => 14,
      'data' => [
        'srcStart' => 'U+04C1',
        'srcEnd' => 'U+04CD',
        'modulo' => '02',
        'op' => '+',
        'dest' => 'U+0001',
      ],
    ],
    5 => [
      'type' => 14,
      'data' => [
        'srcStart' => 'U+04D0',
        'srcEnd' => 'U+04F4',
        'modulo' => '02',
        'op' => '+',
        'dest' => 'U+0001',
      ],
    ],
    6 => [
      'type' => 14,
      'data' => [
        'srcStart' => 'U+0500',
        'srcEnd' => 'U+050E',
        'modulo' => '02',
        'op' => '+',
        'dest' => 'U+0001',
      ],
    ],
  ],
  'cyrillic_diacritical' => [
    0 => [
      'type' => 12,
      'data' => [
        'srcStart' => 'U+0400',
        'srcEnd' => 'U+0401',
        'dest' => 'U+0415',
      ],
    ],
    1 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0403',
        'dest' => 'U+0413',
      ],
    ],
    2 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0407',
        'dest' => 'U+0406',
      ],
    ],
    3 => [
      'type' => 11,
      'data' => [
        'src' => 'U+040C',
        'dest' => 'U+041A',
      ],
    ],
    4 => [
      'type' => 11,
      'data' => [
        'src' => 'U+040D',
        'dest' => 'U+0418',
      ],
    ],
    5 => [
      'type' => 11,
      'data' => [
        'src' => 'U+040E',
        'dest' => 'U+0423',
      ],
    ],
    6 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0419',
        'dest' => 'U+0418',
      ],
    ],
    7 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0439',
        'dest' => 'U+0438',
      ],
    ],
    8 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0450',
        'dest' => 'U+0435',
      ],
    ],
    9 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0451',
        'dest' => 'U+0435',
      ],
    ],
    10 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0453',
        'dest' => 'U+0433',
      ],
    ],
    11 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0457',
        'dest' => 'U+0456',
      ],
    ],
    12 => [
      'type' => 11,
      'data' => [
        'src' => 'U+045C',
        'dest' => 'U+043A',
      ],
    ],
    13 => [
      'type' => 11,
      'data' => [
        'src' => 'U+045D',
        'dest' => 'U+0438',
      ],
    ],
    14 => [
      'type' => 11,
      'data' => [
        'src' => 'U+045E',
        'dest' => 'U+0443',
      ],
    ],
    15 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0476',
        'dest' => 'U+0474',
      ],
    ],
    16 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0477',
        'dest' => 'U+0475',
      ],
    ],
    17 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04C1',
        'dest' => 'U+0416',
      ],
    ],
    18 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04C2',
        'dest' => 'U+0436',
      ],
    ],
    19 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04D0',
        'dest' => 'U+0410',
      ],
    ],
    20 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04D1',
        'dest' => 'U+0430',
      ],
    ],
    21 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04D2',
        'dest' => 'U+0410',
      ],
    ],
    22 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04D3',
        'dest' => 'U+0430',
      ],
    ],
    23 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04D6',
        'dest' => 'U+0415',
      ],
    ],
    24 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04D7',
        'dest' => 'U+0435',
      ],
    ],
    25 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04DA',
        'dest' => 'U+04D8',
      ],
    ],
    26 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04DB',
        'dest' => 'U+04D9',
      ],
    ],
    27 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04DC',
        'dest' => 'U+0416',
      ],
    ],
    28 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04DD',
        'dest' => 'U+0436',
      ],
    ],
    29 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04DE',
        'dest' => 'U+0417',
      ],
    ],
    30 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04DF',
        'dest' => 'U+0437',
      ],
    ],
    31 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04E2',
        'dest' => 'U+0418',
      ],
    ],
    32 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04E3',
        'dest' => 'U+0438',
      ],
    ],
    33 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04E4',
        'dest' => 'U+0418',
      ],
    ],
    34 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04E5',
        'dest' => 'U+0438',
      ],
    ],
    35 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04E6',
        'dest' => 'U+041E',
      ],
    ],
    36 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04E7',
        'dest' => 'U+043E',
      ],
    ],
    37 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04EA',
        'dest' => 'U+04E8',
      ],
    ],
    38 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04EB',
        'dest' => 'U+04E9',
      ],
    ],
    39 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04EC',
        'dest' => 'U+042D',
      ],
    ],
    40 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04ED',
        'dest' => 'U+044D',
      ],
    ],
    41 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04EE',
        'dest' => 'U+0423',
      ],
    ],
    42 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04EF',
        'dest' => 'U+0443',
      ],
    ],
    43 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04F0',
        'dest' => 'U+0423',
      ],
    ],
    44 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04F1',
        'dest' => 'U+0443',
      ],
    ],
    45 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04F2',
        'dest' => 'U+0423',
      ],
    ],
    46 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04F3',
        'dest' => 'U+0443',
      ],
    ],
    47 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04F4',
        'dest' => 'U+0427',
      ],
    ],
    48 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04F5',
        'dest' => 'U+0447',
      ],
    ],
    49 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04F8',
        'dest' => 'U+042B',
      ],
    ],
    50 => [
      'type' => 11,
      'data' => [
        'src' => 'U+04F9',
        'dest' => 'U+044B',
      ],
    ],
  ],
  'cyrillic_transliterate_ascii' => [
    0 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0400',
        'dest' => '"IE"',
      ],
    ],
    1 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0401',
        'dest' => '"IO"',
      ],
    ],
    2 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0402',
        'dest' => '"D"',
      ],
    ],
    3 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0403',
        'dest' => '"G"',
      ],
    ],
    4 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0404',
        'dest' => '"IE"',
      ],
    ],
    5 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0405',
        'dest' => '"DS"',
      ],
    ],
    6 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0406',
        'dest' => '"II"',
      ],
    ],
    7 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0407',
        'dest' => '"YI"',
      ],
    ],
    8 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0408',
        'dest' => '"J"',
      ],
    ],
    9 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0409',
        'dest' => '"LJ"',
      ],
    ],
    10 => [
      'type' => 11,
      'data' => [
        'src' => 'U+040A',
        'dest' => '"NJ"',
      ],
    ],
    11 => [
      'type' => 11,
      'data' => [
        'src' => 'U+040B',
        'dest' => '"Ts"',
      ],
    ],
    12 => [
      'type' => 11,
      'data' => [
        'src' => 'U+040C',
        'dest' => '"KJ"',
      ],
    ],
    13 => [
      'type' => 11,
      'data' => [
        'src' => 'U+040D',
        'dest' => '"I"',
      ],
    ],
    14 => [
      'type' => 11,
      'data' => [
        'src' => 'U+040E',
        'dest' => '"V"',
      ],
    ],
    15 => [
      'type' => 11,
      'data' => [
        'src' => 'U+040F',
        'dest' => '"DZ"',
      ],
    ],
    16 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0410',
        'dest' => '"A"',
      ],
    ],
    17 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0411',
        'dest' => '"B"',
      ],
    ],
    18 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0412',
        'dest' => '"V"',
      ],
    ],
    19 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0413',
        'dest' => '"G"',
      ],
    ],
    20 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0414',
        'dest' => '"D"',
      ],
    ],
    21 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0415',
        'dest' => '"E"',
      ],
    ],
    22 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0416',
        'dest' => '"ZH"',
      ],
    ],
    23 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0417',
        'dest' => '"Z"',
      ],
    ],
    24 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0418',
        'dest' => '"I"',
      ],
    ],
    25 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0419',
        'dest' => '"J"',
      ],
    ],
    26 => [
      'type' => 11,
      'data' => [
        'src' => 'U+041A',
        'dest' => '"K"',
      ],
    ],
    27 => [
      'type' => 11,
      'data' => [
        'src' => 'U+041B',
        'dest' => '"L"',
      ],
    ],
    28 => [
      'type' => 11,
      'data' => [
        'src' => 'U+041C',
        'dest' => '"M"',
      ],
    ],
    29 => [
      'type' => 11,
      'data' => [
        'src' => 'U+041D',
        'dest' => '"N"',
      ],
    ],
    30 => [
      'type' => 11,
      'data' => [
        'src' => 'U+041E',
        'dest' => '"O"',
      ],
    ],
    31 => [
      'type' => 11,
      'data' => [
        'src' => 'U+041F',
        'dest' => '"P"',
      ],
    ],
    32 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0420',
        'dest' => '"R"',
      ],
    ],
    33 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0421',
        'dest' => '"S"',
      ],
    ],
    34 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0422',
        'dest' => '"T"',
      ],
    ],
    35 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0423',
        'dest' => '"U"',
      ],
    ],
    36 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0424',
        'dest' => '"F"',
      ],
    ],
    37 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0425',
        'dest' => '"H"',
      ],
    ],
    38 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0426',
        'dest' => '"C"',
      ],
    ],
    39 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0427',
        'dest' => '"CH"',
      ],
    ],
    40 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0428',
        'dest' => '"SH"',
      ],
    ],
    41 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0429',
        'dest' => '"SCH"',
      ],
    ],
    42 => [
      'type' => 11,
      'data' => [
        'src' => 'U+042A',
        'dest' => '"\\\'"',
      ],
    ],
    43 => [
      'type' => 11,
      'data' => [
        'src' => 'U+042B',
        'dest' => '"Y"',
      ],
    ],
    44 => [
      'type' => 11,
      'data' => [
        'src' => 'U+042C',
        'dest' => '"\\\'"',
      ],
    ],
    45 => [
      'type' => 11,
      'data' => [
        'src' => 'U+042D',
        'dest' => '"E"',
      ],
    ],
    46 => [
      'type' => 11,
      'data' => [
        'src' => 'U+042E',
        'dest' => '"YU"',
      ],
    ],
    47 => [
      'type' => 11,
      'data' => [
        'src' => 'U+042F',
        'dest' => '"YA"',
      ],
    ],
    48 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0430',
        'dest' => '"a"',
      ],
    ],
    49 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0431',
        'dest' => '"b"',
      ],
    ],
    50 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0432',
        'dest' => '"v"',
      ],
    ],
    51 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0433',
        'dest' => '"g"',
      ],
    ],
    52 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0434',
        'dest' => '"d"',
      ],
    ],
    53 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0435',
        'dest' => '"e"',
      ],
    ],
    54 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0436',
        'dest' => '"zh"',
      ],
    ],
    55 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0437',
        'dest' => '"z"',
      ],
    ],
    56 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0438',
        'dest' => '"i"',
      ],
    ],
    57 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0439',
        'dest' => '"j"',
      ],
    ],
    58 => [
      'type' => 11,
      'data' => [
        'src' => 'U+043A',
        'dest' => '"k"',
      ],
    ],
    59 => [
      'type' => 11,
      'data' => [
        'src' => 'U+043B',
        'dest' => '"l"',
      ],
    ],
    60 => [
      'type' => 11,
      'data' => [
        'src' => 'U+043C',
        'dest' => '"m"',
      ],
    ],
    61 => [
      'type' => 11,
      'data' => [
        'src' => 'U+043D',
        'dest' => '"n"',
      ],
    ],
    62 => [
      'type' => 11,
      'data' => [
        'src' => 'U+043E',
        'dest' => '"o"',
      ],
    ],
    63 => [
      'type' => 11,
      'data' => [
        'src' => 'U+043F',
        'dest' => '"p"',
      ],
    ],
    64 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0440',
        'dest' => '"r"',
      ],
    ],
    65 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0441',
        'dest' => '"s"',
      ],
    ],
    66 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0442',
        'dest' => '"t"',
      ],
    ],
    67 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0443',
        'dest' => '"u"',
      ],
    ],
    68 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0444',
        'dest' => '"f"',
      ],
    ],
    69 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0445',
        'dest' => '"h"',
      ],
    ],
    70 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0446',
        'dest' => '"c"',
      ],
    ],
    71 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0447',
        'dest' => '"ch"',
      ],
    ],
    72 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0448',
        'dest' => '"sh"',
      ],
    ],
    73 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0449',
        'dest' => '"sch"',
      ],
    ],
    74 => [
      'type' => 11,
      'data' => [
        'src' => 'U+044A',
        'dest' => '"\\\'"',
      ],
    ],
    75 => [
      'type' => 11,
      'data' => [
        'src' => 'U+044B',
        'dest' => '"y"',
      ],
    ],
    76 => [
      'type' => 11,
      'data' => [
        'src' => 'U+044C',
        'dest' => '"\\\'"',
      ],
    ],
    77 => [
      'type' => 11,
      'data' => [
        'src' => 'U+044D',
        'dest' => '"e"',
      ],
    ],
    78 => [
      'type' => 11,
      'data' => [
        'src' => 'U+044E',
        'dest' => '"yu"',
      ],
    ],
    79 => [
      'type' => 11,
      'data' => [
        'src' => 'U+044F',
        'dest' => '"ya"',
      ],
    ],
    80 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0450',
        'dest' => '"ie"',
      ],
    ],
    81 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0451',
        'dest' => '"io"',
      ],
    ],
    82 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0452',
        'dest' => '"dj"',
      ],
    ],
    83 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0453',
        'dest' => '"g"',
      ],
    ],
    84 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0454',
        'dest' => '"e"',
      ],
    ],
    85 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0455',
        'dest' => '"z"',
      ],
    ],
    86 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0456',
        'dest' => '"i"',
      ],
    ],
    87 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0457',
        'dest' => '"yi"',
      ],
    ],
    88 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0458',
        'dest' => '"j"',
      ],
    ],
    89 => [
      'type' => 11,
      'data' => [
        'src' => 'U+0459',
        'dest' => '"lj"',
      ],
    ],
    90 => [
      'type' => 11,
      'data' => [
        'src' => 'U+045A',
        'dest' => '"nj"',
      ],
    ],
    91 => [
      'type' => 11,
      'data' => [
        'src' => 'U+045B',
        'dest' => '"c"',
      ],
    ],
    92 => [
      'type' => 11,
      'data' => [
        'src' => 'U+045C',
        'dest' => '"kj"',
      ],
    ],
    93 => [
      'type' => 11,
      'data' => [
        'src' => 'U+045D',
        'dest' => '"i"',
      ],
    ],
    94 => [
      'type' => 11,
      'data' => [
        'src' => 'U+045E',
        'dest' => '"v"',
      ],
    ],
    95 => [
      'type' => 11,
      'data' => [
        'src' => 'U+045F',
        'dest' => '"dz"',
      ],
    ],
  ],
  'cyrillic_search_cleanup' => [
    0 => [
      'type' => 12,
      'data' => [
        'srcStart' => 'U+0482',
        'srcEnd' => 'U+0486',
        'dest' => 'U+0020',
      ],
    ],
    1 => [
      'type' => 12,
      'data' => [
        'srcStart' => 'U+0488',
        'srcEnd' => 'U+0489',
        'dest' => 'U+0020',
      ],
    ],
  ],
];

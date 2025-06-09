<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
return [
    'latin1_lowercase' => [
            0 => [
                    'type' => 13,
                    'data' => [
                            'srcStart' => 'U+00C0',
                            'srcEnd' => 'U+00D6',
                            'op' => '+',
                            'dest' => '20',
                        ],
                ],
            1 => [
                    'type' => 13,
                    'data' => [
                            'srcStart' => 'U+00D8',
                            'srcEnd' => 'U+00DE',
                            'op' => '+',
                            'dest' => '20',
                        ],
                ],
        ],
    'latin1_uppercase' => [
            0 => [
                    'type' => 13,
                    'data' => [
                            'srcStart' => 'U+00E0',
                            'srcEnd' => 'U+00F6',
                            'op' => '-',
                            'dest' => '20',
                        ],
                ],
            1 => [
                    'type' => 13,
                    'data' => [
                            'srcStart' => 'U+00F8',
                            'srcEnd' => 'U+00FE',
                            'op' => '-',
                            'dest' => '20',
                        ],
                ],
            2 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00DF',
                            'dest' => '"SS"',
                        ],
                ],
        ],
    'latin-exta_lowercase' => [
            0 => [
                    'type' => 14,
                    'data' => [
                            'srcStart' => 'U+0100',
                            'srcEnd' => 'U+012E',
                            'modulo' => '02',
                            'op' => '+',
                            'dest' => '01',
                        ],
                ],
            1 => [
                    'type' => 14,
                    'data' => [
                            'srcStart' => 'U+0132',
                            'srcEnd' => 'U+0136',
                            'modulo' => '02',
                            'op' => '+',
                            'dest' => '01',
                        ],
                ],
            2 => [
                    'type' => 14,
                    'data' => [
                            'srcStart' => 'U+0139',
                            'srcEnd' => 'U+0147',
                            'modulo' => '02',
                            'op' => '+',
                            'dest' => '01',
                        ],
                ],
            3 => [
                    'type' => 14,
                    'data' => [
                            'srcStart' => 'U+014A',
                            'srcEnd' => 'U+0176',
                            'modulo' => '02',
                            'op' => '+',
                            'dest' => '01',
                        ],
                ],
            4 => [
                    'type' => 14,
                    'data' => [
                            'srcStart' => 'U+0179',
                            'srcEnd' => 'U+017D',
                            'modulo' => '02',
                            'op' => '+',
                            'dest' => '01',
                        ],
                ],
        ],
    'latin-exta_uppercase' => [
            0 => [
                    'type' => 14,
                    'data' => [
                            'srcStart' => 'U+0101',
                            'srcEnd' => 'U+012F',
                            'modulo' => '02',
                            'op' => '-',
                            'dest' => '01',
                        ],
                ],
            1 => [
                    'type' => 14,
                    'data' => [
                            'srcStart' => 'U+0133',
                            'srcEnd' => 'U+0137',
                            'modulo' => '02',
                            'op' => '-',
                            'dest' => '01',
                        ],
                ],
            2 => [
                    'type' => 14,
                    'data' => [
                            'srcStart' => 'U+013A',
                            'srcEnd' => 'U+0148',
                            'modulo' => '02',
                            'op' => '-',
                            'dest' => '01',
                        ],
                ],
            3 => [
                    'type' => 14,
                    'data' => [
                            'srcStart' => 'U+014B',
                            'srcEnd' => 'U+0177',
                            'modulo' => '02',
                            'op' => '-',
                            'dest' => '01',
                        ],
                ],
            4 => [
                    'type' => 14,
                    'data' => [
                            'srcStart' => 'U+017A',
                            'srcEnd' => 'U+017E',
                            'modulo' => '02',
                            'op' => '-',
                            'dest' => '01',
                        ],
                ],
        ],
    'latin_lowercase' => [
            0 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0178',
                            'dest' => 'U+00FF',
                        ],
                ],
            1 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0218',
                            'dest' => 'U+0219',
                        ],
                ],
            2 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+021A',
                            'dest' => 'U+021B',
                        ],
                ],
        ],
    'latin_uppercase' => [
            0 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00FF',
                            'dest' => 'U+0178',
                        ],
                ],
        ],
    'latin1_diacritical' => [
            0 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00C0',
                            'srcEnd' => 'U+00C4',
                            'dest' => '"A"',
                        ],
                ],
            1 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00E0',
                            'srcEnd' => 'U+00E4',
                            'dest' => '"a"',
                        ],
                ],
            2 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00C8',
                            'srcEnd' => 'U+00CB',
                            'dest' => '"E"',
                        ],
                ],
            3 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00E8',
                            'srcEnd' => 'U+00EB',
                            'dest' => '"e"',
                        ],
                ],
            4 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00CC',
                            'srcEnd' => 'U+00CF',
                            'dest' => '"I"',
                        ],
                ],
            5 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00EC',
                            'srcEnd' => 'U+00EF',
                            'dest' => '"i"',
                        ],
                ],
            6 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00D2',
                            'srcEnd' => 'U+00D6',
                            'dest' => '"O"',
                        ],
                ],
            7 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00F2',
                            'srcEnd' => 'U+00F6',
                            'dest' => '"o"',
                        ],
                ],
            8 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00D9',
                            'srcEnd' => 'U+00DC',
                            'dest' => '"U"',
                        ],
                ],
            9 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00F9',
                            'srcEnd' => 'U+00FC',
                            'dest' => '"u"',
                        ],
                ],
            10 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00DD',
                            'dest' => '"Y"',
                        ],
                ],
            11 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+009F',
                            'dest' => '"Y"',
                        ],
                ],
            12 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00FD',
                            'dest' => '"y"',
                        ],
                ],
            13 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00FF',
                            'dest' => '"y"',
                        ],
                ],
            14 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00C7',
                            'dest' => '"C"',
                        ],
                ],
            15 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00E7',
                            'dest' => '"c"',
                        ],
                ],
            16 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00D0',
                            'dest' => '"D"',
                        ],
                ],
            17 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00F0',
                            'dest' => '"d"',
                        ],
                ],
            18 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00D1',
                            'dest' => '"N"',
                        ],
                ],
            19 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00F1',
                            'dest' => '"n"',
                        ],
                ],
            20 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00DE',
                            'dest' => '"TH"',
                        ],
                ],
            21 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00FE',
                            'dest' => '"th"',
                        ],
                ],
            22 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00DF',
                            'dest' => '"ss"',
                        ],
                ],
        ],
    'latin-exta_diacritical' => [
            0 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0100',
                            'dest' => '"A"',
                        ],
                ],
            1 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0102',
                            'dest' => '"A"',
                        ],
                ],
            2 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0104',
                            'dest' => '"A"',
                        ],
                ],
            3 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0101',
                            'dest' => '"a"',
                        ],
                ],
            4 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0103',
                            'dest' => '"a"',
                        ],
                ],
            5 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0105',
                            'dest' => '"a"',
                        ],
                ],
            6 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0106',
                            'dest' => '"C"',
                        ],
                ],
            7 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0108',
                            'dest' => '"C"',
                        ],
                ],
            8 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+010A',
                            'dest' => '"C"',
                        ],
                ],
            9 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+010C',
                            'dest' => '"C"',
                        ],
                ],
            10 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0107',
                            'dest' => '"c"',
                        ],
                ],
            11 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0109',
                            'dest' => '"c"',
                        ],
                ],
            12 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+010B',
                            'dest' => '"c"',
                        ],
                ],
            13 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+010D',
                            'dest' => '"c"',
                        ],
                ],
            14 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+010E',
                            'dest' => '"D"',
                        ],
                ],
            15 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0110',
                            'dest' => '"D"',
                        ],
                ],
            16 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+010F',
                            'dest' => '"d"',
                        ],
                ],
            17 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0111',
                            'dest' => '"d"',
                        ],
                ],
            18 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0112',
                            'dest' => '"E"',
                        ],
                ],
            19 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0114',
                            'dest' => '"E"',
                        ],
                ],
            20 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0116',
                            'dest' => '"E"',
                        ],
                ],
            21 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0118',
                            'dest' => '"E"',
                        ],
                ],
            22 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+011A',
                            'dest' => '"E"',
                        ],
                ],
            23 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0113',
                            'dest' => '"e"',
                        ],
                ],
            24 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0115',
                            'dest' => '"e"',
                        ],
                ],
            25 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0117',
                            'dest' => '"e"',
                        ],
                ],
            26 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0119',
                            'dest' => '"e"',
                        ],
                ],
            27 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+011B',
                            'dest' => '"e"',
                        ],
                ],
            28 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+011C',
                            'dest' => '"G"',
                        ],
                ],
            29 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+011E',
                            'dest' => '"G"',
                        ],
                ],
            30 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0120',
                            'dest' => '"G"',
                        ],
                ],
            31 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0122',
                            'dest' => '"G"',
                        ],
                ],
            32 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+011D',
                            'dest' => '"g"',
                        ],
                ],
            33 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+011F',
                            'dest' => '"g"',
                        ],
                ],
            34 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0121',
                            'dest' => '"g"',
                        ],
                ],
            35 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0123',
                            'dest' => '"g"',
                        ],
                ],
            36 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0124',
                            'dest' => '"H"',
                        ],
                ],
            37 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0126',
                            'dest' => '"H"',
                        ],
                ],
            38 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0125',
                            'dest' => '"h"',
                        ],
                ],
            39 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0127',
                            'dest' => '"h"',
                        ],
                ],
            40 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0128',
                            'dest' => '"I"',
                        ],
                ],
            41 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+012A',
                            'dest' => '"I"',
                        ],
                ],
            42 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+012C',
                            'dest' => '"I"',
                        ],
                ],
            43 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+012E',
                            'dest' => '"I"',
                        ],
                ],
            44 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0130',
                            'dest' => '"I"',
                        ],
                ],
            45 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0129',
                            'dest' => '"i"',
                        ],
                ],
            46 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+012B',
                            'dest' => '"i"',
                        ],
                ],
            47 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+012D',
                            'dest' => '"i"',
                        ],
                ],
            48 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+012F',
                            'dest' => '"i"',
                        ],
                ],
            49 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0131',
                            'dest' => '"i"',
                        ],
                ],
            50 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0134',
                            'dest' => '"J"',
                        ],
                ],
            51 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0135',
                            'dest' => '"j"',
                        ],
                ],
            52 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0136',
                            'dest' => '"K"',
                        ],
                ],
            53 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0137',
                            'dest' => '"k"',
                        ],
                ],
            54 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0139',
                            'dest' => '"L"',
                        ],
                ],
            55 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+013B',
                            'dest' => '"L"',
                        ],
                ],
            56 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+013D',
                            'dest' => '"L"',
                        ],
                ],
            57 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+013F',
                            'dest' => '"L"',
                        ],
                ],
            58 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0141',
                            'dest' => '"L"',
                        ],
                ],
            59 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+013A',
                            'dest' => '"l"',
                        ],
                ],
            60 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+013C',
                            'dest' => '"l"',
                        ],
                ],
            61 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+013E',
                            'dest' => '"l"',
                        ],
                ],
            62 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0140',
                            'dest' => '"l"',
                        ],
                ],
            63 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0142',
                            'dest' => '"l"',
                        ],
                ],
            64 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0143',
                            'dest' => '"N"',
                        ],
                ],
            65 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0145',
                            'dest' => '"N"',
                        ],
                ],
            66 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0147',
                            'dest' => '"N"',
                        ],
                ],
            67 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0144',
                            'dest' => '"n"',
                        ],
                ],
            68 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0146',
                            'dest' => '"n"',
                        ],
                ],
            69 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0148',
                            'dest' => '"n"',
                        ],
                ],
            70 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+014C',
                            'dest' => '"O"',
                        ],
                ],
            71 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+014E',
                            'dest' => '"O"',
                        ],
                ],
            72 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0150',
                            'dest' => '"O"',
                        ],
                ],
            73 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+014D',
                            'dest' => '"o"',
                        ],
                ],
            74 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+014F',
                            'dest' => '"o"',
                        ],
                ],
            75 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0151',
                            'dest' => '"o"',
                        ],
                ],
            76 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0154',
                            'dest' => '"R"',
                        ],
                ],
            77 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0156',
                            'dest' => '"R"',
                        ],
                ],
            78 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0158',
                            'dest' => '"R"',
                        ],
                ],
            79 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0155',
                            'dest' => '"r"',
                        ],
                ],
            80 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0157',
                            'dest' => '"r"',
                        ],
                ],
            81 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0159',
                            'dest' => '"r"',
                        ],
                ],
            82 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+015A',
                            'dest' => '"S"',
                        ],
                ],
            83 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+015C',
                            'dest' => '"S"',
                        ],
                ],
            84 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+015E',
                            'dest' => '"S"',
                        ],
                ],
            85 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0160',
                            'dest' => '"S"',
                        ],
                ],
            86 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+015B',
                            'dest' => '"s"',
                        ],
                ],
            87 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+015D',
                            'dest' => '"s"',
                        ],
                ],
            88 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+015F',
                            'dest' => '"s"',
                        ],
                ],
            89 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0161',
                            'dest' => '"s"',
                        ],
                ],
            90 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0162',
                            'dest' => '"T"',
                        ],
                ],
            91 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0164',
                            'dest' => '"T"',
                        ],
                ],
            92 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0166',
                            'dest' => '"T"',
                        ],
                ],
            93 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0163',
                            'dest' => '"t"',
                        ],
                ],
            94 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0165',
                            'dest' => '"t"',
                        ],
                ],
            95 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0167',
                            'dest' => '"t"',
                        ],
                ],
            96 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0168',
                            'dest' => '"U"',
                        ],
                ],
            97 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+016A',
                            'dest' => '"U"',
                        ],
                ],
            98 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+016C',
                            'dest' => '"U"',
                        ],
                ],
            99 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+016E',
                            'dest' => '"U"',
                        ],
                ],
            100 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0170',
                            'dest' => '"U"',
                        ],
                ],
            101 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0172',
                            'dest' => '"U"',
                        ],
                ],
            102 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0169',
                            'dest' => '"u"',
                        ],
                ],
            103 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+016B',
                            'dest' => '"u"',
                        ],
                ],
            104 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+016D',
                            'dest' => '"u"',
                        ],
                ],
            105 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+016F',
                            'dest' => '"u"',
                        ],
                ],
            106 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0171',
                            'dest' => '"u"',
                        ],
                ],
            107 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0173',
                            'dest' => '"u"',
                        ],
                ],
            108 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0174',
                            'dest' => '"W"',
                        ],
                ],
            109 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0175',
                            'dest' => '"w"',
                        ],
                ],
            110 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0176',
                            'dest' => '"Y"',
                        ],
                ],
            111 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0177',
                            'dest' => '"y"',
                        ],
                ],
            112 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0178',
                            'dest' => '"Y"',
                        ],
                ],
            113 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0179',
                            'dest' => '"Z"',
                        ],
                ],
            114 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+017B',
                            'dest' => '"Z"',
                        ],
                ],
            115 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+017D',
                            'dest' => '"Z"',
                        ],
                ],
            116 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+017A',
                            'dest' => '"z"',
                        ],
                ],
            117 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+017C',
                            'dest' => '"z"',
                        ],
                ],
            118 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+017E',
                            'dest' => '"z"',
                        ],
                ],
            119 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0218',
                            'dest' => '"S"',
                        ],
                ],
            120 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0219',
                            'dest' => '"s"',
                        ],
                ],
            121 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+021A',
                            'dest' => '"T"',
                        ],
                ],
            122 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+021B',
                            'dest' => '"t"',
                        ],
                ],
        ],
    'latin1_transliterate_ascii' => [
            0 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00E6',
                            'dest' => '"ae"',
                        ],
                ],
            1 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00C6',
                            'dest' => '"AE"',
                        ],
                ],
            2 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00E5',
                            'dest' => '"aa"',
                        ],
                ],
            3 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00C5',
                            'dest' => '"AA"',
                        ],
                ],
            4 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00F8',
                            'dest' => '"oe"',
                        ],
                ],
            5 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00D8',
                            'dest' => '"OE"',
                        ],
                ],
            6 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+009C',
                            'dest' => '"oe"',
                        ],
                ],
            7 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+008C',
                            'dest' => '"OE"',
                        ],
                ],
            8 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00AA',
                            'dest' => '"a"',
                        ],
                ],
            9 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00BA',
                            'dest' => '"o"',
                        ],
                ],
            10 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00E4',
                            'dest' => '"ae"',
                        ],
                ],
            11 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00F6',
                            'dest' => '"oe"',
                        ],
                ],
            12 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00FC',
                            'dest' => '"ue"',
                        ],
                ],
            13 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00C4',
                            'dest' => '"Ae"',
                        ],
                ],
            14 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00D6',
                            'dest' => '"Oe"',
                        ],
                ],
            15 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00DC',
                            'dest' => '"Ue"',
                        ],
                ],
        ],
    'latin-exta_transliterate_ascii' => [
            0 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0132',
                            'dest' => '"IJ"',
                        ],
                ],
            1 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0133',
                            'dest' => '"ij"',
                        ],
                ],
            2 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0138',
                            'dest' => '"k"',
                        ],
                ],
            3 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0149',
                            'dest' => '"\'n"',
                        ],
                ],
            4 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+014A',
                            'dest' => '"N"',
                        ],
                ],
            5 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+014B',
                            'dest' => '"n"',
                        ],
                ],
            6 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0152',
                            'dest' => '"AE"',
                        ],
                ],
            7 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+0153',
                            'dest' => '"ae"',
                        ],
                ],
            8 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+017F',
                            'dest' => '"s"',
                        ],
                ],
        ],
    'math_to_ascii' => [
            0 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00D7',
                            'dest' => '"*"',
                        ],
                ],
            1 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00F7',
                            'dest' => '"/"',
                        ],
                ],
        ],
    'inverted_to_normal' => [
            0 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00BF',
                            'dest' => 'U+003F',
                        ],
                ],
            1 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00A1',
                            'dest' => 'U+0021',
                        ],
                ],
        ],
    'latin_search_cleanup' => [
            0 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00BC',
                            'srcEnd' => 'U+00BE',
                            'dest' => 'U+0020',
                        ],
                ],
            1 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+00A2',
                            'srcEnd' => 'U+00A7',
                            'dest' => 'U+0020',
                        ],
                ],
            2 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00AC',
                            'dest' => 'U+002D',
                        ],
                ],
            3 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00AF',
                            'dest' => 'U+002D',
                        ],
                ],
            4 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00B5',
                            'dest' => 'U+0020',
                        ],
                ],
            5 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00B6',
                            'dest' => 'U+0020',
                        ],
                ],
            6 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00B7',
                            'dest' => 'U+0020',
                        ],
                ],
            7 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00B8',
                            'dest' => 'U+0020',
                        ],
                ],
            8 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00A6',
                            'dest' => 'U+0020',
                        ],
                ],
            9 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00A7',
                            'dest' => 'U+0020',
                        ],
                ],
            10 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00A8',
                            'dest' => 'remove',
                        ],
                ],
            11 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00B0',
                            'dest' => 'U+0020',
                        ],
                ],
            12 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00A9',
                            'dest' => 'U+0020',
                        ],
                ],
            13 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00AE',
                            'dest' => 'U+0020',
                        ],
                ],
            14 => [
                    'type' => 11,
                    'data' => [
                            'src' => 'U+00B4',
                            'dest' => 'U+0020',
                        ],
                ],
            15 => [
                    'type' => 12,
                    'data' => [
                            'srcStart' => 'U+0080',
                            'srcEnd' => 'U+009F',
                            'dest' => 'U+0020',
                        ],
                ],
        ],
];

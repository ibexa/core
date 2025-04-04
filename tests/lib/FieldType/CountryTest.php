<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Country\Exception\InvalidValue;
use Ibexa\Core\FieldType\Country\Type as Country;
use Ibexa\Core\FieldType\Country\Value as CountryValue;
use Ibexa\Core\FieldType\ValidationError;

/**
 * @group fieldType
 * @group ibexa_country
 */
class CountryTest extends FieldTypeTestCase
{
    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_country';
    }

    protected function createFieldTypeUnderTest(): Country
    {
        $fieldType = new Country(
            [
                'BE' => [
                    'Name' => 'Belgium',
                    'Alpha2' => 'BE',
                    'Alpha3' => 'BEL',
                    'IDC' => 32,
                ],
                'FR' => [
                    'Name' => 'France',
                    'Alpha2' => 'FR',
                    'Alpha3' => 'FRA',
                    'IDC' => 33,
                ],
                'NO' => [
                    'Name' => 'Norway',
                    'Alpha2' => 'NO',
                    'Alpha3' => 'NOR',
                    'IDC' => 47,
                ],
                'KP' => [
                    'Name' => "Korea, Democratic People's Republic of",
                    'Alpha2' => 'KP',
                    'Alpha3' => 'PRK',
                    'IDC' => 850,
                ],
                'TF' => [
                    'Name' => 'French Southern Territories',
                    'Alpha2' => 'TF',
                    'Alpha3' => 'ATF',
                    'IDC' => 0,
                ],
                'CF' => [
                    'Name' => 'Central African Republic',
                    'Alpha2' => 'CF',
                    'Alpha3' => 'CAF',
                    'IDC' => 236,
                ],
            ]
        );
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [
            'isMultiple' => [
                'type' => 'boolean',
                'default' => false,
            ],
        ];
    }

    protected function getEmptyValueExpectation(): CountryValue
    {
        return new CountryValue();
    }

    public function provideInvalidInputForAcceptValue(): array
    {
        return [
            [
                'LegoLand',
                InvalidArgumentException::class,
            ],
            [
                ['Norway', 'France', 'LegoLand'],
                InvalidValue::class,
            ],
            [
                ['FR', 'BE', 'LE'],
                InvalidValue::class,
            ],
            [
                ['FRE', 'BEL', 'LEG'],
                InvalidValue::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): array
    {
        return [
            [
                ['BE', 'FR'],
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                        'FR' => [
                            'Name' => 'France',
                            'Alpha2' => 'FR',
                            'Alpha3' => 'FRA',
                            'IDC' => 33,
                        ],
                    ]
                ),
            ],
            [
                ['Belgium'],
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                    ]
                ),
            ],
            [
                ['BE'],
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                    ]
                ),
            ],
            [
                ['BEL'],
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideInputForToHash(): array
    {
        return [
            [
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                    ]
                ),
                ['BE'],
            ],
            [
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                        'FR' => [
                            'Name' => 'France',
                            'Alpha2' => 'FR',
                            'Alpha3' => 'FRA',
                            'IDC' => 33,
                        ],
                    ]
                ),
                ['BE', 'FR'],
            ],
        ];
    }

    public function provideInputForFromHash(): array
    {
        return [
            [
                ['BE'],
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                    ]
                ),
            ],
            [
                ['BE', 'FR'],
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                        'FR' => [
                            'Name' => 'France',
                            'Alpha2' => 'FR',
                            'Alpha3' => 'FRA',
                            'IDC' => 33,
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideDataForGetName(): array
    {
        return [
            [new CountryValue(), '', [], 'en_GB'],
            [new CountryValue(['FR' => ['Name' => 'France']]), 'France', [], 'en_GB'],
            [
                new CountryValue(['FR' => ['Name' => 'France'], 'DE' => ['Name' => 'Deutschland']]),
                'France, Deutschland',
                [],
                'en_GB',
            ],
        ];
    }

    public function provideValidDataForValidate(): array
    {
        return [
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => true,
                    ],
                ],
                new CountryValue(),
            ],
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => false,
                    ],
                ],
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                    ]
                ),
            ],
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => true,
                    ],
                ],
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                        'FR' => [
                            'Name' => 'France',
                            'Alpha2' => 'FR',
                            'Alpha3' => 'FRA',
                            'IDC' => 33,
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideInvalidDataForValidate(): array
    {
        return [
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => false,
                    ],
                ],
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                        'FR' => [
                            'Name' => 'France',
                            'Alpha2' => 'FR',
                            'Alpha3' => 'FRA',
                            'IDC' => 33,
                        ],
                    ]
                ),
                [
                    new ValidationError(
                        'Field definition does not allow multiple countries to be selected.',
                        null,
                        [],
                        'countries'
                    ),
                ],
            ],
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => true,
                    ],
                ],
                new CountryValue(
                    [
                        'LE' => [
                            'Name' => 'LegoLand',
                            'Alpha2' => 'LE',
                            'Alpha3' => 'LEG',
                            'IDC' => 888,
                        ],
                    ]
                ),
                [
                    new ValidationError(
                        "Country with Alpha2 code '%alpha2%' is not defined in FieldType settings.",
                        null,
                        [
                            '%alpha2%' => 'LE',
                        ],
                        'countries'
                    ),
                ],
            ],
        ];
    }
}

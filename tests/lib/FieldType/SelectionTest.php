<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Selection\Type as Selection;
use Ibexa\Core\FieldType\Selection\Value as SelectionValue;
use Ibexa\Core\FieldType\ValidationError;

/**
 * @group fieldType
 * @group ibexa_selection
 */
class SelectionTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): Selection
    {
        $fieldType = new Selection();
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
                'type' => 'bool',
                'default' => false,
            ],
            'options' => [
                'type' => 'hash',
                'default' => [],
            ],
            'multilingualOptions' => [
                'type' => 'hash',
                'default' => [],
            ],
        ];
    }

    protected function getEmptyValueExpectation(): SelectionValue
    {
        return new SelectionValue();
    }

    public function provideInvalidInputForAcceptValue(): array
    {
        return [
            [
                23,
                InvalidArgumentException::class,
            ],
            [
                'sindelfingen',
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): array
    {
        return [
            [
                [],
                new SelectionValue(),
            ],
            [
                [23],
                new SelectionValue([23]),
            ],
            [
                [23, 42],
                new SelectionValue([23, 42]),
            ],
            [
                new SelectionValue([23, 42]),
                new SelectionValue([23, 42]),
            ],
        ];
    }

    public function provideInputForToHash(): array
    {
        return [
            [
                new SelectionValue(),
                [],
            ],
            [
                new SelectionValue([23, 42]),
                [23, 42],
            ],
        ];
    }

    public function provideInputForFromHash(): array
    {
        return [
            [
                [],
                new SelectionValue(),
            ],
            [
                [23, 42],
                new SelectionValue([23, 42]),
            ],
        ];
    }

    public function provideValidFieldSettings(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'isMultiple' => true,
                    'options' => ['foo', 'bar'],
                ],
            ],
            [
                [
                    'isMultiple' => false,
                    'options' => [23, 42],
                ],
            ],
        ];
    }

    public function provideInValidFieldSettings(): array
    {
        return [
            [
                [
                    // isMultiple must be bool
                    'isMultiple' => 23,
                ],
            ],
            [
                [
                    // options must be array
                    'options' => 23,
                ],
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_selection';
    }

    /**
     * @dataProvider provideDataForGetName
     */
    public function testGetName(
        SPIValue $value,
        string $expected,
        array $fieldSettings = [],
        string $languageCode = 'en_GB'
    ): void {
        $fieldDefinitionMock = $this->getFieldDefinitionMock($fieldSettings);
        $fieldDefinitionMock
            ->method('__get')
            ->with('mainLanguageCode')
            ->willReturn('de_DE');

        $name = $this->getFieldTypeUnderTest()->getName($value, $fieldDefinitionMock, $languageCode);

        self::assertSame($expected, $name);
    }

    public function provideDataForGetName(): array
    {
        return [
            'empty_value_and_field_settings' => [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            'one_option' => [
                new SelectionValue(['optionIndex1']),
                'option_1',
                ['options' => ['optionIndex1' => 'option_1']],
                'en_GB',
            ],
            'two_options' => [
                new SelectionValue(['optionIndex1', 'optionIndex2']),
                'option_1 option_2',
                ['options' => ['optionIndex1' => 'option_1', 'optionIndex2' => 'option_2']],
                'en_GB',
            ],
            'multilingual_options' => [
                new SelectionValue(['optionIndex1', 'optionIndex2']),
                'option_1 option_2',
                ['multilingualOptions' => ['en_GB' => ['optionIndex1' => 'option_1', 'optionIndex2' => 'option_2']]],
                'en_GB',
            ],
            'multilingual_options_with_main_language_code' => [
                new SelectionValue(['optionIndex3', 'optionIndex4']),
                'option_3 option_4',
                ['multilingualOptions' => [
                    'en_GB' => ['optionIndex1' => 'option_1', 'optionIndex2' => 'option_2'],
                    'de_DE' => ['optionIndex3' => 'option_3', 'optionIndex4' => 'option_4'],
                ]],
                'de_DE',
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
                        'options' => [0 => 1, 1 => 2],
                    ],
                ],
                new SelectionValue([0, 1]),
            ],
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => false,
                        'options' => [0 => 1, 1 => 2],
                    ],
                ],
                new SelectionValue([1]),
            ],
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => false,
                        'options' => [0 => 1, 1 => 2],
                    ],
                ],
                new SelectionValue(),
            ],
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => false,
                        'options' => [0 => 1, 1 => 2],
                        'multilingualOptions' => [
                            'en_GB' => [0 => 1, 1 => 2],
                            'de_DE' => [0 => 1, 1 => 2],
                        ],
                    ],
                ],
                new SelectionValue([1]),
            ],
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => false,
                        'options' => [0 => 1, 1 => 2],
                        'multilingualOptions' => [
                            'en_GB' => [0 => 1, 1 => 2],
                            'de_DE' => [0 => 1],
                        ],
                    ],
                ],
                new SelectionValue([1]),
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
                        'options' => [0 => 1, 1 => 2],
                    ],
                ],
                new SelectionValue([0, 1]),
                [
                    new ValidationError(
                        'Field definition does not allow multiple options to be selected.',
                        null,
                        [],
                        'selection'
                    ),
                ],
            ],
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => false,
                        'options' => [0 => 1, 1 => 2],
                    ],
                ],
                new SelectionValue([3]),
                [
                    new ValidationError(
                        'Option with index %index% does not exist in the field definition.',
                        null,
                        [
                            '%index%' => 3,
                        ],
                        'selection'
                    ),
                ],
            ],
            [
                [
                    'fieldSettings' => [
                        'isMultiple' => false,
                        'options' => [0 => 1, 1 => 2],
                        'multilingualOptions' => [
                            'en_GB' => [0 => 1, 1 => 2],
                            'de_DE' => [0 => 1],
                        ],
                    ],
                ],
                new SelectionValue([3]),
                [
                    new ValidationError(
                        'Option with index %index% does not exist in the field definition.',
                        null,
                        [
                            '%index%' => 3,
                        ],
                        'selection'
                    ),
                ],
            ],
        ];
    }
}

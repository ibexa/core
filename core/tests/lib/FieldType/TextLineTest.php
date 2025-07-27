<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\Exception\InvalidArgumentType;
use Ibexa\Core\FieldType\TextLine\Type as TextLineType;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\FieldType\ValidationError;

/**
 * @group fieldType
 * @group ibexa_string
 */
final class TextLineTest extends FieldTypeTestCase
{
    private const string STRING_TOO_SHORT_EXPECTED_SINGULAR_MESSAGE = 'The string cannot be shorter than %size% character.';
    private const string STRING_TOO_SHORT_EXPECTED_PLURAL_MESSAGE = 'The string cannot be shorter than %size% characters.';
    private const string SIZE_PARAM_NAME = '%size%';
    private const string SAMPLE_TEXT_LINE_VALUE = ' sindelfingen ';

    protected function createFieldTypeUnderTest(): TextLineType
    {
        $fieldType = new TextLineType();
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [
            'StringLengthValidator' => [
                'minStringLength' => [
                    'type' => 'int',
                    'default' => 0,
                ],
                'maxStringLength' => [
                    'type' => 'int',
                    'default' => null,
                ],
            ],
        ];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    protected function getEmptyValueExpectation(): TextLineValue
    {
        return new TextLineValue();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                23,
                InvalidArgumentType::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null input' => [
            null,
            new TextLineValue(),
        ];

        yield 'empty string' => [
            '',
            new TextLineValue(),
        ];

        yield 'whitespace string' => [
            ' ',
            new TextLineValue(),
        ];

        yield 'text string' => [
            self::SAMPLE_TEXT_LINE_VALUE,
            new TextLineValue(self::SAMPLE_TEXT_LINE_VALUE),
        ];

        yield 'TextLineValue object' => [
            new TextLineValue(self::SAMPLE_TEXT_LINE_VALUE),
            new TextLineValue(self::SAMPLE_TEXT_LINE_VALUE),
        ];

        yield 'large number string' => [
            '12345678901',
            new TextLineValue('12345678901'),
        ];

        yield 'empty TextLineValue object' => [
            new TextLineValue(''),
            new TextLineValue(),
        ];

        yield 'whitespace TextLineValue object' => [
            new TextLineValue(' '),
            new TextLineValue(),
        ];

        yield 'null TextLineValue object' => [
            new TextLineValue(null),
            new TextLineValue(),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new TextLineValue(),
                null,
            ],
            [
                new TextLineValue(''),
                null,
            ],
            [
                new TextLineValue(self::SAMPLE_TEXT_LINE_VALUE),
                self::SAMPLE_TEXT_LINE_VALUE,
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                null,
                new TextLineValue(),
            ],
            [
                '',
                new TextLineValue(),
            ],
            [
                self::SAMPLE_TEXT_LINE_VALUE,
                new TextLineValue(self::SAMPLE_TEXT_LINE_VALUE),
            ],
        ];
    }

    public function provideValidValidatorConfiguration(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'minStringLength' => null,
                    ],
                ],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'minStringLength' => 23,
                    ],
                ],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'maxStringLength' => null,
                    ],
                ],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'maxStringLength' => 23,
                    ],
                ],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'minStringLength' => 23,
                        'maxStringLength' => 42,
                    ],
                ],
            ],
        ];
    }

    public function provideInvalidValidatorConfiguration(): array
    {
        return [
            [
                [
                    'NonExistentValidator' => [],
                ],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'nonExistentValue' => 23,
                    ],
                ],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'minStringLength' => .23,
                    ],
                ],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'maxStringLength' => .42,
                    ],
                ],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'minStringLength' => -23,
                    ],
                ],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'maxStringLength' => -42,
                    ],
                ],
            ],
            [
                [
                    'StringLengthValidator' => [
                        'maxStringLength' => 23,
                        'minStringLength' => 42,
                    ],
                ],
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_string';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new TextLineValue('This is a line of text'), 'This is a line of text', [], 'en_GB'],
        ];
    }

    public function provideValidDataForValidate(): iterable
    {
        yield 'string within length limits' => [
            [
                'validatorConfiguration' => [
                    'StringLengthValidator' => [
                        'minStringLength' => 2,
                        'maxStringLength' => 10,
                    ],
                ],
            ],
            new TextLineValue('lalalala'),
        ];

        yield 'string within max length only' => [
            [
                'validatorConfiguration' => [
                    'StringLengthValidator' => [
                        'maxStringLength' => 10,
                    ],
                ],
            ],
            new TextLineValue('lililili'),
        ];

        yield 'unicode string within limits' => [
            [
                'validatorConfiguration' => [
                    'StringLengthValidator' => [
                        'maxStringLength' => 10,
                    ],
                ],
            ],
            new TextLineValue('♔♕♖♗♘♙♚♛♜♝'),
        ];
    }

    public function provideInvalidDataForValidate(): iterable
    {
        yield 'string too short' => [
            [
                'validatorConfiguration' => [
                    'StringLengthValidator' => [
                        'minStringLength' => 5,
                        'maxStringLength' => 10,
                    ],
                ],
            ],
            new TextLineValue('aaa'),
            [
                new ValidationError(
                    self::STRING_TOO_SHORT_EXPECTED_SINGULAR_MESSAGE,
                    self::STRING_TOO_SHORT_EXPECTED_PLURAL_MESSAGE,
                    [
                        self::SIZE_PARAM_NAME => 5,
                    ],
                    'text'
                ),
            ],
        ];

        yield 'string too long' => [
            [
                'validatorConfiguration' => [
                    'StringLengthValidator' => [
                        'minStringLength' => 5,
                        'maxStringLength' => 10,
                    ],
                ],
            ],
            new TextLineValue('0123456789012345'),
            [
                new ValidationError(
                    'The string can not exceed %size% character.',
                    'The string can not exceed %size% characters.',
                    [
                        self::SIZE_PARAM_NAME => 10,
                    ],
                    'text'
                ),
            ],
        ];

        yield 'string wrong length with reversed limits' => [
            [
                'validatorConfiguration' => [
                    'StringLengthValidator' => [
                        'minStringLength' => 10,
                        'maxStringLength' => 5,
                    ],
                ],
            ],
            new TextLineValue('1234567'),
            [
                new ValidationError(
                    'The string can not exceed %size% character.',
                    'The string can not exceed %size% characters.',
                    [
                        self::SIZE_PARAM_NAME => 5,
                    ],
                    'text'
                ),
                new ValidationError(
                    self::STRING_TOO_SHORT_EXPECTED_SINGULAR_MESSAGE,
                    self::STRING_TOO_SHORT_EXPECTED_PLURAL_MESSAGE,
                    [
                        self::SIZE_PARAM_NAME => 10,
                    ],
                    'text'
                ),
            ],
        ];

        yield 'unicode string too short' => [
            [
                'validatorConfiguration' => [
                    'StringLengthValidator' => [
                        'minStringLength' => 5,
                        'maxStringLength' => 10,
                    ],
                ],
            ],
            new TextLineValue('ABC♔'),
            [
                new ValidationError(
                    self::STRING_TOO_SHORT_EXPECTED_SINGULAR_MESSAGE,
                    self::STRING_TOO_SHORT_EXPECTED_PLURAL_MESSAGE,
                    [
                        self::SIZE_PARAM_NAME => 5,
                    ],
                    'text'
                ),
            ],
        ];
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\TextBlock\Type as TextBlockType;
use Ibexa\Core\FieldType\TextBlock\Value as TextBlockValue;

/**
 * @group fieldType
 * @group ibexa_text
 */
final class TextBlockTest extends FieldTypeTestCase
{
    private const string SAMPLE_TEXT_LINE_VALUE = ' sindelfingen ';

    protected function createFieldTypeUnderTest(): TextBlockType
    {
        $fieldType = new TextBlockType();
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
            'textRows' => [
                'type' => 'int',
                'default' => 10,
            ],
        ];
    }

    protected function getEmptyValueExpectation(): TextBlockValue
    {
        return new TextBlockValue();
    }

    /**
     * @return list<array{mixed, class-string}>
     */
    public function provideInvalidInputForAcceptValue(): array
    {
        return [
            [
                23,
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): array
    {
        return [
            [
                null,
                new TextBlockValue(),
            ],
            [
                '',
                new TextBlockValue(),
            ],
            [
                self::SAMPLE_TEXT_LINE_VALUE,
                new TextBlockValue(self::SAMPLE_TEXT_LINE_VALUE),
            ],
            [
                new TextBlockValue(self::SAMPLE_TEXT_LINE_VALUE),
                new TextBlockValue(self::SAMPLE_TEXT_LINE_VALUE),
            ],
            [
                new TextBlockValue(''),
                new TextBlockValue(),
            ],
            [
                new TextBlockValue(null),
                new TextBlockValue(),
            ],
        ];
    }

    public function provideInputForToHash(): array
    {
        return [
            [
                new TextBlockValue(),
                null,
            ],
            [
                new TextBlockValue(self::SAMPLE_TEXT_LINE_VALUE),
                self::SAMPLE_TEXT_LINE_VALUE,
            ],
        ];
    }

    public function provideInputForFromHash(): array
    {
        return [
            [
                '',
                new TextBlockValue(),
            ],
            [
                self::SAMPLE_TEXT_LINE_VALUE,
                new TextBlockValue(self::SAMPLE_TEXT_LINE_VALUE),
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
                    'textRows' => 23,
                ],
            ],
        ];
    }

    public function provideInValidFieldSettings(): array
    {
        return [
            [
                [
                    'non-existent' => 'foo',
                ],
            ],
            [
                [
                    // textRows must be integer
                    'textRows' => 'foo',
                ],
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_text';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new TextBlockValue('This is a piece of text'), 'This is a piece of text', [], 'en_GB'],
        ];
    }
}

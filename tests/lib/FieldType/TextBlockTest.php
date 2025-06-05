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

    public function provideInvalidInputForAcceptValue(): iterable
    {
        yield [
            23,
            InvalidArgumentException::class,
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null input' => [
            null,
            new TextBlockValue(),
        ];

        yield 'empty string' => [
            '',
            new TextBlockValue(),
        ];

        yield 'text string' => [
            self::SAMPLE_TEXT_LINE_VALUE,
            new TextBlockValue(self::SAMPLE_TEXT_LINE_VALUE),
        ];

        yield 'TextBlockValue object' => [
            new TextBlockValue(self::SAMPLE_TEXT_LINE_VALUE),
            new TextBlockValue(self::SAMPLE_TEXT_LINE_VALUE),
        ];

        yield 'empty TextBlockValue object' => [
            new TextBlockValue(''),
            new TextBlockValue(),
        ];

        yield 'null TextBlockValue object' => [
            new TextBlockValue(null),
            new TextBlockValue(),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        yield 'empty value' => [
            new TextBlockValue(),
            null,
        ];

        yield 'sample text value' => [
            new TextBlockValue(self::SAMPLE_TEXT_LINE_VALUE),
            self::SAMPLE_TEXT_LINE_VALUE,
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        yield 'empty string' => [
            '',
            new TextBlockValue(),
        ];

        yield 'sample text value' => [
            self::SAMPLE_TEXT_LINE_VALUE,
            new TextBlockValue(self::SAMPLE_TEXT_LINE_VALUE),
        ];
    }

    public function provideValidFieldSettings(): iterable
    {
        yield 'empty settings' => [
            [],
        ];

        yield 'text rows setting' => [
            [
                'textRows' => 23,
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

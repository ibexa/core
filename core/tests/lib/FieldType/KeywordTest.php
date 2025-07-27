<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Keyword\Type as KeywordType;
use Ibexa\Core\FieldType\Keyword\Value as KeywordValue;
use Ibexa\Core\FieldType\ValidationError;

/**
 * @group fieldType
 * @group ibexa_integer
 */
class KeywordTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): KeywordType
    {
        $fieldType = new KeywordType();
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    protected function getEmptyValueExpectation(): KeywordValue
    {
        return new KeywordValue([]);
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                23,
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null input' => [
            null,
            new KeywordValue([]),
        ];

        yield 'empty array' => [
            [],
            new KeywordValue([]),
        ];

        yield 'single string keyword' => [
            'foo',
            new KeywordValue(['foo']),
        ];

        yield 'array with single keyword' => [
            ['foo'],
            new KeywordValue(['foo']),
        ];

        yield 'KeywordValue object' => [
            new KeywordValue(['foo']),
            new KeywordValue(['foo']),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new KeywordValue([]),
                [],
            ],
            [
                new KeywordValue(['foo', 'bar']),
                ['foo', 'bar'],
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                [],
                new KeywordValue([]),
            ],
            [
                ['foo', 'bar'],
                new KeywordValue(['foo', 'bar']),
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_keyword';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new KeywordValue(['foo', 'bar']), 'foo, bar', [], 'en_GB'],
        ];
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: \Ibexa\Core\FieldType\Keyword\Value}>
     */
    public function provideValidDataForValidate(): iterable
    {
        yield 'multiple keywords' => [
            [],
            new KeywordValue(['foo', 'bar']),
        ];

        yield 'empty string keyword' => [
            [],
            new KeywordValue(['']),
        ];

        yield 'empty keyword list' => [
            [],
            new KeywordValue([]),
        ];
    }

    /**
     * @return iterable<string, array{
     *     0: array<string, mixed>,
     *     1: \Ibexa\Core\FieldType\Keyword\Value,
     *     2: array<\Ibexa\Contracts\Core\FieldType\ValidationError>
     * }>
     */
    public function provideInvalidDataForValidate(): iterable
    {
        $maxLen = KeywordType::MAX_KEYWORD_LENGTH;

        yield 'non-string keyword (int)' => [
            [],
            // @phpstan-ignore-next-line
            new KeywordValue(['valid', 123]),
            [
                new ValidationError(
                    'Each keyword must be a string.',
                    null,
                    [],
                    'values'
                ),
            ],
        ];

        yield 'non-string keyword (null)' => [
            [],
            // @phpstan-ignore-next-line
            new KeywordValue(['valid', null]),
            [
                new ValidationError(
                    'Each keyword must be a string.',
                    null,
                    [],
                    'values'
                ),
            ],
        ];

        yield 'too long keyword' => [
            [],
            new KeywordValue(['valid', str_repeat('a', $maxLen + 1)]),
            [
                new ValidationError(
                    'Keyword value must be less than or equal to ' . $maxLen . ' characters.',
                    null,
                    [],
                    'values'
                ),
            ],
        ];
    }
}

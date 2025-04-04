<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Keyword\Type as KeywordType;
use Ibexa\Core\FieldType\Keyword\Value as KeywordValue;

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
                new KeywordValue([]),
            ],
            [
                [],
                new KeywordValue([]),
            ],
            [
                'foo',
                new KeywordValue(['foo']),
            ],
            [
                ['foo'],
                new KeywordValue(['foo']),
            ],
            [
                new KeywordValue(['foo']),
                new KeywordValue(['foo']),
            ],
        ];
    }

    public function provideInputForToHash(): array
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

    public function provideInputForFromHash(): array
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
}

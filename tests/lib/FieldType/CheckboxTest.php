<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Checkbox\Type as Checkbox;
use Ibexa\Core\FieldType\Checkbox\Value as CheckboxValue;

/**
 * @group fieldType
 * @group ibexa_boolean
 */
class CheckboxTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): Checkbox
    {
        $fieldType = new Checkbox();
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

    protected function getEmptyValueExpectation(): CheckboxValue
    {
        return new CheckboxValue(false);
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                23,
                InvalidArgumentException::class,
            ],
            [
                /** @phpstan-ignore argument.type */
                new CheckboxValue(42),
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'false value' => [
            false,
            new CheckboxValue(false),
        ];

        yield 'true value' => [
            true,
            new CheckboxValue(true),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new CheckboxValue(true),
                true,
            ],
            [
                new CheckboxValue(false),
                false,
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                true,
                new CheckboxValue(true),
            ],
            [
                false,
                new CheckboxValue(false),
            ],
        ];
    }

    public function testToPersistenceValue(): void
    {
        $ft = $this->createFieldTypeUnderTest();
        $fieldValue = $ft->toPersistenceValue(new CheckboxValue(true));

        self::assertTrue($fieldValue->data);
        self::assertSame(1, $fieldValue->sortKey);
    }

    public function testBuildFieldValueWithParam(): void
    {
        $bool = true;
        $value = new CheckboxValue($bool);
        self::assertSame($bool, $value->bool);
    }

    public function testBuildFieldValueWithoutParam(): void
    {
        $value = new CheckboxValue();
        self::assertFalse($value->bool);
    }

    public function testFieldValueToString(): void
    {
        $valueTrue = new CheckboxValue(true);
        $valueFalse = new CheckboxValue(false);
        self::assertSame('1', (string)$valueTrue);
        self::assertSame('0', (string)$valueFalse);
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_boolean';
    }

    public function provideDataForGetName(): array
    {
        return [
            [new CheckboxValue(true), '1', [], 'en_GB'],
            [new CheckboxValue(false), '0', [], 'en_GB'],
        ];
    }

    /**
     * @dataProvider provideForValueIsNeverEmpty
     */
    public function testValueIsNeverEmpty(CheckboxValue $value): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        self::assertFalse($fieldType->isEmptyValue($value));
    }

    /**
     * @return iterable<array{
     *     \Ibexa\Core\FieldType\Checkbox\Value,
     * }>
     */
    public function provideForValueIsNeverEmpty(): iterable
    {
        yield [new CheckboxValue(true)];
        yield [new CheckboxValue(false)];
    }

    public function testEmptyValueIsEmpty(): void
    {
        self::markTestSkipped('Value of Checkbox fieldtype is never considered empty');
    }
}

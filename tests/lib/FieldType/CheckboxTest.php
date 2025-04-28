<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Checkbox\Type;
use Ibexa\Core\FieldType\Checkbox\Type as Checkbox;
use Ibexa\Core\FieldType\Checkbox\Value;
use Ibexa\Core\FieldType\Checkbox\Value as CheckboxValue;

/**
 * @group fieldType
 * @group ezboolean
 */
class CheckboxTest extends FieldTypeTestCase
{
    /**
     * Returns the field type under test.
     *
     * This method is used by all test cases to retrieve the field type under
     * test. Just create the FieldType instance using mocks from the provided
     * get*Mock() methods and/or custom get*Mock() implementations. You MUST
     * NOT take care for test case wide caching of the field type, just return
     * a new instance from this method!
     *
     * @return \Ibexa\Contracts\Core\FieldType\FieldType
     */
    protected function createFieldTypeUnderTest(): Type
    {
        $fieldType = new Checkbox();
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    /**
     * Returns the validator configuration schema expected from the field type.
     *
     * @return array
     */
    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [];
    }

    /**
     * Returns the settings schema expected from the field type.
     *
     * @return array
     */
    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    /**
     * Returns the empty value expected from the field type.
     *
     * @return \Ibexa\Core\FieldType\Checkbox\Value
     */
    protected function getEmptyValueExpectation(): Value
    {
        return new CheckboxValue(false);
    }

    public function provideInvalidInputForAcceptValue(): array
    {
        return [
            [
                23,
                InvalidArgumentException::class,
            ],
            [
                new CheckboxValue(42),
                InvalidArgumentException::class,
            ],
        ];
    }

    /**
     * Data provider for valid input to acceptValue().
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to acceptValue(), 2. The expected return value from acceptValue().
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          null,
     *          null
     *      ),
     *      array(
     *          __FILE__,
     *          new BinaryFileValue( array(
     *              'path' => __FILE__,
     *              'fileName' => basename( __FILE__ ),
     *              'fileSize' => filesize( __FILE__ ),
     *              'downloadCount' => 0,
     *              'mimeType' => 'text/plain',
     *          ) )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideValidInputForAcceptValue(): array
    {
        return [
            [
                false,
                new CheckboxValue(false),
            ],
            [
                true,
                new CheckboxValue(true),
            ],
        ];
    }

    /**
     * Provide input for the toHash() method.
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to toHash(), 2. The expected return value from toHash().
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          null,
     *          null
     *      ),
     *      array(
     *          new BinaryFileValue( array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ) ),
     *          array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideInputForToHash(): array
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

    /**
     * Provide input to fromHash() method.
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to fromHash(), 2. The expected return value from fromHash().
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          null,
     *          null
     *      ),
     *      array(
     *          array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ),
     *          new BinaryFileValue( array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ) )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideInputForFromHash(): array
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

    /**
     * @covers \Ibexa\Core\FieldType\Checkbox\Type::toPersistenceValue
     */
    public function testToPersistenceValue(): void
    {
        $ft = $this->createFieldTypeUnderTest();
        $fieldValue = $ft->toPersistenceValue(new CheckboxValue(true));

        self::assertTrue($fieldValue->data);
        self::assertSame(1, $fieldValue->sortKey);
    }

    /**
     * @covers \Ibexa\Core\FieldType\Checkbox\Value::__construct
     */
    public function testBuildFieldValueWithParam(): void
    {
        $bool = true;
        $value = new CheckboxValue($bool);
        self::assertSame($bool, $value->bool);
    }

    /**
     * @covers \Ibexa\Core\FieldType\Checkbox\Value::__construct
     */
    public function testBuildFieldValueWithoutParam(): void
    {
        $value = new CheckboxValue();
        self::assertFalse($value->bool);
    }

    /**
     * @covers \Ibexa\Core\FieldType\Checkbox\Value::__toString
     */
    public function testFieldValueToString(): void
    {
        $valueTrue = new CheckboxValue(true);
        $valueFalse = new CheckboxValue(false);
        self::assertSame('1', (string)$valueTrue);
        self::assertSame('0', (string)$valueFalse);
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ezboolean';
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

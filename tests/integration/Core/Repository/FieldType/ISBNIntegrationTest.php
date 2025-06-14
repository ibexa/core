<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\ISBN\Value as ISBNValue;

/**
 * Integration test for use field type.
 *
 * @group integration
 * @group field-type
 */
class ISBNIntegrationTest extends SearchBaseIntegrationTestCase
{
    /**
     * Get name of tested field type.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'ibexa_isbn';
    }

    /**
     * Get expected settings schema.
     *
     * @return array
     */
    public function getSettingsSchema()
    {
        return [
            'isISBN13' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ];
    }

    /**
     * Get a valid $fieldSettings value.
     *
     * @return mixed
     */
    public function getValidFieldSettings()
    {
        return [
            'isISBN13' => true,
        ];
    }

    /**
     * Get $fieldSettings value not accepted by the field type.
     *
     * @return mixed
     */
    public function getInvalidFieldSettings()
    {
        return [
            'somethingUnknown' => 0,
        ];
    }

    /**
     * Get expected validator schema.
     *
     * @return array
     */
    public function getValidatorSchema()
    {
        return [];
    }

    /**
     * Get a valid $validatorConfiguration.
     *
     * @return mixed
     */
    public function getValidValidatorConfiguration()
    {
        return [];
    }

    /**
     * Get $validatorConfiguration not accepted by the field type.
     *
     * @return mixed
     */
    public function getInvalidValidatorConfiguration()
    {
        return [
            'unknown' => ['value' => 42],
        ];
    }

    /**
     * Get initial field data for valid object creation.
     *
     * @return mixed
     */
    public function getValidCreationFieldData()
    {
        return new ISBNValue('9789722514095');
    }

    /**
     * Get name generated by the given field type (via fieldType->getName()).
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return '9789722514095';
    }

    /**
     * Asserts that the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was stored and loaded correctly.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field $field
     */
    public function assertFieldDataLoadedCorrect(Field $field)
    {
        self::assertInstanceOf(
            ISBNValue::class,
            $field->value
        );

        $expectedData = '9789722514095';

        self::assertEquals(
            $expectedData,
            $field->value
        );
    }

    public function provideInvalidCreationFieldData()
    {
        return [
            [
                '9789722',
                ContentFieldValidationException::class,
            ],
            [
                'NON_VALID_ISBN_CODE',
                ContentFieldValidationException::class,
            ],
            [
                new \stdClass(),
                InvalidArgumentException::class,
            ],
            [
                new ISBNValue('97897225'),
                ContentFieldValidationException::class,
            ],
        ];
    }

    /**
     * Get update field externals data.
     *
     * @return array
     */
    public function getValidUpdateFieldData()
    {
        return new ISBNValue('978-972-25-1409-5');
    }

    /**
     * Get externals updated field data values.
     *
     * This is a PHPUnit data provider
     *
     * @return array
     */
    public function assertUpdatedFieldDataLoadedCorrect(Field $field)
    {
        self::assertInstanceOf(
            ISBNValue::class,
            $field->value
        );

        $expectedData = '978-972-25-1409-5';
        self::assertEquals(
            $expectedData,
            $field->value
        );
    }

    public function provideInvalidUpdateFieldData()
    {
        return $this->provideInvalidCreationFieldData();
    }

    /**
     * Asserts the the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was copied and loaded correctly.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field $field
     */
    public function assertCopiedFieldDataLoadedCorrectly(Field $field)
    {
        self::assertInstanceOf(
            ISBNValue::class,
            $field->value
        );

        $expectedData = '9789722514095';

        self::assertEquals(
            $expectedData,
            $field->value
        );
    }

    /**
     * Get data to test to hash method.
     *
     * This is a PHPUnit data provider
     *
     * The returned records must have the the original value assigned to the
     * first index and the expected hash result to the second. For example:
     *
     * <code>
     * array(
     *      array(
     *          new MyValue( true ),
     *          array( 'myValue' => true ),
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array
     */
    public function provideToHashData()
    {
        return [
            [
                new ISBNValue('9789722514095'),
                '9789722514095',
            ],
            [
                new ISBNValue('978-972-25-1409-5'),
                '978-972-25-1409-5',
            ],
            [
                new ISBNValue('0-9752298-0-X'),
                '0-9752298-0-X',
            ],
        ];
    }

    /**
     * Get expectations for the fromHash call on our field value.
     *
     * This is a PHPUnit data provider
     *
     * @return array
     */
    public function provideFromHashData()
    {
        return [
            [
                '9789722514095',
                new ISBNValue('9789722514095'),
            ],
            [
                '978-972-25-1409-5',
                new ISBNValue('978-972-25-1409-5'),
            ],
            [
                '0-9752298-0-X',
                new ISBNValue('0-9752298-0-X'),
            ],
            [
                '097522980X',
                new ISBNValue('097522980X'),
            ],
        ];
    }

    public function providerForTestIsEmptyValue()
    {
        return [
            [new ISBNValue()],
            [new ISBNValue(null)],
            [new ISBNValue('')],
        ];
    }

    public function providerForTestIsNotEmptyValue()
    {
        return [
            [
                $this->getValidCreationFieldData(),
            ],
        ];
    }

    protected function getValidSearchValueOne(): string
    {
        return '9780099067504';
    }

    protected function getValidSearchValueTwo(): string
    {
        return '9780380448340';
    }

    protected function getFullTextIndexedFieldData()
    {
        return [
            ['9780099067504', '9780380448340'],
        ];
    }
}

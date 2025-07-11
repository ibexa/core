<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\FieldType;

use DateTime;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Core\FieldType\DateAndTime\Value as DateAndTimeValue;

/**
 * Integration test for use field type.
 *
 * @group integration
 * @group field-type
 */
class DateAndTimeIntegrationTest extends SearchBaseIntegrationTestCase
{
    /**
     * Get name of tested field type.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'ibexa_datetime';
    }

    /**
     * {@inheritdoc}
     */
    protected function supportsLikeWildcard($value): bool
    {
        parent::supportsLikeWildcard($value);

        return false;
    }

    /**
     * Get expected settings schema.
     *
     * @return array
     */
    public function getSettingsSchema()
    {
        return [
            'useSeconds' => [
                'type' => 'bool',
                'default' => false,
            ],
            'defaultType' => [
                'type' => 'choice',
                'default' => 0,
            ],
            'dateInterval' => [
                'type' => 'dateInterval',
                'default' => null,
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
            'useSeconds' => false,
            'defaultType' => 0,
            'dateInterval' => null,
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
        // We may only create times from timestamps here, since storing will
        // loose information about the timezone.
        return DateAndTimeValue::fromTimestamp(123456);
    }

    /**
     * Get name generated by the given field type (via fieldType->getName()).
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return 'Fri 1970-02-01 10:17:36';
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
            DateAndTimeValue::class,
            $field->value
        );

        $expectedData = [
            'value' => new \DateTime('@123456'),
        ];
        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );
    }

    public function provideInvalidCreationFieldData()
    {
        return [
            [
                'Some unknown date format', InvalidArgumentException::class,
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
        return DateAndTimeValue::fromTimestamp(12345678);
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
            DateAndTimeValue::class,
            $field->value
        );

        $expectedData = [
            'value' => new \DateTime('@12345678'),
        ];
        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );
    }

    public function provideInvalidUpdateFieldData()
    {
        return $this->provideInvalidCreationFieldData();
    }

    /**
     * Tests failing content update.
     *
     * @param mixed $failingValue
     * @param string $expectedException
     *
     * @dataProvider provideInvalidUpdateFieldData
     */
    public function testUpdateContentFails($failingValue, $expectedException)
    {
        return [
            [
                'Some unknown date format', InvalidArgumentException::class,
            ],
        ];
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
            DateAndTimeValue::class,
            $field->value
        );

        $expectedData = [
            'value' => new \DateTime('@123456'),
        ];
        $this->assertPropertiesCorrect(
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
                DateAndTimeValue::fromTimestamp(123456),
                [
                    'timestamp' => 123456,
                    'rfc850' => 'Friday, 02-Jan-70 10:17:36 GMT+0000',
                ],
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
                [
                    'timestamp' => 123456,
                    'rfc850' => 'Friday, 02-Jan-70 10:17:36 GMT+0000',
                ],
                DateAndTimeValue::fromTimestamp(123456),
            ],
        ];
    }

    public function providerForTestIsEmptyValue()
    {
        return [
            [new DateAndTimeValue()],
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
        return '2012-04-15T15:43:56Z';
    }

    protected function getValidSearchValueTwo(): string
    {
        return '2015-04-15T15:43:56Z';
    }

    protected function getSearchTargetValueOne()
    {
        // Handling Legacy Search Engine, which stores DateAndTime value as integer timestamp
        if ($this->getSetupFactory() instanceof Legacy) {
            $dateTime = new DateTime($this->getValidSearchValueOne());

            return $dateTime->getTimestamp();
        }

        return parent::getSearchTargetValueOne();
    }

    protected function getSearchTargetValueTwo()
    {
        // Handling Legacy Search Engine, which stores DateAndTime value as integer timestamp
        if ($this->getSetupFactory() instanceof Legacy) {
            $dateTime = new DateTime($this->getValidSearchValueTwo());

            return $dateTime->getTimestamp();
        }

        return parent::getSearchTargetValueTwo();
    }
}

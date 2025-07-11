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
use Ibexa\Core\FieldType\Date\Type;
use Ibexa\Core\FieldType\Date\Value as DateValue;

/**
 * Integration test for use field type.
 *
 * @group integration
 * @group field-type
 */
class DateIntegrationTest extends SearchBaseIntegrationTestCase
{
    /**
     * Get name of tested field type.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'ibexa_date';
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
            'defaultType' => [
                'type' => 'choice',
                'default' => Type::DEFAULT_EMPTY,
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
            'defaultType' => Type::DEFAULT_EMPTY,
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
        return DateValue::fromTimestamp(86400);
    }

    /**
     * Get name generated by the given field type (via fieldType->getName()).
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return 'Friday 02 January 1970';
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
            DateValue::class,
            $field->value
        );

        $expectedData = [
            'date' => new DateTime('@86400'),
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
                'Some unknown date format',
                InvalidArgumentException::class,
            ],
        ];
    }

    /**
     * Get valid field data for updating content.
     *
     * @return mixed
     */
    public function getValidUpdateFieldData()
    {
        return DateValue::fromTimestamp(86400);
    }

    /**
     * Asserts the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidUpdateFieldData()}
     * was stored and loaded correctly.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field $field
     */
    public function assertUpdatedFieldDataLoadedCorrect(Field $field)
    {
        self::assertInstanceOf(
            DateValue::class,
            $field->value
        );

        $expectedData = [
            'date' => new DateTime('@86400'),
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
     * Asserts the the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was copied and loaded correctly.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field $field
     */
    public function assertCopiedFieldDataLoadedCorrectly(Field $field)
    {
        $this->assertFieldDataLoadedCorrect($field);
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
        $timestamp = 186401;
        $dateTime = new DateTime("@{$timestamp}");

        return [
            [
                DateValue::fromTimestamp($timestamp),
                [
                    'timestamp' => $dateTime->setTime(0, 0, 0)->getTimestamp(),
                    'rfc850' => $dateTime->format(DateTime::RFC850),
                ],
            ],
        ];
    }

    /**
     * Get hashes and their respective converted values.
     *
     * This is a PHPUnit data provider
     *
     * The returned records must have the the input hash assigned to the
     * first index and the expected value result to the second. For example:
     *
     * <code>
     * array(
     *      array(
     *          array( 'myValue' => true ),
     *          new MyValue( true ),
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array
     */
    public function provideFromHashData()
    {
        $timestamp = 123456;

        $dateTime = new DateTime("@{$timestamp}");
        $dateTime->setTime(0, 0, 0);

        return [
            [
                [
                    'timestamp' => $timestamp,
                    'rfc850' => ($rfc850 = $dateTime->format(DateTime::RFC850)),
                ],
                DateValue::fromString($rfc850),
            ],
            [
                [
                    'timestamp' => $dateTime->getTimestamp(),
                    'rfc850' => null,
                ],
                DateValue::fromTimestamp($timestamp),
            ],
        ];
    }

    public function providerForTestIsEmptyValue()
    {
        return [
            [new DateValue()],
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

    protected function getValidSearchValueOne(): int
    {
        return 86400;
    }

    protected function getValidSearchValueTwo(): int
    {
        return 172800;
    }

    protected function getSearchTargetValueOne()
    {
        // Handling Legacy Search Engine, which stores Date value as timestamp
        if ($this->getSetupFactory() instanceof Legacy) {
            return $this->getValidSearchValueOne();
        }

        return '1970-01-02T00:00:00Z';
    }

    protected function getSearchTargetValueTwo()
    {
        // Handling Legacy Search Engine, which stores Date value as timestamp
        if ($this->getSetupFactory() instanceof Legacy) {
            return $this->getValidSearchValueTwo();
        }

        return '1970-01-03T00:00:00Z';
    }
}

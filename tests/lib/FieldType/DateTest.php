<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use DateTime;
use DateTimeZone;
use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Date\Type as Date;
use Ibexa\Core\FieldType\Date\Value as DateValue;

/**
 * @group fieldType
 * @group ibexa_date
 */
class DateTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): Date
    {
        $fieldType = new Date();
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
            'defaultType' => [
                'type' => 'choice',
                'default' => Date::DEFAULT_EMPTY,
            ],
        ];
    }

    protected function getEmptyValueExpectation(): DateValue
    {
        return new DateValue();
    }

    public function provideInvalidInputForAcceptValue(): array
    {
        return [
            [
                [],
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): array
    {
        return [
            [
                null,
                new DateValue(),
            ],
            [
                ($dateString = '2012-08-28 12:20 EST'),
                new DateValue(new DateTime($dateString)),
            ],
            [
                ($timestamp = 1346149200),
                new DateValue(
                    new DateTime("@{$timestamp}")
                ),
            ],
            [
                DateValue::fromTimestamp($timestamp = 1372895999),
                new DateValue(new DateTime("@{$timestamp}")),
            ],
            [
                ($dateTime = new DateTime()),
                new DateValue($dateTime),
            ],
        ];
    }

    public function provideInputForToHash(): array
    {
        return [
            [
                new DateValue(),
                null,
            ],
            [
                new DateValue($dateTime = new DateTime()),
                [
                    'timestamp' => $dateTime->setTime(0, 0, 0)->getTimestamp(),
                    'rfc850' => $dateTime->format(DateTime::RFC850),
                ],
            ],
        ];
    }

    public function provideInputForFromHash(): array
    {
        $dateTime = new DateTime();

        return [
            [
                null,
                new DateValue(),
            ],
            [
                [
                    'timestamp' => ($timestamp = 1362614400),
                ],
                new DateValue(new DateTime("@{$timestamp}")),
            ],
            [
                [
                    // Timezone is not abbreviated because PHP converts it to non-abbreviated local name,
                    // but this is sufficient to test conversion
                    'rfc850' => 'Thursday, 04-Jul-13 23:59:59 Europe/Zagreb',
                ],
                new DateValue(
                    $dateTime
                        ->setTimeZone(new DateTimeZone('Europe/Zagreb'))
                        ->setTimestamp(1372896000)
                ),
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
                    'defaultType' => Date::DEFAULT_EMPTY,
                ],
            ],
            [
                [
                    'defaultType' => Date::DEFAULT_CURRENT_DATE,
                ],
            ],
        ];
    }

    public function provideInValidFieldSettings(): array
    {
        return [
            [
                [
                    // non-existent setting
                    'useSeconds' => 23,
                ],
            ],
            [
                [
                    // defaultType must be constant
                    'defaultType' => 42,
                ],
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_date';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new DateValue(new DateTime('11/24/1983')), 'Thursday 24 November 1983', [], 'en_GB'],
        ];
    }
}

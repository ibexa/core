<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use DateTime;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Time\Type as Time;
use Ibexa\Core\FieldType\Time\Value as TimeValue;

/**
 * @group fieldType
 * @group ibexa_time
 */
class TimeTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): Time
    {
        $fieldType = new Time();
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
            'useSeconds' => [
                'type' => 'bool',
                'default' => false,
            ],
            'defaultType' => [
                'type' => 'choice',
                'default' => Time::DEFAULT_EMPTY,
            ],
        ];
    }

    protected function getEmptyValueExpectation(): TimeValue
    {
        return new TimeValue();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                [],
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        $dateTime = new DateTime();
        // change timezone to UTC (+00:00) to be able to calculate proper TimeValue
        $timestamp = $dateTime->setTimezone(new \DateTimeZone('UTC'))->getTimestamp();

        yield 'null input' => [
            null,
            new TimeValue(),
        ];

        yield 'date string without timezone' => [
            '2012-08-28 12:20',
            new TimeValue(44400),
        ];

        yield 'date string with Europe timezone' => [
            '2012-08-28 12:20 Europe/Berlin',
            new TimeValue(44400),
        ];

        yield 'date string with Asia timezone' => [
            '2012-08-28 12:20 Asia/Sakhalin',
            new TimeValue(44400),
        ];

        yield 'timestamp from unix epoch' => [
            (new DateTime('@1372896001'))->getTimestamp(),
            new TimeValue(1),
        ];

        yield 'TimeValue from timestamp' => [
            TimeValue::fromTimestamp($timestamp),
            new TimeValue(
                $timestamp - $dateTime->setTime(0, 0, 0)->getTimestamp()
            ),
        ];

        yield 'DateTime object' => [
            clone $dateTime,
            new TimeValue(
                $dateTime->getTimestamp() - $dateTime->setTime(0, 0, 0)->getTimestamp()
            ),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new TimeValue(),
                null,
            ],
            [
                new TimeValue(0),
                0,
            ],
            [
                new TimeValue(200),
                200,
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                null,
                new TimeValue(),
            ],
            [
                0,
                new TimeValue(0),
            ],
            [
                200,
                new TimeValue(200),
            ],
        ];
    }

    public function provideValidFieldSettings(): iterable
    {
        return [
            [
                [],
            ],
            [
                [
                    'useSeconds' => true,
                    'defaultType' => Time::DEFAULT_EMPTY,
                ],
            ],
            [
                [
                    'useSeconds' => false,
                    'defaultType' => Time::DEFAULT_CURRENT_TIME,
                ],
            ],
        ];
    }

    public function provideInValidFieldSettings(): array
    {
        return [
            [
                [
                    // useSeconds must be bool
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
        return 'ibexa_time';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new TimeValue(200), '12:03:20 am', [], 'en_GB'],
        ];
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use DateTime;
use Ibexa\Contracts\Core\FieldType\FieldType;
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
        $dateTime = new DateTime();
        // change timezone to UTC (+00:00) to be able to calculate proper TimeValue
        $timestamp = $dateTime->setTimezone(new \DateTimeZone('UTC'))->getTimestamp();

        return [
            [
                null,
                new TimeValue(),
            ],
            [
                '2012-08-28 12:20',
                new TimeValue(44400),
            ],
            [
                '2012-08-28 12:20 Europe/Berlin',
                new TimeValue(44400),
            ],
            [
                '2012-08-28 12:20 Asia/Sakhalin',
                new TimeValue(44400),
            ],
            [
                // create new DateTime object from timestamp w/o taking into account server timezone
                (new DateTime('@1372896001'))->getTimestamp(),
                new TimeValue(1),
            ],
            [
                TimeValue::fromTimestamp($timestamp),
                new TimeValue(
                    $timestamp - $dateTime->setTime(0, 0, 0)->getTimestamp()
                ),
            ],
            [
                clone $dateTime,
                new TimeValue(
                    $dateTime->getTimestamp() - $dateTime->setTime(0, 0, 0)->getTimestamp()
                ),
            ],
        ];
    }

    public function provideInputForToHash(): array
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

    public function provideInputForFromHash(): array
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

    public function provideValidFieldSettings(): array
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

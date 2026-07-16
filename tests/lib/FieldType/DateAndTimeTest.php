<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use DateInterval;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\DateAndTime\Type;
use Ibexa\Core\FieldType\DateAndTime\Type as DateAndTime;
use Ibexa\Core\FieldType\DateAndTime\Value;
use Ibexa\Core\FieldType\DateAndTime\Value as DateAndTimeValue;
use stdClass;

/**
 * @group fieldType
 * @group ibexa_datetime
 */
class DateAndTimeTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): DateAndTime
    {
        $fieldType = new DateAndTime();
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
                'default' => DateAndTime::DEFAULT_EMPTY,
            ],
            'dateInterval' => [
                'type' => 'dateInterval',
                'default' => null,
            ],
        ];
    }

    protected function getEmptyValueExpectation(): DateAndTimeValue
    {
        return new DateAndTimeValue();
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
        yield 'null input' => [
            null,
            new DateAndTimeValue(),
        ];

        yield 'date string' => [
            '2012-08-28 12:20 Europe/Berlin',
            DateAndTimeValue::fromString('2012-08-28 12:20 Europe/Berlin'),
        ];

        yield 'timestamp' => [
            1346149200,
            DateAndTimeValue::fromTimestamp(1346149200),
        ];

        yield 'DateTime object' => [
            ($dateTime = new \DateTime()),
            new DateAndTimeValue($dateTime),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new DateAndTimeValue(),
                null,
            ],
            [
                new DateAndTimeValue($date = new \DateTime('Tue, 28 Aug 2012 12:20:00 +0200')),
                [
                    'rfc850' => $date->format(\DateTime::RFC850),
                    'timestamp' => $date->getTimeStamp(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideInputForFromHash
     *
     * @param Value $expectedResult
     */
    public function testFromHash(
        mixed $inputHash,
        mixed $expectedResult
    ): void {
        $this->assertIsValidHashValue($inputHash);

        /** @var Type $fieldType */
        $fieldType = $this->getFieldTypeUnderTest();

        $actualResult = $fieldType->fromHash($inputHash);

        // Tests may run slowly. Allow 20 seconds margin of error.
        self::assertGreaterThanOrEqual(
            $expectedResult,
            $actualResult,
            'fromHash() method did not create expected result.'
        );
        if ($expectedResult->value !== null) {
            self::assertLessThan(
                $expectedResult->value->add(new DateInterval('PT20S')),
                $actualResult->value,
                'fromHash() method did not create expected result.'
            );
        }
    }

    public function provideInputForFromHash(): iterable
    {
        $date = new \DateTime('Tue, 28 Aug 2012 12:20:00 +0200');

        return [
            [
                null,
                new DateAndTimeValue(),
            ],
            [
                [
                    'rfc850' => $date->format(\DateTime::RFC850),
                    'timestamp' => $date->getTimeStamp(),
                ],
                new DateAndTimeValue($date),
            ],
            [
                [
                    'timestamp' => $date->getTimeStamp(),
                ],
                DateAndTimeValue::fromTimestamp($date->getTimeStamp()),
            ],
        ];
    }

    /**
     * @dataProvider provideInputForTimeStringFromHash
     *
     * @throws \DateMalformedIntervalStringException
     */
    public function testTimeStringFromHash(
        mixed $inputHash,
        string $intervalSpec
    ): void {
        $this->assertIsValidHashValue($inputHash);

        /** @var Type $fieldType */
        $fieldType = $this->getFieldTypeUnderTest();

        $expectedResult = new DateAndTimeValue(new \DateTime());
        self::assertNotNull($expectedResult->value);
        $expectedResult->value->add(new DateInterval($intervalSpec));

        $actualResult = $fieldType->fromHash($inputHash);

        // Tests may run slowly. Allow 20 seconds margin of error.
        self::assertGreaterThanOrEqual(
            $expectedResult,
            $actualResult,
            'fromHash() method did not create expected result.'
        );
        if ($expectedResult->value !== null) {
            self::assertLessThan(
                $expectedResult->value->add(new DateInterval('PT20S')),
                $actualResult->value,
                'fromHash() method did not create expected result.'
            );
        }
    }

    /**
     * Provide input to testTimeStringFromHash() method.
     *
     * Returns an array of data provider sets with 2 arguments: 1. A valid
     * time string input to fromHash(), 2. An interval specification string,
     * from which can be created a DateInterval which can be added to the
     * current DateTime, to be compared with the expected return value from
     * fromHash().
     *
     * @phpstan-return array<array{mixed, string}>
     */
    public function provideInputForTimeStringFromHash(): array
    {
        return [
            [
                [
                    'timestring' => 'now',
                ],
                'P0Y',
            ],
            [
                [
                    'timestring' => '+42 seconds',
                ],
                'PT42S',
            ],
            [
                [
                    'timestring' => '+3 months 2 days 5 hours',
                ],
                'P3M2DT5H',
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
                    'defaultType' => DateAndTime::DEFAULT_EMPTY,
                ],
            ],
            [
                [
                    'useSeconds' => false,
                    'defaultType' => DateAndTime::DEFAULT_CURRENT_DATE,
                ],
            ],
            [
                [
                    'useSeconds' => false,
                    'defaultType' => DateAndTime::DEFAULT_CURRENT_DATE_ADJUSTED,
                    'dateInterval' => new DateInterval('P2Y'),
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
            [
                [
                    // No dateInterval allowed with this defaultType
                    'defaultType' => DateAndTime::DEFAULT_EMPTY,
                    'dateInterval' => new DateInterval('P2Y'),
                ],
            ],
            [
                [
                    // dateInterval must be a \DateInterval
                    'defaultType' => DateAndTime::DEFAULT_CURRENT_DATE_ADJUSTED,
                    'dateInterval' => new stdClass(),
                ],
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_datetime';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [DateAndTimeValue::fromTimestamp(438512400), 'Thu 1983-24-11 09:00:00', [], 'en_GB'],
        ];
    }
}

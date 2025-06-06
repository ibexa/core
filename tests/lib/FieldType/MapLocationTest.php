<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\MapLocation;

class MapLocationTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): MapLocation\Type
    {
        $fieldType = new MapLocation\Type();
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

    protected function getEmptyValueExpectation(): MapLocation\Value
    {
        return new MapLocation\Value();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                'some string',
                InvalidArgumentException::class,
            ],
            [
                new MapLocation\Value(
                    [
                        'latitude' => 'foo',
                    ]
                ),
                InvalidArgumentException::class,
            ],
            [
                new MapLocation\Value(
                    [
                        'latitude' => 23.42,
                        'longitude' => 'bar',
                    ]
                ),
                InvalidArgumentException::class,
            ],
            [
                new MapLocation\Value(
                    [
                        'latitude' => 23.42,
                        'longitude' => 42.23,
                        'address' => [],
                    ]
                ),
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null input' => [
            null,
            new MapLocation\Value(),
        ];

        yield 'empty array' => [
            [],
            new MapLocation\Value(),
        ];

        yield 'empty MapLocation object' => [
            new MapLocation\Value(),
            new MapLocation\Value(),
        ];

        yield 'array with float coordinates' => [
            [
                'latitude' => 23.42,
                'longitude' => 42.23,
                'address' => 'Nowhere',
            ],
            new MapLocation\Value(
                [
                    'latitude' => 23.42,
                    'longitude' => 42.23,
                    'address' => 'Nowhere',
                ]
            ),
        ];

        yield 'array with integer coordinates' => [
            [
                'latitude' => 23,
                'longitude' => 42,
                'address' => 'Somewhere',
            ],
            new MapLocation\Value(
                [
                    'latitude' => 23,
                    'longitude' => 42,
                    'address' => 'Somewhere',
                ]
            ),
        ];

        yield 'MapLocation object with data' => [
            new MapLocation\Value(
                [
                    'latitude' => 23.42,
                    'longitude' => 42.23,
                    'address' => 'Nowhere',
                ]
            ),
            new MapLocation\Value(
                [
                    'latitude' => 23.42,
                    'longitude' => 42.23,
                    'address' => 'Nowhere',
                ]
            ),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new MapLocation\Value(),
                null,
            ],
            [
                new MapLocation\Value(
                    [
                        'latitude' => 23.42,
                        'longitude' => 42.23,
                        'address' => 'Nowhere',
                    ]
                ),
                [
                    'latitude' => 23.42,
                    'longitude' => 42.23,
                    'address' => 'Nowhere',
                ],
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                null,
                new MapLocation\Value(),
            ],
            [
                [
                    'latitude' => 23.42,
                    'longitude' => 42.23,
                    'address' => 'Nowhere',
                ],
                new MapLocation\Value(
                    [
                        'latitude' => 23.42,
                        'longitude' => 42.23,
                        'address' => 'Nowhere',
                    ]
                ),
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_gmap_location';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new MapLocation\Value(['address' => 'Bag End, The Shire']), 'Bag End, The Shire', [], 'en_GB'],
        ];
    }
}

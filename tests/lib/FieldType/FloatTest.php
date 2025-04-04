<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Float\Type as FloatType;
use Ibexa\Core\FieldType\Float\Value as FloatValue;
use Ibexa\Core\FieldType\ValidationError;

/**
 * @group fieldType
 * @group ibexa_float
 */
class FloatTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): FloatType
    {
        $fieldType = new FloatType();
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [
            'FloatValueValidator' => [
                'minFloatValue' => [
                    'type' => 'float',
                    'default' => null,
                ],
                'maxFloatValue' => [
                    'type' => 'float',
                    'default' => null,
                ],
            ],
        ];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    protected function getEmptyValueExpectation(): FloatValue
    {
        return new FloatValue();
    }

    public function provideInvalidInputForAcceptValue(): array
    {
        return [
            [
                'foo',
                InvalidArgumentException::class,
            ],
            [
                [],
                InvalidArgumentException::class,
            ],
            [
                new FloatValue('foo'),
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): array
    {
        return [
            [
                null,
                new FloatValue(),
            ],
            [
                42.23,
                new FloatValue(42.23),
            ],
            [
                23,
                new FloatValue(23.),
            ],
            [
                new FloatValue(23.42),
                new FloatValue(23.42),
            ],
            [
                '42.23',
                new FloatValue(42.23),
            ],
            [
                '23',
                new FloatValue(23.),
            ],
        ];
    }

    public function provideInputForToHash(): array
    {
        return [
            [
                new FloatValue(),
                null,
            ],
            [
                new FloatValue(23.42),
                23.42,
            ],
        ];
    }

    public function provideInputForFromHash(): array
    {
        return [
            [
                null,
                new FloatValue(),
            ],
            [
                23.42,
                new FloatValue(23.42),
            ],
        ];
    }

    public function provideValidValidatorConfiguration(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'FloatValueValidator' => [
                        'minFloatValue' => null,
                    ],
                ],
            ],
            [
                [
                    'FloatValueValidator' => [
                        'minFloatValue' => .23,
                    ],
                ],
            ],
            [
                [
                    'FloatValueValidator' => [
                        'maxFloatValue' => null,
                    ],
                ],
            ],
            [
                [
                    'FloatValueValidator' => [
                        'maxFloatValue' => .23,
                    ],
                ],
            ],
            [
                [
                    'FloatValueValidator' => [
                        'minFloatValue' => .23,
                        'maxFloatValue' => .42,
                    ],
                ],
            ],
        ];
    }

    public function provideInvalidValidatorConfiguration(): array
    {
        return [
            [
                [
                    'NonExistentValidator' => [],
                ],
            ],
            [
                [
                    'FloatValueValidator' => [
                        'nonExistentValue' => .23,
                    ],
                ],
            ],
            [
                [
                    'FloatValueValidator' => [
                        'minFloatValue' => 'foo',
                    ],
                ],
            ],
            [
                [
                    'FloatValueValidator' => [
                        'maxFloatValue' => 'bar',
                    ],
                ],
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_float';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new FloatValue(23.42), '23.42', [], 'en_GB'],
        ];
    }

    public function provideValidDataForValidate(): array
    {
        return [
            [
                [
                    'validatorConfiguration' => [
                        'FloatValueValidator' => [
                            'minFloatValue' => 5.1,
                            'maxFloatValue' => 10.5,
                        ],
                    ],
                ],
                new FloatValue(7.5),
            ],
        ];
    }

    public function provideInvalidDataForValidate(): array
    {
        return [
            [
                [
                    'validatorConfiguration' => [
                        'FloatValueValidator' => [
                            'minFloatValue' => 5.1,
                            'maxFloatValue' => 10.5,
                        ],
                    ],
                ],
                new FloatValue(3.2),
                [
                    new ValidationError(
                        'The value can not be lower than %size%.',
                        null,
                        [
                            '%size%' => 5.1,
                        ],
                        'value'
                    ),
                ],
            ],
            [
                [
                    'validatorConfiguration' => [
                        'FloatValueValidator' => [
                            'minFloatValue' => 5.1,
                            'maxFloatValue' => 10.5,
                        ],
                    ],
                ],
                new FloatValue(13.2),
                [
                    new ValidationError(
                        'The value can not be higher than %size%.',
                        null,
                        [
                            '%size%' => 10.5,
                        ],
                        'value'
                    ),
                ],
            ],
            [
                [
                    'validatorConfiguration' => [
                        'FloatValueValidator' => [
                            'minFloatValue' => 10.5,
                            'maxFloatValue' => 5.1,
                        ],
                    ],
                ],
                new FloatValue(7.5),
                [
                    new ValidationError(
                        'The value can not be higher than %size%.',
                        null,
                        [
                            '%size%' => 5.1,
                        ],
                        'value'
                    ),
                    new ValidationError(
                        'The value can not be lower than %size%.',
                        null,
                        [
                            '%size%' => 10.5,
                        ],
                        'value'
                    ),
                ],
            ],
        ];
    }
}

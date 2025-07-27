<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Integer\Type as IntegerType;
use Ibexa\Core\FieldType\Integer\Value as IntegerValue;
use Ibexa\Core\FieldType\ValidationError;

/**
 * @group fieldType
 * @group ibexa_integer
 */
class IntegerTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): IntegerType
    {
        $fieldType = new IntegerType();
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [
            'IntegerValueValidator' => [
                'minIntegerValue' => [
                    'type' => 'int',
                    'default' => null,
                ],
                'maxIntegerValue' => [
                    'type' => 'int',
                    'default' => null,
                ],
            ],
        ];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    protected function getEmptyValueExpectation(): IntegerValue
    {
        return new IntegerValue();
    }

    public function provideInvalidInputForAcceptValue(): iterable
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
                new IntegerValue('foo'),
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null input' => [
            null,
            new IntegerValue(),
        ];

        yield 'integer value 42' => [
            42,
            new IntegerValue(42),
        ];

        yield 'integer value 23' => [
            23,
            new IntegerValue(23),
        ];

        yield 'IntegerValue object' => [
            new IntegerValue(23),
            new IntegerValue(23),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new IntegerValue(),
                null,
            ],
            [
                new IntegerValue(42),
                42,
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                null,
                new IntegerValue(),
            ],
            [
                42,
                new IntegerValue(42),
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
                    'IntegerValueValidator' => [
                        'minIntegerValue' => null,
                    ],
                ],
            ],
            [
                [
                    'IntegerValueValidator' => [
                        'minIntegerValue' => 23,
                    ],
                ],
            ],
            [
                [
                    'IntegerValueValidator' => [
                        'maxIntegerValue' => null,
                    ],
                ],
            ],
            [
                [
                    'IntegerValueValidator' => [
                        'maxIntegerValue' => 23,
                    ],
                ],
            ],
            [
                [
                    'IntegerValueValidator' => [
                        'minIntegerValue' => 23,
                        'maxIntegerValue' => 42,
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
                    'IntegerValueValidator' => [
                        'nonExistentValue' => 23,
                    ],
                ],
            ],
            [
                [
                    'IntegerValueValidator' => [
                        'minIntegerValue' => .23,
                    ],
                ],
            ],
            [
                [
                    'IntegerValueValidator' => [
                        'maxIntegerValue' => .42,
                    ],
                ],
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_integer';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new IntegerValue(42), '42', [], 'en_GB'],
        ];
    }

    public function provideValidDataForValidate(): iterable
    {
        yield 'value within range' => [
            [
                'validatorConfiguration' => [
                    'IntegerValueValidator' => [
                        'minIntegerValue' => 5,
                        'maxIntegerValue' => 10,
                    ],
                ],
            ],
            new IntegerValue(7),
        ];
    }

    public function provideInvalidDataForValidate(): iterable
    {
        yield 'value below minimum' => [
            [
                'validatorConfiguration' => [
                    'IntegerValueValidator' => [
                        'minIntegerValue' => 5,
                        'maxIntegerValue' => 10,
                    ],
                ],
            ],
            new IntegerValue(3),
            [
                new ValidationError(
                    'The value can not be lower than %size%.',
                    null,
                    [
                        '%size%' => 5,
                    ],
                    'value'
                ),
            ],
        ];

        yield 'value above maximum' => [
            [
                'validatorConfiguration' => [
                    'IntegerValueValidator' => [
                        'minIntegerValue' => 5,
                        'maxIntegerValue' => 10,
                    ],
                ],
            ],
            new IntegerValue(13),
            [
                new ValidationError(
                    'The value can not be higher than %size%.',
                    null,
                    [
                        '%size%' => 10,
                    ],
                    'value'
                ),
            ],
        ];

        yield 'value outside reversed range' => [
            [
                'validatorConfiguration' => [
                    'IntegerValueValidator' => [
                        'minIntegerValue' => 10,
                        'maxIntegerValue' => 5,
                    ],
                ],
            ],
            new IntegerValue(7),
            [
                new ValidationError(
                    'The value can not be higher than %size%.',
                    null,
                    [
                        '%size%' => 5,
                    ],
                    'value'
                ),
                new ValidationError(
                    'The value can not be lower than %size%.',
                    null,
                    [
                        '%size%' => 10,
                    ],
                    'value'
                ),
            ],
        ];
    }
}

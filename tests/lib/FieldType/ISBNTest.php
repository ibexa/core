<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\ISBN\Type as ISBN;
use Ibexa\Core\FieldType\ISBN\Value as ISBNValue;
use Ibexa\Core\FieldType\ValidationError;

/**
 * @group fieldType
 * @group ibexa_isbn
 */
class ISBNTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): ISBN
    {
        $fieldType = new ISBN();
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
            'isISBN13' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ];
    }

    protected function getEmptyValueExpectation(): ISBNValue
    {
        return new ISBNValue();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                1234567890,
                InvalidArgumentException::class,
            ],
            [
                [],
                InvalidArgumentException::class,
            ],
            [
                new \stdClass(),
                InvalidArgumentException::class,
            ],
            [
                44.55,
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'ISBN-13 standard' => [
            '9789722514095',
            new ISBNValue('9789722514095'),
        ];

        yield 'ISBN-13 with dashes' => [
            '978-972-25-1409-5',
            new ISBNValue('978-972-25-1409-5'),
        ];

        yield 'ISBN-10 with X checksum' => [
            '0-9752298-0-X',
            new ISBNValue('0-9752298-0-X'),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new ISBNValue('9789722514095'),
                '9789722514095',
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                '9789722514095',
                new ISBNValue('9789722514095'),
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_isbn';
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new ISBNValue('9789722514095'), '9789722514095', [], 'en_GB'],
        ];
    }

    public function provideValidDataForValidate(): iterable
    {
        yield 'empty value with ISBN-13' => [
            [
                'fieldSettings' => [
                    'isISBN13' => true,
                ],
            ],
            new ISBNValue(),
        ];

        yield 'empty value with ISBN-10' => [
            [
                'fieldSettings' => [
                    'isISBN13' => false,
                ],
            ],
            new ISBNValue(),
        ];

        yield 'valid ISBN-13' => [
            [
                'fieldSettings' => [
                    'isISBN13' => true,
                ],
            ],
            new ISBNValue('9789722514095'),
        ];

        yield 'valid ISBN-10' => [
            [
                'fieldSettings' => [
                    'isISBN13' => false,
                ],
            ],
            new ISBNValue('0-9752298-0-X'),
        ];
    }

    public function provideInvalidDataForValidate(): iterable
    {
        yield 'ISBN-13 when ISBN-10 required' => [
            [
                'fieldSettings' => [
                    'isISBN13' => false,
                ],
            ],
            new ISBNValue('9789722514095'),
            [
                new ValidationError('ISBN-10 must be 10 character length', null, [], 'isbn'),
            ],
        ];
    }
}

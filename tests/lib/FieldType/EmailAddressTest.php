<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\EmailAddress\Type as EmailAddressType;
use Ibexa\Core\FieldType\EmailAddress\Value as EmailAddressValue;
use Ibexa\Core\FieldType\ValidationError;

/**
 * @group fieldType
 * @group ibexa_email
 */
class EmailAddressTest extends FieldTypeTestCase
{
    protected function createFieldTypeUnderTest(): EmailAddressType
    {
        $transformationProcessorMock = $this->getTransformationProcessorMock();

        $transformationProcessorMock
            ->method('transformByGroup')
            ->with(self::anything(), 'lowercase')
            ->willReturnCallback(
                static function ($value, $group): string {
                    return strtolower($value);
                }
            );

        $fieldType = new EmailAddressType();
        $fieldType->setTransformationProcessor($transformationProcessorMock);

        return $fieldType;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [
            'EmailAddressValidator' => [],
        ];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    protected function getEmptyValueExpectation(): EmailAddressValue
    {
        return new EmailAddressValue();
    }

    public function provideInvalidInputForAcceptValue(): array
    {
        return [
            [
                23,
                InvalidArgumentException::class,
            ],
            [
                new EmailAddressValue(23),
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): array
    {
        return [
            [
                null,
                new EmailAddressValue(),
            ],
            [
                'spam_mail@ex-something.no',
                new EmailAddressValue('spam_mail@ex-something.no'),
            ],
            [
                new EmailAddressValue('spam_mail@ex-something.no'),
                new EmailAddressValue('spam_mail@ex-something.no'),
            ],
        ];
    }

    public function provideInputForToHash(): array
    {
        return [
            [
                new EmailAddressValue(),
                null,
            ],
            [
                new EmailAddressValue('spam_mail@ex-something.no'),
                'spam_mail@ex-something.no',
            ],
        ];
    }

    public function provideInputForFromHash(): array
    {
        return [
            [
                null,
                new EmailAddressValue(),
            ],
            [
                '',
                new EmailAddressValue(),
            ],
            [
                'spam_mail@ex-something.no',
                new EmailAddressValue('spam_mail@ex-something.no'),
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
                    'EmailAddressValidator' => [],
                ],
            ],
            [
                [
                    'EmailAddressValidator' => [
                        'Extent' => 'regex',
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
                    'EmailAddressValidator' => [
                        'Extent' => 23,
                    ],
                ],
            ],
            [
                [
                    'EmailAddressValidator' => [
                        'Extent' => '',
                    ],
                ],
            ],
            [
                [
                    'EmailAddressValidator' => [
                        'Extent' => '\\http\\',
                    ],
                ],
            ],
        ];
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_email';
    }

    public function provideDataForGetName(): array
    {
        return [
            [new EmailAddressValue('john.doe@example.com'), 'john.doe@example.com', [], 'en_GB'],
            [new EmailAddressValue('JANE.DOE@EXAMPLE.COM'), 'jane.doe@example.com', [], 'en_GB'],
        ];
    }

    public function provideValidDataForValidate(): array
    {
        return [
            [
                [
                    'validatorConfiguration' => [],
                ],
                new EmailAddressValue('jane.doe@example.com'),
            ],
        ];
    }

    public function provideInvalidDataForValidate(): array
    {
        return [
            [
                [
                    'validatorConfiguration' => [],
                ],
                new EmailAddressValue('jane.doe.example.com'),
                [
                    new ValidationError('The value must be a valid email address.', null, [], 'email'),
                ],
            ],
        ];
    }
}

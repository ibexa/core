<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Core\FieldType\EmailAddress\Value as EmailAddressValue;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Validator\EmailAddressValidator;
use PHPUnit\Framework\TestCase;

/**
 * @todo add more tests, like on validateConstraints method
 *
 * @group fieldType
 * @group validator
 *
 * @covers \Ibexa\Core\FieldType\Validator\EmailAddressValidator
 */
class EmailAddressValidatorTest extends TestCase
{
    public function testConstructor(): void
    {
        self::assertInstanceOf(
            Validator::class,
            new EmailAddressValidator()
        );
    }

    /**
     * Tests setting and getting constraints.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException
     */
    public function testConstraintsInitializeGet(): void
    {
        $constraints = [
            'Extent' => 'regex',
        ];
        $validator = new EmailAddressValidator();
        $validator->initializeWithConstraints(
            $constraints
        );
        self::assertSame($constraints['Extent'], $validator->Extent);
    }

    public function testGetConstraintsSchema(): void
    {
        $constraintsSchema = [
            'Extent' => [
                'type' => 'string',
                'default' => 'regex',
            ],
        ];
        $validator = new EmailAddressValidator();
        self::assertSame($constraintsSchema, $validator->getConstraintsSchema());
    }

    public function testConstraintsSetGet(): void
    {
        $constraints = [
            'Extent' => 'regex',
        ];
        $validator = new EmailAddressValidator();
        $validator->Extent = $constraints['Extent'];
        self::assertSame($constraints['Extent'], $validator->Extent);
    }

    public function testValidateCorrectEmailAddresses(): void
    {
        $validator = new EmailAddressValidator();
        $validator->Extent = 'regex';
        $emailAddresses = ['john.doe@example.com', 'Info@Ibexa.Co'];
        foreach ($emailAddresses as $value) {
            self::assertTrue($validator->validate(new EmailAddressValue($value)));
            self::assertSame([], $validator->getMessage());
        }
    }

    /**
     * Tests validating a wrong value.
     */
    public function testValidateWrongEmailAddresses(): void
    {
        $validator = new EmailAddressValidator();
        $validator->Extent = 'regex';
        $emailAddresses = ['.john.doe@example.com', 'Info-at-Ibexa.Co'];
        foreach ($emailAddresses as $value) {
            self::assertFalse($validator->validate(new EmailAddressValue($value)));
        }
    }
}

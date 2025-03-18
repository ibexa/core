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
 */
class EmailAddressValidatorTest extends TestCase
{
    /**
     * This test ensure an EmailAddressValidator can be created.
     */
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
     * @covers \Ibexa\Core\FieldType\Validator::initializeWithConstraints
     * @covers \Ibexa\Core\FieldType\Validator::__get
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

    /**
     * Test getting constraints schema.
     *
     * @covers \Ibexa\Core\FieldType\Validator::getConstraintsSchema
     */
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

    /**
     * Tests setting and getting constraints.
     *
     * @covers \Ibexa\Core\FieldType\Validator::__set
     * @covers \Ibexa\Core\FieldType\Validator::__get
     */
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
     *
     * @covers \Ibexa\Core\FieldType\Validator\EmailAddressValidator::validate
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

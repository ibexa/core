<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\ValidationError;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Translation\Message;
use Ibexa\Core\FieldType\Float\Value as FloatValue;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Validator\FloatValueValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group fieldType
 * @group validator
 */
class FloatValueValidatorTest extends TestCase
{
    /**
     * @return float
     */
    protected function getMinFloatValue()
    {
        return 10 / 7;
    }

    /**
     * @return float
     */
    protected function getMaxFloatValue()
    {
        return 11 / 7;
    }

    /**
     * This test ensure an FloatValueValidator can be created.
     */
    public function testConstructor()
    {
        self::assertInstanceOf(
            Validator::class,
            new FloatValueValidator()
        );
    }

    /**
     * Tests setting and getting constraints.
     *
     * @covers \Ibexa\Core\FieldType\Validator::initializeWithConstraints
     * @covers \Ibexa\Core\FieldType\Validator::__get
     */
    public function testConstraintsInitializeGet()
    {
        $constraints = [
            'minFloatValue' => 0.5,
            'maxFloatValue' => 22 / 7,
        ];
        $validator = new FloatValueValidator();
        $validator->initializeWithConstraints(
            $constraints
        );
        self::assertSame($constraints['minFloatValue'], $validator->minFloatValue);
        self::assertSame($constraints['maxFloatValue'], $validator->maxFloatValue);
    }

    /**
     * Test getting constraints schema.
     *
     * @covers \Ibexa\Core\FieldType\Validator::getConstraintsSchema
     */
    public function testGetConstraintsSchema()
    {
        $constraintsSchema = [
            'minFloatValue' => [
                'type' => 'float',
                'default' => null,
            ],
            'maxFloatValue' => [
                'type' => 'float',
                'default' => null,
            ],
        ];
        $validator = new FloatValueValidator();
        self::assertSame($constraintsSchema, $validator->getConstraintsSchema());
    }

    /**
     * Tests setting and getting constraints.
     *
     * @covers \Ibexa\Core\FieldType\Validator::__set
     * @covers \Ibexa\Core\FieldType\Validator::__get
     */
    public function testConstraintsSetGet()
    {
        $constraints = [
            'minFloatValue' => 0.5,
            'maxFloatValue' => 22 / 7,
        ];
        $validator = new FloatValueValidator();
        $validator->minFloatValue = $constraints['minFloatValue'];
        $validator->maxFloatValue = $constraints['maxFloatValue'];
        self::assertSame($constraints['minFloatValue'], $validator->minFloatValue);
        self::assertSame($constraints['maxFloatValue'], $validator->maxFloatValue);
    }

    /**
     * Tests initializing with a wrong constraint.
     *
     * @covers \Ibexa\Core\FieldType\Validator::initializeWithConstraints
     */
    public function testInitializeBadConstraint()
    {
        $this->expectException(PropertyNotFoundException::class);

        $constraints = [
            'unexisting' => 0,
        ];
        $validator = new FloatValueValidator();
        $validator->initializeWithConstraints(
            $constraints
        );
    }

    /**
     * Tests setting a wrong constraint.
     *
     * @covers \Ibexa\Core\FieldType\Validator::__set
     */
    public function testSetBadConstraint()
    {
        $this->expectException(PropertyNotFoundException::class);

        $validator = new FloatValueValidator();
        $validator->unexisting = 0;
    }

    /**
     * Tests getting a wrong constraint.
     *
     * @covers \Ibexa\Core\FieldType\Validator::__get
     */
    public function testGetBadConstraint()
    {
        $this->expectException(PropertyNotFoundException::class);

        $validator = new FloatValueValidator();
        $null = $validator->unexisting;
    }

    /**
     * Tests validating a correct value.
     *
     * @dataProvider providerForValidateOK
     *
     * @covers \Ibexa\Core\FieldType\Validator\FloatValueValidator::validate
     * @covers \Ibexa\Core\FieldType\Validator::getMessage
     */
    public function testValidateCorrectValues($value)
    {
        $validator = new FloatValueValidator();
        $validator->minFloatValue = 10 / 7;
        $validator->maxFloatValue = 11 / 7;
        self::assertTrue($validator->validate(new FloatValue($value)));
        self::assertSame([], $validator->getMessage());
    }

    public function providerForValidateOK()
    {
        return [
            [100 / 70],
            [101 / 70],
            [105 / 70],
            [109 / 70],
            [110 / 70],
        ];
    }

    /**
     * Tests validating a wrong value.
     *
     * @dataProvider providerForValidateKO
     *
     * @covers \Ibexa\Core\FieldType\Validator\FloatValueValidator::validate
     */
    public function testValidateWrongValues($value, $message, $values)
    {
        $validator = new FloatValueValidator();
        $validator->minFloatValue = $this->getMinFloatValue();
        $validator->maxFloatValue = $this->getMaxFloatValue();
        self::assertFalse($validator->validate(new FloatValue($value)));
        $messages = $validator->getMessage();
        self::assertCount(1, $messages);
        self::assertInstanceOf(
            ValidationError::class,
            $messages[0]
        );
        self::assertInstanceOf(
            Message::class,
            $messages[0]->getTranslatableMessage()
        );
        self::assertEquals(
            $message,
            $messages[0]->getTranslatableMessage()->message
        );
        self::assertEquals(
            $values,
            $messages[0]->getTranslatableMessage()->values
        );
    }

    public function providerForValidateKO()
    {
        return [
            [-10 / 7, 'The value can not be lower than %size%.', ['%size%' => $this->getMinFloatValue()]],
            [0, 'The value can not be lower than %size%.', ['%size%' => $this->getMinFloatValue()]],
            [99 / 70, 'The value can not be lower than %size%.', ['%size%' => $this->getMinFloatValue()]],
            [111 / 70, 'The value can not be higher than %size%.', ['%size%' => $this->getMaxFloatValue()]],
        ];
    }

    /**
     * Tests validation of constraints.
     *
     * @dataProvider providerForValidateConstraintsOK
     *
     * @covers \Ibexa\Core\FieldType\Validator\FileSizeValidator::validateConstraints
     */
    public function testValidateConstraintsCorrectValues($constraints)
    {
        $validator = new FloatValueValidator();

        self::assertEmpty(
            $validator->validateConstraints($constraints)
        );
    }

    public function providerForValidateConstraintsOK()
    {
        return [
            [
                [],
                [
                    'minFloatValue' => 5,
                ],
                [
                    'maxFloatValue' => 2.2,
                ],
                [
                    'minFloatValue' => null,
                    'maxFloatValue' => null,
                ],
                [
                    'minFloatValue' => -5,
                    'maxFloatValue' => null,
                ],
                [
                    'minFloatValue' => null,
                    'maxFloatValue' => 12.7,
                ],
                [
                    'minFloatValue' => 6,
                    'maxFloatValue' => 8.3,
                ],
            ],
        ];
    }

    /**
     * Tests validation of constraints.
     *
     * @dataProvider providerForValidateConstraintsKO
     *
     * @covers \Ibexa\Core\FieldType\Validator\FileSizeValidator::validateConstraints
     */
    public function testValidateConstraintsWrongValues($constraints, $expectedMessages, $values)
    {
        $validator = new FloatValueValidator();
        $messages = $validator->validateConstraints($constraints);

        foreach ($expectedMessages as $index => $expectedMessage) {
            self::assertInstanceOf(
                Message::class,
                $messages[0]->getTranslatableMessage()
            );
            self::assertEquals(
                $expectedMessage,
                $messages[$index]->getTranslatableMessage()->message
            );
            self::assertEquals(
                $values[$index],
                $messages[$index]->getTranslatableMessage()->values
            );
        }
    }

    public function providerForValidateConstraintsKO()
    {
        return [
            [
                [
                    'minFloatValue' => true,
                ],
                ["Validator parameter '%parameter%' value must be of numeric type"],
                [
                    ['%parameter%' => 'minFloatValue'],
                ],
            ],
            [
                [
                    'minFloatValue' => 'five thousand bytes',
                ],
                ["Validator parameter '%parameter%' value must be of numeric type"],
                [
                    ['%parameter%' => 'minFloatValue'],
                ],
            ],
            [
                [
                    'minFloatValue' => 'five thousand bytes',
                    'maxFloatValue' => 1234,
                ],
                ["Validator parameter '%parameter%' value must be of numeric type"],
                [
                    ['%parameter%' => 'minFloatValue'],
                ],
            ],
            [
                [
                    'maxFloatValue' => new \DateTime(),
                    'minFloatValue' => 1234,
                ],
                ["Validator parameter '%parameter%' value must be of numeric type"],
                [
                    ['%parameter%' => 'maxFloatValue'],
                ],
            ],
            [
                [
                    'minFloatValue' => true,
                    'maxFloatValue' => 1234,
                ],
                ["Validator parameter '%parameter%' value must be of numeric type"],
                [
                    ['%parameter%' => 'minFloatValue'],
                ],
            ],
            [
                [
                    'minFloatValue' => 'five thousand bytes',
                    'maxFloatValue' => 'ten billion bytes',
                ],
                [
                    "Validator parameter '%parameter%' value must be of numeric type",
                    "Validator parameter '%parameter%' value must be of numeric type",
                ],
                [
                    ['%parameter%' => 'minFloatValue'],
                    ['%parameter%' => 'maxFloatValue'],
                ],
            ],
            [
                [
                    'brljix' => 12345,
                ],
                ["Validator parameter '%parameter%' is unknown"],
                [
                    ['%parameter%' => 'brljix'],
                ],
            ],
            [
                [
                    'minFloatValue' => 12345,
                    'brljix' => 12345,
                ],
                ["Validator parameter '%parameter%' is unknown"],
                [
                    ['%parameter%' => 'brljix'],
                ],
            ],
        ];
    }
}

class_alias(FloatValueValidatorTest::class, 'eZ\Publish\Core\FieldType\Tests\FloatValueValidatorTest');

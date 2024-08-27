<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\Validator;

use Ibexa\Contracts\Core\FieldType\ValidationError;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Translation\Message;
use Ibexa\Core\FieldType\Float\Value as FloatValue;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Validator\FloatValueValidator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\FieldType\Validator\FloatValueValidator
 *
 * @group fieldType
 * @group validator
 */
final class FloatValueValidatorTest extends TestCase
{
    private const string VALUE_TOO_LOW_VALIDATION_MESSAGE = 'The value can not be lower than %size%.';
    private const string VALUE_TOO_HIGH_VALIDATION_MESSAGE = 'The value can not be higher than %size%.';
    private const string SIZE_PARAM = '%size%';
    private const string MIN_FLOAT_VALUE_NUMERIC_TYPE_VALIDATION_MESSAGE = "Validator parameter 'minFloatValue' value must be of numeric type";
    private const string MAX_FLOAT_VALUE_NUMERIC_TYPE_VALIDATION_MESSAGE = "Validator parameter 'maxFloatValue' value must be of numeric type";
    private const string WRONG_MIN_FLOAT_VALUE = 'five thousand bytes';
    private const string WRONG_MAX_FLOAT_VALUE = 'ten billion bytes';
    private const string UNKNOWN_PARAM_VALIDATION_MESSAGE = "Validator parameter 'brljix' is unknown";

    protected function getMinFloatValue(): float
    {
        return 10 / 7;
    }

    protected function getMaxFloatValue(): float
    {
        return 11 / 7;
    }

    /**
     * This test ensure an FloatValueValidator can be created.
     */
    public function testConstructor(): void
    {
        self::assertInstanceOf(
            Validator::class,
            new FloatValueValidator()
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException
     */
    public function testConstraintsInitializeGet(): void
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
     */
    public function testGetConstraintsSchema(): void
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

    public function testConstraintsSetGet(): void
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

    public function testInitializeBadConstraint(): void
    {
        $constraints = [
            'unexisting' => 0,
        ];
        $validator = new FloatValueValidator();

        $this->expectException(PropertyNotFoundException::class);
        $validator->initializeWithConstraints(
            $constraints
        );
    }

    public function testSetBadConstraint(): void
    {
        $validator = new FloatValueValidator();

        $this->expectException(PropertyNotFoundException::class);
        $validator->unexisting = 0;
    }

    public function testGetBadConstraint(): void
    {
        $validator = new FloatValueValidator();

        $this->expectException(PropertyNotFoundException::class);
        $null = $validator->unexisting;
    }

    /**
     * @dataProvider providerForValidateOK
     */
    public function testValidateCorrectValues(float $value): void
    {
        $validator = new FloatValueValidator();
        $validator->minFloatValue = 10 / 7;
        $validator->maxFloatValue = 11 / 7;
        self::assertTrue($validator->validate(new FloatValue($value)));
        self::assertSame([], $validator->getMessage());
    }

    /**
     * @return list<array{float}>
     */
    public function providerForValidateOK(): array
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
     * @dataProvider providerForValidateKO
     */
    public function testValidateWrongValues(float $value, string $message): void
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
            (string)$messages[0]->getTranslatableMessage()
        );
    }

    /**
     * @return list<array{float, string}>
     */
    public function providerForValidateKO(): array
    {
        return [
            [-10 / 7, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMinFloatValue()])],
            [0, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMinFloatValue()])],
            [99 / 70, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMinFloatValue()])],
            [111 / 70, strtr(self::VALUE_TOO_HIGH_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMaxFloatValue()])],
        ];
    }

    /**
     * Tests validation of constraints.
     *
     * @dataProvider providerForValidateConstraintsOK
     *
     * @param array<string, mixed> $constraints
     */
    public function testValidateConstraintsCorrectValues(array $constraints): void
    {
        $validator = new FloatValueValidator();

        self::assertEmpty(
            $validator->validateConstraints($constraints)
        );
    }

    /**
     * @return list<array{array<string, mixed>}>
     */
    public function providerForValidateConstraintsOK(): array
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
     * @param array<string, mixed> $constraints
     * @param array<string> $expectedMessages
     *
     * @dataProvider providerForValidateConstraintsKO
     */
    public function testValidateConstraintsWrongValues(array $constraints, array $expectedMessages): void
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
                (string)$messages[$index]->getTranslatableMessage()
            );
        }
    }

    /**
     * @return list<array{array<string, mixed>, array<string>}>
     */
    public function providerForValidateConstraintsKO(): array
    {
        return [
            [
                [
                    'minFloatValue' => true,
                ],
                [self::MIN_FLOAT_VALUE_NUMERIC_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minFloatValue' => self::WRONG_MIN_FLOAT_VALUE,
                ],
                [self::MIN_FLOAT_VALUE_NUMERIC_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minFloatValue' => self::WRONG_MIN_FLOAT_VALUE,
                    'maxFloatValue' => 1234,
                ],
                [self::MIN_FLOAT_VALUE_NUMERIC_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'maxFloatValue' => new \DateTime(),
                    'minFloatValue' => 1234,
                ],
                [self::MAX_FLOAT_VALUE_NUMERIC_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minFloatValue' => true,
                    'maxFloatValue' => 1234,
                ],
                [self::MIN_FLOAT_VALUE_NUMERIC_TYPE_VALIDATION_MESSAGE],
                [
                    ['%parameter%' => 'minFloatValue'],
                ],
            ],
            [
                [
                    'minFloatValue' => self::WRONG_MIN_FLOAT_VALUE,
                    'maxFloatValue' => self::WRONG_MAX_FLOAT_VALUE,
                ],
                [
                    self::MIN_FLOAT_VALUE_NUMERIC_TYPE_VALIDATION_MESSAGE,
                    self::MAX_FLOAT_VALUE_NUMERIC_TYPE_VALIDATION_MESSAGE,
                ],
            ],
            [
                [
                    'brljix' => 12345,
                ],
                [self::UNKNOWN_PARAM_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minFloatValue' => 12345,
                    'brljix' => 12345,
                ],
                [self::UNKNOWN_PARAM_VALIDATION_MESSAGE],
            ],
        ];
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\ValidationError;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Translation\Message;
use Ibexa\Core\FieldType\Integer\Value as IntegerValue;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Validator\IntegerValueValidator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\FieldType\Validator\IntegerValueValidator
 *
 * @group fieldType
 * @group validator
 */
final class IntegerValueValidatorTest extends TestCase
{
    private const string VALUE_TOO_LOW_VALIDATION_MESSAGE = 'The value can not be lower than %size%.';
    private const string VALUE_TOO_HIGH_VALIDATION_MESSAGE = 'The value can not be higher than %size%.';
    private const string MIN_VALUE_OF_INT_TYPE_VALIDATION_MESSAGE = "Validator parameter 'minIntegerValue' value must be of integer type";
    private const string MAX_VALUE_OF_INT_TYPE_VALIDATION_MESSAGE = "Validator parameter 'maxIntegerValue' value must be of integer type";
    private const string WRONG_MIN_INT_VALUE = 'five thousand bytes';
    private const string WRONG_MAX_INT_VALUE = 'ten billion bytes';
    private const string UNKNOWN_PARAM_VALIDATION_MESSAGE = "Validator parameter 'brljix' is unknown";
    public const string SIZE_PARAM = '%size%';

    protected function getMinIntegerValue(): int
    {
        return 10;
    }

    protected function getMaxIntegerValue(): int
    {
        return 15;
    }

    public function testConstructor(): void
    {
        self::assertInstanceOf(
            Validator::class,
            new IntegerValueValidator()
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException
     */
    public function testConstraintsInitializeGet(): void
    {
        $constraints = [
            'minIntegerValue' => 0,
            'maxIntegerValue' => 100,
        ];
        $validator = new IntegerValueValidator();
        $validator->initializeWithConstraints(
            $constraints
        );
        self::assertSame($constraints['minIntegerValue'], $validator->minIntegerValue);
        self::assertSame($constraints['maxIntegerValue'], $validator->maxIntegerValue);
    }

    public function testGetConstraintsSchema(): void
    {
        $constraintsSchema = [
            'minIntegerValue' => [
                'type' => 'int',
                'default' => 0,
            ],
            'maxIntegerValue' => [
                'type' => 'int',
                'default' => null,
            ],
        ];
        $validator = new IntegerValueValidator();
        self::assertSame($constraintsSchema, $validator->getConstraintsSchema());
    }

    public function testConstraintsSetGet(): void
    {
        $constraints = [
            'minIntegerValue' => 0,
            'maxIntegerValue' => 100,
        ];
        $validator = new IntegerValueValidator();
        $validator->minIntegerValue = $constraints['minIntegerValue'];
        $validator->maxIntegerValue = $constraints['maxIntegerValue'];
        self::assertSame($constraints['minIntegerValue'], $validator->minIntegerValue);
        self::assertSame($constraints['maxIntegerValue'], $validator->maxIntegerValue);
    }

    public function testInitializeBadConstraint(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $constraints = [
            'unexisting' => 0,
        ];
        $validator = new IntegerValueValidator();
        $validator->initializeWithConstraints(
            $constraints
        );
    }

    public function testSetBadConstraint(): void
    {
        $validator = new IntegerValueValidator();

        $this->expectException(PropertyNotFoundException::class);
        $validator->unexisting = 0;
    }

    public function testGetBadConstraint(): void
    {
        $validator = new IntegerValueValidator();

        $this->expectException(PropertyNotFoundException::class);
        $null = $validator->unexisting;
    }

    /**
     * @dataProvider providerForValidateOK
     */
    public function testValidateCorrectValues(int $value): void
    {
        $validator = new IntegerValueValidator();
        $validator->minIntegerValue = 10;
        $validator->maxIntegerValue = 15;
        self::assertTrue($validator->validate(new IntegerValue($value)));
        self::assertSame([], $validator->getMessage());
    }

    /**
     * @return list<array{int}>
     */
    public function providerForValidateOK(): array
    {
        return [
            [10],
            [11],
            [12],
            [13],
            [14],
            [15],
        ];
    }

    /**
     * Tests validating a wrong value.
     *
     * @dataProvider providerForValidateKO
     */
    public function testValidateWrongValues($value, $message): void
    {
        $validator = new IntegerValueValidator();
        $validator->minIntegerValue = $this->getMinIntegerValue();
        $validator->maxIntegerValue = $this->getMaxIntegerValue();
        self::assertFalse($validator->validate(new IntegerValue($value)));
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
            $messages[0]->getTranslatableMessage()
        );
    }

    /**
     * @return list<array{int, string}>
     */
    public function providerForValidateKO(): array
    {
        return [
            [-12, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMinIntegerValue()])],
            [0, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMinIntegerValue()])],
            [9, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMinIntegerValue()])],
            [16, strtr(self::VALUE_TOO_HIGH_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMaxIntegerValue()])],
        ];
    }

    /**
     * @dataProvider providerForValidateConstraintsOK
     *
     * @param array<string, mixed> $constraints
     */
    public function testValidateConstraintsCorrectValues(array $constraints): void
    {
        $validator = new IntegerValueValidator();

        self::assertEmpty(
            $validator->validateConstraints($constraints)
        );
    }

    /**
     * @return list<list<array<string, mixed>>>
     */
    public function providerForValidateConstraintsOK(): array
    {
        return [
            [
                [],
                [
                    'minIntegerValue' => 5,
                ],
                [
                    'maxIntegerValue' => 2,
                ],
                [
                    'minIntegerValue' => null,
                    'maxIntegerValue' => null,
                ],
                [
                    'minIntegerValue' => -5,
                    'maxIntegerValue' => null,
                ],
                [
                    'minIntegerValue' => null,
                    'maxIntegerValue' => 12,
                ],
                [
                    'minIntegerValue' => 6,
                    'maxIntegerValue' => 8,
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForValidateConstraintsKO
     *
     * @param array<string, mixed> $constraints
     * @param array<int, string> $expectedMessages
     */
    public function testValidateConstraintsWrongValues(array $constraints, array $expectedMessages): void
    {
        $validator = new IntegerValueValidator();
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
     * @return list<array{array<string, mixed>, string[]}>
     */
    public function providerForValidateConstraintsKO(): array
    {
        return [
            [
                [
                    'minIntegerValue' => true,
                ],
                [self::MIN_VALUE_OF_INT_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minIntegerValue' => self::WRONG_MIN_INT_VALUE,
                ],
                [self::MIN_VALUE_OF_INT_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minIntegerValue' => self::WRONG_MIN_INT_VALUE,
                    'maxIntegerValue' => 1234,
                ],
                [self::MIN_VALUE_OF_INT_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'maxIntegerValue' => new \DateTime(),
                    'minIntegerValue' => 1234,
                ],
                [self::MAX_VALUE_OF_INT_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minIntegerValue' => true,
                    'maxIntegerValue' => 1234,
                ],
                [self::MIN_VALUE_OF_INT_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minIntegerValue' => self::WRONG_MIN_INT_VALUE,
                    'maxIntegerValue' => self::WRONG_MAX_INT_VALUE,
                ],
                [
                    self::MIN_VALUE_OF_INT_TYPE_VALIDATION_MESSAGE,
                    self::MAX_VALUE_OF_INT_TYPE_VALIDATION_MESSAGE,
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
                    'minIntegerValue' => 12345,
                    'brljix' => 12345,
                ],
                [self::UNKNOWN_PARAM_VALIDATION_MESSAGE],
            ],
        ];
    }
}

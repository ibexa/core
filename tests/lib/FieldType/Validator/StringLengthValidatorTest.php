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
use Ibexa\Contracts\Core\Repository\Values\Translation\Plural;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Validator\StringLengthValidator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\FieldType\Validator\StringLengthValidator
 *
 * @group fieldType
 * @group validator
 */
final class StringLengthValidatorTest extends TestCase
{
    private const string STRING_TOO_SHORT_VALIDATION_MESSAGE = 'The string cannot be shorter than 5 characters.';
    private const string STRING_TOO_LONG_VALIDATION_MESSAGE = 'The string can not exceed 10 characters.';
    private const string MIN_STR_LEN_INT_TYPE_VALIDATION_MESSAGE = "Validator parameter 'minStringLength' value must be of integer type";
    private const string WRONG_MIN_STR_LEN_VALUE = 'five thousand characters';
    private const string MAX_STR_LEN_INT_TYPE_VALIDATION_MESSAGE = "Validator parameter 'maxStringLength' value must be of integer type";
    private const string WRONG_MAX_STR_LEN_VALUE = 'ten billion characters';

    protected function getMinStringLength(): int
    {
        return 5;
    }

    protected function getMaxStringLength(): int
    {
        return 10;
    }

    public function testConstructor(): void
    {
        self::assertInstanceOf(
            Validator::class,
            new StringLengthValidator()
        );
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function testConstraintsInitializeGet(): void
    {
        $constraints = [
            'minStringLength' => 5,
            'maxStringLength' => 10,
        ];
        $validator = new StringLengthValidator();
        $validator->initializeWithConstraints(
            $constraints
        );
        self::assertSame($constraints['minStringLength'], $validator->minStringLength);
        self::assertSame($constraints['maxStringLength'], $validator->maxStringLength);
    }

    public function testGetConstraintsSchema(): void
    {
        $constraintsSchema = [
            'minStringLength' => [
                'type' => 'int',
                'default' => 0,
            ],
            'maxStringLength' => [
                'type' => 'int',
                'default' => null,
            ],
        ];
        $validator = new StringLengthValidator();
        self::assertSame($constraintsSchema, $validator->getConstraintsSchema());
    }

    public function testConstraintsSetGet(): void
    {
        $constraints = [
            'minStringLength' => 5,
            'maxStringLength' => 10,
        ];
        $validator = new StringLengthValidator();
        $validator->minStringLength = $constraints['minStringLength'];
        $validator->maxStringLength = $constraints['maxStringLength'];
        self::assertSame($constraints['minStringLength'], $validator->minStringLength);
        self::assertSame($constraints['maxStringLength'], $validator->maxStringLength);
    }

    public function testInitializeBadConstraint(): void
    {
        $constraints = [
            'unexisting' => 0,
        ];
        $validator = new StringLengthValidator();

        $this->expectException(PropertyNotFoundException::class);
        $validator->initializeWithConstraints(
            $constraints
        );
    }

    public function testSetBadConstraint(): void
    {
        $validator = new StringLengthValidator();

        $this->expectException(PropertyNotFoundException::class);
        /** @phpstan-ignore-next-line */
        $validator->unexisting = 0;
    }

    public function testGetBadConstraint(): void
    {
        $validator = new StringLengthValidator();

        $this->expectException(PropertyNotFoundException::class);
        /** @phpstan-ignore-next-line */
        $validator->unexisting;
    }

    /**
     * @dataProvider providerForValidateOK
     */
    public function testValidateCorrectValues(string $value): void
    {
        $validator = new StringLengthValidator();
        $validator->minStringLength = 5;
        $validator->maxStringLength = 10;
        self::assertTrue($validator->validate(new TextLineValue($value)));
        self::assertSame([], $validator->getMessage());
    }

    /**
     * @return list<array{string}>
     */
    public function providerForValidateOK(): array
    {
        return [
            ['hello'],
            ['hello!'],
            ['0123456789'],
            ['♔♕♖♗♘♙♚♛♜♝'],
        ];
    }

    /**
     * @dataProvider providerForValidateKO
     */
    public function testValidateWrongValues(
        string $value,
        string $expectedMessage,
        int $minStringLength,
        int $maxStringLength
    ): void {
        $validator = new StringLengthValidator();
        $validator->minStringLength = $minStringLength;
        $validator->maxStringLength = $maxStringLength;
        self::assertFalse($validator->validate(new TextLineValue($value)));
        $messages = $validator->getMessage();
        self::assertCount(1, $messages);
        self::assertInstanceOf(
            ValidationError::class,
            $messages[0]
        );
        self::assertInstanceOf(
            Plural::class,
            $messages[0]->getTranslatableMessage()
        );
        self::assertEquals(
            $expectedMessage,
            (string)$messages[0]->getTranslatableMessage()
        );
    }

    /**
     * @return iterable<string, array{string, string, int, int}>
     */
    public function providerForValidateKO(): iterable
    {
        yield 'empty string' => [
            '',
            self::STRING_TOO_SHORT_VALIDATION_MESSAGE,
            $this->getMinStringLength(),
            $this->getMaxStringLength(),
        ];

        yield 'too short string' => [
            'Hi!',
            self::STRING_TOO_SHORT_VALIDATION_MESSAGE,
            $this->getMinStringLength(),
            $this->getMaxStringLength(),
        ];

        yield 'too long string' => [
            '0123456789!',
            self::STRING_TOO_LONG_VALIDATION_MESSAGE,
            $this->getMinStringLength(),
            $this->getMaxStringLength(),
        ];

        yield 'too short string with special characters' => [
            'ABC♔',
            self::STRING_TOO_SHORT_VALIDATION_MESSAGE,
            $this->getMinStringLength(),
            $this->getMaxStringLength(),
        ];

        yield 'too short string, singular form validation message' => [
            '',
            'The string cannot be shorter than 1 character.',
            1,
            $this->getMaxStringLength(),
        ];

        yield 'too long string, singular form validation message' => [
            'foo',
            'The string can not exceed 1 character.',
            1,
            1,
        ];
    }

    /**
     * @param array<string, mixed> $constraints
     *
     * @dataProvider providerForValidateConstraintsOK
     */
    public function testValidateConstraintsCorrectValues(array $constraints): void
    {
        $validator = new StringLengthValidator();

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
                    'minStringLength' => 5,
                ],
                [
                    'maxStringLength' => 2,
                ],
                [
                    'minStringLength' => false,
                    'maxStringLength' => false,
                ],
                [
                    'minStringLength' => -5,
                    'maxStringLength' => false,
                ],
                [
                    'minStringLength' => false,
                    'maxStringLength' => 12,
                ],
                [
                    'minStringLength' => 6,
                    'maxStringLength' => 8,
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForValidateConstraintsKO
     *
     * @param array<string, mixed> $constraints
     * @param string[] $expectedMessages
     */
    public function testValidateConstraintsWrongValues(
        array $constraints,
        array $expectedMessages
    ): void {
        $validator = new StringLengthValidator();
        $messages = $validator->validateConstraints($constraints);

        foreach ($expectedMessages as $index => $expectedMessage) {
            self::assertInstanceOf(
                Message::class,
                $messages[$index]->getTranslatableMessage()
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
                    'minStringLength' => true,
                ],
                [self::MIN_STR_LEN_INT_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minStringLength' => self::WRONG_MIN_STR_LEN_VALUE,
                ],
                [self::MIN_STR_LEN_INT_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minStringLength' => self::WRONG_MIN_STR_LEN_VALUE,
                    'maxStringLength' => 1234,
                ],
                [self::MIN_STR_LEN_INT_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'maxStringLength' => new \DateTime(),
                    'minStringLength' => 1234,
                ],
                [self::MAX_STR_LEN_INT_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minStringLength' => true,
                    'maxStringLength' => 1234,
                ],
                [self::MIN_STR_LEN_INT_TYPE_VALIDATION_MESSAGE],
            ],
            [
                [
                    'minStringLength' => self::WRONG_MIN_STR_LEN_VALUE,
                    'maxStringLength' => self::WRONG_MAX_STR_LEN_VALUE,
                ],
                [
                    self::MIN_STR_LEN_INT_TYPE_VALIDATION_MESSAGE,
                    self::MAX_STR_LEN_INT_TYPE_VALIDATION_MESSAGE,
                ],
            ],
            [
                [
                    'brljix' => 12345,
                ],
                ["Validator parameter 'brljix' is unknown"],
            ],
            [
                [
                    'minStringLength' => 12345,
                    'brljix' => 12345,
                ],
                ["Validator parameter 'brljix' is unknown"],
            ],
        ];
    }
}

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
use Ibexa\Core\FieldType\Validator;
use PHPUnit\Framework\TestCase;

/**
 * @template TValidatorType of \Ibexa\Core\FieldType\Validator
 */
abstract class BaseNumericValidatorTestCase extends TestCase
{
    protected const string VALUE_TOO_LOW_VALIDATION_MESSAGE = 'The value can not be lower than %size%.';
    protected const string VALUE_TOO_HIGH_VALIDATION_MESSAGE = 'The value can not be higher than %size%.';
    protected const string SIZE_PARAM = '%size%';

    protected const string WRONG_NUMERIC_MIN_VALUE = 'five thousand bytes';
    protected const string WRONG_NUMERIC_MAX_VALUE = 'ten billion bytes';
    protected const string UNKNOWN_PARAM_VALIDATION_MESSAGE = "Validator parameter 'brljix' is unknown";

    protected const string MIN = 'min';
    protected const string MAX = 'max';

    /**
     * @phpstan-return TValidatorType
     */
    abstract protected function getValidatorInstance(): Validator;

    abstract protected function getMinNumericValueName(): string;

    abstract protected function getMaxNumericValueName(): string;

    /**
     * @return iterable<array{min?: ?scalar, max?: ?scalar}>
     */
    abstract public function providerForValidateConstraintsOK(): iterable;

    /**
     * @return iterable<list<array<string, scalar>>>
     */
    abstract public static function providerForConstraintsInitializeSetGet(): iterable;

    abstract protected function getIncorrectNumericTypeValidationMessage(string $parameterName): string;

    /**
     * @return iterable<string, array{array<string, mixed>, array<string>}>
     */
    final public function providerForValidateConstraintsKO(): iterable
    {
        $minNumericValueName = $this->getMinNumericValueName();
        $minValueNumericTypeValidationMessage = $this->getIncorrectNumericTypeValidationMessage(
            $minNumericValueName
        );
        $maxNumericValueName = $this->getMaxNumericValueName();
        $maxValueNumericTypeValidationMessage = $this->getIncorrectNumericTypeValidationMessage(
            $maxNumericValueName
        );

        yield 'invalid min type (bool), max not set' => [
            [
                $minNumericValueName => true,
            ],
            [$minValueNumericTypeValidationMessage],
        ];

        yield 'invalid min type (string), max not set' => [
            [
                $minNumericValueName => self::WRONG_NUMERIC_MIN_VALUE,
            ],
            [$minValueNumericTypeValidationMessage],
        ];

        yield 'invalid min type (string), valid max' => [
            [
                $minNumericValueName => self::WRONG_NUMERIC_MIN_VALUE,
                $maxNumericValueName => 1234,
            ],
            [$minValueNumericTypeValidationMessage],
        ];

        yield 'valid min, invalid max type (DateTime object)' => [
            [
                $maxNumericValueName => new \DateTime(),
                $minNumericValueName => 1234,
            ],
            [$maxValueNumericTypeValidationMessage],
        ];

        yield 'invalid min type (bool), valid max, with a parameter' => [
            [
                $minNumericValueName => true,
                $maxNumericValueName => 1234,
            ],
            [$minValueNumericTypeValidationMessage],
            [
                ['%parameter%' => $minNumericValueName],
            ],
        ];

        yield 'invalid min and max types (strings)' => [
            [
                $minNumericValueName => self::WRONG_NUMERIC_MIN_VALUE,
                $maxNumericValueName => self::WRONG_NUMERIC_MAX_VALUE,
            ],
            [
                $minValueNumericTypeValidationMessage,
                $maxValueNumericTypeValidationMessage,
            ],
        ];

        yield 'unknown parameter' => [
            [
                'brljix' => 12345,
            ],
            [self::UNKNOWN_PARAM_VALIDATION_MESSAGE],
        ];

        yield 'unknown parameter, valid min' => [
            [
                $minNumericValueName => 12345,
                'brljix' => 12345,
            ],
            [self::UNKNOWN_PARAM_VALIDATION_MESSAGE],
        ];
    }

    /**
     * @param array<string, mixed> $constraints
     * @param array<int, string> $expectedMessages
     *
     * @dataProvider providerForValidateConstraintsKO
     */
    final public function testValidateConstraintsWrongValues(
        array $constraints,
        array $expectedMessages
    ): void {
        $validator = $this->getValidatorInstance();
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

    final public function testSetBadConstraint(): void
    {
        $validator = $this->getValidatorInstance();

        $this->expectException(PropertyNotFoundException::class);
        /** @phpstan-ignore-next-line */
        $validator->unexisting = 0;
    }

    final public function testGetBadConstraint(): void
    {
        $validator = $this->getValidatorInstance();

        $this->expectException(PropertyNotFoundException::class);
        /** @phpstan-ignore-next-line */
        $validator->unexisting;
    }

    /**
     * @dataProvider providerForValidateConstraintsOK
     *
     * @param array{min?: ?scalar, max?: ?scalar} $data
     */
    public function testValidateConstraintsCorrectValues(array $data): void
    {
        $validator = $this->getValidatorInstance();

        $constraints = [];
        if (array_key_exists('min', $data)) {
            $constraints[$this->getMinNumericValueName()] = $data['min'];
        }
        if (array_key_exists('max', $data)) {
            $constraints[$this->getMinNumericValueName()] = $data['max'];
        }

        self::assertEmpty(
            $validator->validateConstraints($constraints)
        );
    }

    /**
     * @dataProvider providerForConstraintsInitializeSetGet
     *
     * @param array<string, scalar> $constraints
     *
     * @throws PropertyNotFoundException
     */
    final public function testConstraintsInitializeGet(array $constraints): void
    {
        $minNumericValueName = $this->getMinNumericValueName();
        $maxNumericValueName = $this->getMaxNumericValueName();
        $validator = $this->getValidatorInstance();
        $validator->initializeWithConstraints(
            $constraints
        );
        self::assertSame($constraints[$minNumericValueName], $validator->{$minNumericValueName});
        self::assertSame($constraints[$maxNumericValueName], $validator->{$maxNumericValueName});
    }

    /**
     * @dataProvider providerForConstraintsInitializeSetGet
     *
     * @param array<string, scalar> $constraints
     */
    final public function testConstraintsSetGet(array $constraints): void
    {
        $minNumericValueName = $this->getMinNumericValueName();
        $maxNumericValueName = $this->getMaxNumericValueName();
        $validator = $this->getValidatorInstance();
        $validator->{$minNumericValueName} = $constraints[$minNumericValueName];
        $validator->{$maxNumericValueName} = $constraints[$maxNumericValueName];
        self::assertSame($constraints[$minNumericValueName], $validator->{$minNumericValueName});
        self::assertSame($constraints[$maxNumericValueName], $validator->{$maxNumericValueName});
    }

    /**
     * @param ValidationError[] $actualMessages
     */
    protected static function assertWrongValueValidationMessage(
        array $actualMessages,
        string $expectedMessage
    ): void {
        self::assertCount(1, $actualMessages);
        self::assertInstanceOf(
            ValidationError::class,
            $actualMessages[0]
        );
        self::assertInstanceOf(
            Message::class,
            $actualMessages[0]->getTranslatableMessage()
        );
        self::assertEquals(
            $expectedMessage,
            (string)$actualMessages[0]->getTranslatableMessage()
        );
    }
}

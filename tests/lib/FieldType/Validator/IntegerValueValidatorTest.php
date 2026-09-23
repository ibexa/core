<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\Validator;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Core\FieldType\Integer\Value as IntegerValue;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Validator\IntegerValueValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * @extends BaseNumericValidatorTestCase<IntegerValueValidator>
 */
#[CoversClass(IntegerValueValidator::class)]
#[Group('fieldType')]
#[Group('validator')]
final class IntegerValueValidatorTest extends BaseNumericValidatorTestCase
{
    protected function getValidatorInstance(): Validator
    {
        return new IntegerValueValidator();
    }

    protected static function getMinNumericValueName(): string
    {
        return 'minIntegerValue';
    }

    protected static function getMaxNumericValueName(): string
    {
        return 'maxIntegerValue';
    }

    protected static function getMinIntegerValue(): int
    {
        return 10;
    }

    protected static function getMaxIntegerValue(): int
    {
        return 15;
    }

    public static function providerForConstraintsInitializeSetGet(): iterable
    {
        yield [
            [
                'minIntegerValue' => 0,
                'maxIntegerValue' => 100,
            ],
        ];
    }

    public function testGetConstraintsSchema(): void
    {
        $constraintsSchema = [
            self::getMinNumericValueName() => [
                'type' => 'int',
                'default' => 0,
            ],
            self::getMaxNumericValueName() => [
                'type' => 'int',
                'default' => null,
            ],
        ];
        $validator = $this->getValidatorInstance();
        self::assertSame($constraintsSchema, $validator->getConstraintsSchema());
    }

    public function testInitializeBadConstraint(): void
    {
        $this->expectException(PropertyNotFoundException::class);

        $constraints = [
            'unexisting' => 0,
        ];
        $validator = $this->getValidatorInstance();
        $validator->initializeWithConstraints(
            $constraints
        );
    }

    #[DataProvider('providerForValidateOK')]
    public function testValidateCorrectValues(int $value): void
    {
        $validator = $this->getValidatorInstance();
        $validator->minIntegerValue = 10;
        $validator->maxIntegerValue = 15;
        self::assertTrue($validator->validate(new IntegerValue($value)));
        self::assertSame([], $validator->getMessage());
    }

    /**
     * @return list<array{int}>
     */
    public static function providerForValidateOK(): array
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
     */
    #[DataProvider('providerForValidateKO')]
    public function testValidateWrongValues(int $value, string $message): void
    {
        $validator = $this->getValidatorInstance();
        $validator->minIntegerValue = self::getMinIntegerValue();
        $validator->maxIntegerValue = self::getMaxIntegerValue();
        self::assertFalse($validator->validate(new IntegerValue($value)));
        self::assertWrongValueValidationMessage($validator->getMessage(), $message);
    }

    /**
     * @return list<array{int, string}>
     */
    public static function providerForValidateKO(): array
    {
        return [
            [-12, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => self::getMinIntegerValue()])],
            [0, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => self::getMinIntegerValue()])],
            [9, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => self::getMinIntegerValue()])],
            [16, strtr(self::VALUE_TOO_HIGH_VALIDATION_MESSAGE, [self::SIZE_PARAM => self::getMaxIntegerValue()])],
        ];
    }

    public static function providerForValidateConstraintsOK(): iterable
    {
        yield [[]];
        yield [[self::MIN => 5]];
        yield [[self::MAX => 2]];
        yield [[self::MIN => null, self::MAX => null]];
        yield [[self::MIN => -5, self::MAX => null]];
        yield [[self::MIN => null, self::MAX => 12]];
        yield [[self::MIN => 6, self::MAX => 8]];
    }

    protected static function getIncorrectNumericTypeValidationMessage(string $parameterName): string
    {
        return sprintf(
            "Validator parameter '%s' value must be of integer type",
            $parameterName
        );
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\Validator;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Core\FieldType\Float\Value as FloatValue;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Validator\FloatValueValidator;

/**
 * @covers \Ibexa\Core\FieldType\Validator\FloatValueValidator
 *
 * @extends BaseNumericValidatorTestCase<FloatValueValidator>
 *
 * @group fieldType
 * @group validator
 */
final class FloatValueValidatorTest extends BaseNumericValidatorTestCase
{
    protected function getValidatorInstance(): Validator
    {
        return new FloatValueValidator();
    }

    protected function getMinNumericValueName(): string
    {
        return 'minFloatValue';
    }

    protected function getMaxNumericValueName(): string
    {
        return 'maxFloatValue';
    }

    protected function getMinFloatValue(): float
    {
        return 10 / 7;
    }

    protected function getMaxFloatValue(): float
    {
        return 11 / 7;
    }

    public static function providerForConstraintsInitializeSetGet(): iterable
    {
        yield [
            [
                'minFloatValue' => 0.5,
                'maxFloatValue' => 22 / 7,
            ],
        ];
    }

    /**
     * Test getting constraints schema.
     */
    public function testGetConstraintsSchema(): void
    {
        $constraintsSchema = [
            $this->getMinNumericValueName() => [
                'type' => 'float',
                'default' => null,
            ],
            $this->getMaxNumericValueName() => [
                'type' => 'float',
                'default' => null,
            ],
        ];
        $validator = $this->getValidatorInstance();
        self::assertSame($constraintsSchema, $validator->getConstraintsSchema());
    }

    public function testInitializeBadConstraint(): void
    {
        $constraints = [
            'unexisting' => 0,
        ];
        $validator = $this->getValidatorInstance();

        $this->expectException(PropertyNotFoundException::class);
        $validator->initializeWithConstraints(
            $constraints
        );
    }

    /**
     * @dataProvider providerForValidateOK
     */
    public function testValidateCorrectValues(float $value): void
    {
        $validator = $this->getValidatorInstance();
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
        $validator = $this->getValidatorInstance();
        $validator->minFloatValue = $this->getMinFloatValue();
        $validator->maxFloatValue = $this->getMaxFloatValue();
        self::assertFalse($validator->validate(new FloatValue($value)));
        self::assertWrongValueValidationMessage($validator->getMessage(), $message);
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

    public function providerForValidateConstraintsOK(): iterable
    {
        yield [[]];
        yield [[self::MAX => 3.2]];
        yield [[self::MIN => 7]];
        yield [[self::MIN => -7, self::MAX => null]];
        yield [[self::MIN => 4, self::MAX => 4.3]];
        yield [[self::MIN => null, self::MAX => 12.7]];
        yield [[self::MIN => null, self::MAX => null]];
    }

    protected function getIncorrectNumericTypeValidationMessage(string $parameterName): string
    {
        return sprintf(
            "Validator parameter '%s' value must be of numeric type",
            $parameterName
        );
    }
}

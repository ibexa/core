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
    private const float MIN_FLOAT_VALUE = 1.4285714285714;
    private const float MAX_FLOAT_VALUE = 1.5714285714286;

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
        return self::MIN_FLOAT_VALUE;
    }

    protected function getMaxFloatValue(): float
    {
        return self::MAX_FLOAT_VALUE;
    }

    public static function providerForConstraintsInitializeSetGet(): iterable
    {
        yield [
            [
                'minFloatValue' => 0.5,
                'maxFloatValue' => 3.1428571428571,
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
        $validator->minFloatValue = self::MIN_FLOAT_VALUE;
        $validator->maxFloatValue = self::MAX_FLOAT_VALUE;
        self::assertTrue($validator->validate(new FloatValue($value)));
        self::assertSame([], $validator->getMessage());
    }

    /**
     * @return list<array{float}>
     */
    public function providerForValidateOK(): array
    {
        return [
            [
                1.4285714285714286,
            ],
            [
                1.4428571428571428,
            ],
            [
                1.5,
            ],
            [
                1.5571428571428572,
            ],
            [
                1.5714285714285714,
            ],
        ];
    }

    /**
     * @dataProvider providerForValidateKO
     */
    public function testValidateWrongValues(
        float $value,
        string $message
    ): void {
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
            [-self::MIN_FLOAT_VALUE, strtr(
                self::VALUE_TOO_LOW_VALIDATION_MESSAGE,
                [self::SIZE_PARAM => $this->getMinFloatValue()]
            )],
            [0, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMinFloatValue()])],
            [1.4142857142857, strtr(self::VALUE_TOO_LOW_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMinFloatValue()])],
            [1.5857142857143, strtr(self::VALUE_TOO_HIGH_VALIDATION_MESSAGE, [self::SIZE_PARAM => $this->getMaxFloatValue()])],
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

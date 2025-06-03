<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Value;
use InvalidArgumentException;

abstract class Criterion implements CriterionInterface
{
    /**
     * The operator used by the Criterion.
     */
    public string $operator;

    /**
     * The value(s) matched by the criteria.
     *
     * @var scalar[]|scalar
     */
    public mixed $value;

    /**
     * The target used by the criteria (field, metadata...).
     */
    public ?string $target;

    /**
     * Additional value data, required by some criteria, MapLocationDistance for instance.
     */
    public ?Value $valueData;

    /**
     * Creates a Criterion.
     *
     * Performs operator validation based on the Criterion specifications returned by {@see Criterion::getSpecifications()}.
     *
     * @param string|null $target The target the Criterion applies to: metadata identifier, field identifier...
     * @param string|null $operator
     *        The operator the Criterion uses. If null is given, will default to {@see Operator::IN} if $value is an array,
     *        {@see Operator::EQ} if it isn't.
     * @param array<int, scalar>|scalar $value
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Value|null $valueData
     *
     * @throws \InvalidArgumentException if the provided operator isn't supported
     */
    public function __construct(?string $target, ?string $operator, mixed $value, ?Value $valueData = null)
    {
        if ($operator === null) {
            $operator = is_array($value) ? Operator::IN : Operator::EQ;
        }

        $operatorFound = false;

        // we loop on each specified operator.
        // If the provided operator ain't found, an exception will be thrown at the end
        foreach ($this->getSpecifications() as $operatorSpecifications) {
            if ($operatorSpecifications->operator != $operator) {
                continue;
            }
            $operatorFound = true;

            // input format check (single/array)
            switch ($operatorSpecifications->valueFormat) {
                case Specifications::FORMAT_SINGLE:
                    if (is_array($value)) {
                        throw new InvalidArgumentException('The Criterion expects a single value');
                    }
                    break;

                case Specifications::FORMAT_ARRAY:
                    if (!is_array($value)) {
                        throw new InvalidArgumentException('The Criterion expects an array of values');
                    }
                    break;
            }

            // input value check
            if ($operatorSpecifications->valueTypes !== null) {
                $callback = $this->getValueTypeCheckCallback($operatorSpecifications->valueTypes);
                if (!is_array($value)) {
                    $value = [$value];
                }
                foreach ($value as $item) {
                    if ($callback($item) === false) {
                        throw new InvalidArgumentException('Unsupported value (' . gettype($item) . ")$item");
                    }
                }
            }
        }

        // Operator wasn't found in the criterion specifications
        if ($operatorFound === false) {
            throw new InvalidArgumentException("Operator $operator isn't supported by Criterion " . static::class);
        }

        $this->operator = $operator;
        $this->value = $value;
        $this->target = $target;
        $this->valueData = $valueData;
    }

    /**
     * Criterion description function.
     *
     * Returns the combination of the Criterion's supported operator/value,
     * as an array of {@see \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications} objects
     * - {@see Specifications::$operator} is a supported {@see Operator} constant.
     * - {@see Specifications::$valueFormat} is the type of input value this operator requires, either array ({@see Specifications::FORMAT_ARRAY}) or single ({@see Specifications::FORMAT_SINGLE}).
     * - {@see Specifications::$valueTypes} are bitwise flags of types the operator will accept ({@see Specifications::TYPE_BOOLEAN}, {@see Specifications::TYPE_INTEGER}, and/or {@see Specifications::TYPE_STRING}).
     * - {@see Specifications::$valueCount} is an integer saying how many values are expected.
     *
     * ```
     * // IN and EQ are supported
     * return [
     *     // The EQ operator expects a single value, either as an integer or a string
     *     new Specifications(
     *         Operator::EQ,
     *         Specifications::FORMAT_SINGLE,
     *         Specifications::TYPE_INTEGER | Specifications::TYPE_STRING
     *     ),
     *     // The IN operator expects an array of values, of either integers or strings
     *     new Specifications(
     *         Operator::IN,
     *         Specifications::FORMAT_ARRAY,
     *         Specifications::TYPE_INTEGER | Specifications::TYPE_STRING
     *     )
     * ]
     * ```
     *
     * @return array<int, \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications>
     */
    abstract public function getSpecifications(): array;

    /**
     * Returns a callback that checks the values types depending on the operator specifications.
     *
     * @param int $valueTypes The accepted values, as a bit field of {@see Specifications}::TYPE_* constants
     *
     * @return callable
     */
    private function getValueTypeCheckCallback(int $valueTypes): callable
    {
        $callback = static function ($value): bool {
            return false;
        };

        // the callback code will return true as soon as an accepted value type is found
        if ($valueTypes & Specifications::TYPE_INTEGER) {
            $callback = static function ($value) use ($callback): bool {
                return is_numeric($value) || $callback($value);
            };
        }
        if ($valueTypes & Specifications::TYPE_STRING) {
            $callback = static function ($value) use ($callback): bool {
                return is_string($value) || $callback($value);
            };
        }
        if ($valueTypes & Specifications::TYPE_BOOLEAN) {
            $callback = static function ($value) use ($callback): bool {
                return is_bool($value) || $callback($value);
            };
        }

        return $callback;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Image;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

abstract class AbstractImageRangeCriterion extends Criterion
{
    /**
     * @param numeric|null $minValue
     * @param numeric|null $maxValue
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function __construct(
        string $fieldDefIdentifier,
        $minValue = null,
        $maxValue = null
    ) {
        $this->validate($minValue, $maxValue);
        $value[] = $minValue ?? 0;

        if ($maxValue > 0) {
            $value[] = $maxValue;
        }

        $operator = $this->getOperator($value);

        parent::__construct(
            $fieldDefIdentifier,
            $operator,
            $value
        );
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::BETWEEN,
                Specifications::FORMAT_ARRAY,
                Specifications::TYPE_INTEGER
            ),
            new Specifications(
                Operator::GTE,
                Specifications::FORMAT_ARRAY,
                Specifications::TYPE_INTEGER
            ),
        ];
    }

    /**
     * @param numeric|null $minValue
     * @param numeric|null $maxValue
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function validate($minValue, $maxValue): void
    {
        if (
            null === $minValue
            && null === $maxValue
        ) {
            throw new InvalidArgumentException(
                implode(', ', ['$minValue', '$maxValue']),
                'At least one value must be specified.'
            );
        }

        if ($minValue < 0) {
            throw new InvalidArgumentException(
                '$minValue',
                'Value should be greater or equal 0'
            );
        }

        if (
            null !== $maxValue
            && $maxValue < 0
        ) {
            throw new InvalidArgumentException(
                '$maxValue',
                'Value should be greater than 0'
            );
        }

        if (
            null !== $maxValue
            && $minValue > $maxValue
        ) {
            throw new InvalidArgumentException(
                '$minValue',
                'Value should be greater than' . $maxValue
            );
        }
    }

    /**
     * @param array{0: numeric, 1?: numeric|null} $value
     */
    private function getOperator(array $value): string
    {
        if (count($value) === 2) {
            return Operator::BETWEEN;
        }

        return Operator::GTE;
    }
}

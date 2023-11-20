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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function __construct(
        string $fieldDefIdentifier,
        int $minValue = 0,
        ?int $maxValue = null
    ) {
        $this->validate($minValue, $maxValue);

        $value[] = $minValue;
        $operator = Operator::GTE;

        if ($maxValue >= 1) {
            $operator = Operator::BETWEEN;
            $value[] = $maxValue;
        }

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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function validate(
        int $minValue,
        ?int $maxValue
    ): void {
        if ($minValue < 0) {
            throw new InvalidArgumentException(
                '$minValue',
                'Value should be grater or equal 0'
            );
        }

        if (
            null !== $maxValue
            && $maxValue < 1
        ) {
            throw new InvalidArgumentException(
                '$maxValue',
                'Value should be grater or equal 1'
            );
        }

        if (
            null !== $maxValue
            && $minValue > $maxValue
        ) {
            throw new InvalidArgumentException(
                '$minValue',
                'Value should be grater than' . $maxValue
            );
        }
    }
}

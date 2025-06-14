<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;

/**
 * A criterion that matches Location based on its priority.
 *
 * Supported operators:
 * - BETWEEN: matches location whose priority is between (included) the TWO given priorities
 * - GT, GTE: matches location whose priority is greater than/greater than or equals the given priority
 * - LT, LTE: matches location whose priority is lower than/lower than or equals the given priority
 */
class Priority extends Location implements FilteringCriterion
{
    /**
     * Creates a new LocationPriority criterion.
     *
     * @param string $operator One of the Operator constants
     * @param int|int[] $value The match value, either as an array of as a single value, depending on the operator
     */
    public function __construct(string $operator, int|array $value)
    {
        parent::__construct(null, $operator, $value);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(Operator::BETWEEN, Specifications::FORMAT_ARRAY, Specifications::TYPE_INTEGER),
            new Specifications(Operator::GT, Specifications::FORMAT_SINGLE, Specifications::TYPE_INTEGER),
            new Specifications(Operator::GTE, Specifications::FORMAT_SINGLE, Specifications::TYPE_INTEGER),
            new Specifications(Operator::LT, Specifications::FORMAT_SINGLE, Specifications::TYPE_INTEGER),
            new Specifications(Operator::LTE, Specifications::FORMAT_SINGLE, Specifications::TYPE_INTEGER),
        ];
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;

/**
 * A criterion that matches content based on its state.
 *
 * Supported operators:
 * - IN: matches against a list of object state IDs
 * - EQ: matches against one object state ID
 */
class ObjectStateId extends Criterion implements FilteringCriterion
{
    /**
     * Creates a new ObjectStateId criterion.
     *
     * @param int|int[] $value One or more object state IDs that must be matched
     *
     * @throws \InvalidArgumentException if a non-numeric id is given
     * @throws \InvalidArgumentException if the value type doesn't match the operator
     */
    public function __construct(int|array $value)
    {
        parent::__construct(null, null, $value);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::IN,
                Specifications::FORMAT_ARRAY,
                Specifications::TYPE_INTEGER | Specifications::TYPE_STRING
            ),
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_INTEGER | Specifications::TYPE_STRING
            ),
        ];
    }
}

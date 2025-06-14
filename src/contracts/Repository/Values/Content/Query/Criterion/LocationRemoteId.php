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
 * A criterion that matches content based on remote ID of its locations.
 *
 * Supported operators:
 * - IN: will match from a list of location remote IDs
 * - EQ: will match against one location remote ID
 */
class LocationRemoteId extends Criterion implements FilteringCriterion
{
    /**
     * Creates a new location remote id criterion.
     *
     * @param string|string[] $value One or more locationRemoteId that must be matched
     *
     * @throws \InvalidArgumentException if a non-string remove id is given
     * @throws \InvalidArgumentException if the value type doesn't match the operator
     */
    public function __construct(string|array $value)
    {
        parent::__construct(null, null, $value);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::IN,
                Specifications::FORMAT_ARRAY,
                Specifications::TYPE_STRING
            ),
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_STRING
            ),
        ];
    }
}

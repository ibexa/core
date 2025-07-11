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
use Ibexa\Contracts\Core\Repository\Values\Trash\Query\Criterion as TrashCriterion;

/**
 * SectionId Criterion.
 *
 * Will match content that belongs to one of the given sections
 */
class SectionId extends Criterion implements TrashCriterion, FilteringCriterion
{
    /**
     * Creates a new Section criterion.
     *
     * Matches the content against one or more sectionId
     *
     * @param int|int[] $value One or more sectionId that must be matched
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
                Specifications::TYPE_INTEGER
            ),
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_INTEGER
            ),
        ];
    }
}

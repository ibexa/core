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
 * A criterion that will match content based on its ContentTypeGroup id.
 * The ContentType must belong to at least one of the matched ContentTypeGroups.
 *
 * Supported operators:
 * - IN: will match from a list of ContentTypeGroup id
 * - EQ: will match against one ContentTypeGroup id
 */
class ContentTypeGroupId extends Criterion implements FilteringCriterion
{
    /**
     * Creates a new ContentTypeGroup criterion.
     *
     * Content will be matched if it matches one of the contentTypeGroupId in $value
     *
     * @param int|int[] $value One or more contentTypeGroupId that must be matched
     *
     * @throws \InvalidArgumentException if the parameters don't match what the criterion expects
     */
    public function __construct(int|array $value)
    {
        parent::__construct(null, null, $value);
    }

    public function getSpecifications(): array
    {
        $types = Specifications::TYPE_INTEGER | Specifications::TYPE_STRING;

        return [
            new Specifications(
                Operator::IN,
                Specifications::FORMAT_ARRAY,
                $types
            ),
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                $types
            ),
        ];
    }
}

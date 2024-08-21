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
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Value\IsBookmarkedValue;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;

final class IsBookmarked extends Location implements FilteringCriterion
{
    public function __construct(?int $userId = null)
    {
        $valueData = new IsBookmarkedValue($userId);

        parent::__construct(
            null,
            Operator::EQ,
            true,
            $valueData
        );
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_BOOLEAN
            ),
        ];
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\QueryType\BuiltIn;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Ancestor;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LocationId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalNot;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\MatchNone;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;

final class AncestorsQueryType extends AbstractLocationQueryType
{
    public static function getName(): string
    {
        return 'Ancestors';
    }

    protected function getQueryFilter(array $parameters): CriterionInterface
    {
        $location = $this->resolveLocation($parameters);

        if ($location === null) {
            return new MatchNone();
        }

        return new LogicalAnd([
            new Ancestor($location->pathString),
            new LogicalNot(
                new LocationId($location->id)
            ),
        ]);
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\UserGroup;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException;
use Ibexa\Contracts\Core\Repository\Values\User\Query\UserGroupCriterionInterface;

/**
 * Matches items matching all of the given criteria. An empty list matches everything.
 */
final readonly class LogicalAnd implements UserGroupCriterionInterface
{
    /** @var list<\Ibexa\Contracts\Core\Repository\Values\User\Query\UserGroupCriterionInterface> */
    private array $criteria;

    /**
     * @param list<\Ibexa\Contracts\Core\Repository\Values\User\Query\UserGroupCriterionInterface> $criteria
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     */
    public function __construct(array $criteria)
    {
        $validCriteria = [];
        foreach ($criteria as $key => $criterion) {
            if (!$criterion instanceof UserGroupCriterionInterface) {
                throw new InvalidCriterionArgumentException($key, $criterion, UserGroupCriterionInterface::class);
            }

            $validCriteria[] = $criterion;
        }

        $this->criteria = $validCriteria;
    }

    /**
     * @return list<\Ibexa\Contracts\Core\Repository\Values\User\Query\UserGroupCriterionInterface>
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }
}

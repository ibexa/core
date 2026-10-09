<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\User;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException;
use Ibexa\Contracts\Core\Repository\Values\User\Query\UserCriterionInterface;

/**
 * Matches items matching all of the given criteria. An empty list matches everything.
 */
final readonly class LogicalAnd implements UserCriterionInterface
{
    /** @var list<\Ibexa\Contracts\Core\Repository\Values\User\Query\UserCriterionInterface> */
    private array $criteria;

    /**
     * @param list<\Ibexa\Contracts\Core\Repository\Values\User\Query\UserCriterionInterface> $criteria
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     */
    public function __construct(array $criteria)
    {
        $validCriteria = [];
        foreach ($criteria as $key => $criterion) {
            if (!$criterion instanceof UserCriterionInterface) {
                throw new InvalidCriterionArgumentException($key, $criterion, UserCriterionInterface::class);
            }

            $validCriteria[] = $criterion;
        }

        $this->criteria = $validCriteria;
    }

    /**
     * @return list<\Ibexa\Contracts\Core\Repository\Values\User\Query\UserCriterionInterface>
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }
}

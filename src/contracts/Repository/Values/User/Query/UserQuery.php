<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User\Query;

/**
 * Query for {@see \Ibexa\Contracts\Core\Repository\UserService::findUsers()}.
 */
final class UserQuery extends AbstractQuery
{
    private ?UserCriterionInterface $criterion;

    /**
     * @param list<\Ibexa\Contracts\Core\Repository\Values\User\Query\SortClause> $sortClauses
     */
    public function __construct(
        ?UserCriterionInterface $criterion = null,
        array $sortClauses = [],
        int $offset = 0,
        ?int $limit = self::DEFAULT_LIMIT
    ) {
        parent::__construct($sortClauses, $offset, $limit);
        $this->criterion = $criterion;
    }

    public function getCriterion(): ?UserCriterionInterface
    {
        return $this->criterion;
    }

    public function setCriterion(?UserCriterionInterface $criterion): void
    {
        $this->criterion = $criterion;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Notification\Query;

final class NotificationQuery
{
    /** @var CriterionInterface[] */
    private array $criteria;

    private int $offset;

    private int $limit;

    /**
     * @param CriterionInterface[] $criteria
     */
    public function __construct(
        array $criteria = [],
        int $offset = 0,
        int $limit = 25
    ) {
        $this->criteria = $criteria;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function addCriterion(CriterionInterface $criterion): void
    {
        $this->criteria[] = $criterion;
    }

    /**
     * @return CriterionInterface[]
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }
}

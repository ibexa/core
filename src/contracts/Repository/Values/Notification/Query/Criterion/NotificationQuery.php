<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion;

final class NotificationQuery
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion[] */
    public array $criteria = [];

    public int $offset = 0;

    public int $limit = 25;

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion[] $criteria
     */
    public function __construct(array $criteria = [], int $offset = 0, int $limit = 25)
    {
        $this->criteria = $criteria;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function addCriterion(Criterion $criterion): void
    {
        $this->criteria[] = $criterion;
    }
}

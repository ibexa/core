<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;

abstract class LogicalOperator implements CriterionInterface
{
    /**
     * @var list<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface>
     */
    private array $criteria = [];

    /**
     * @param list<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface> $criteria
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     */
    public function __construct(array $criteria)
    {
        foreach ($criteria as $key => $criterion) {
            if (!$criterion instanceof CriterionInterface) {
                throw new InvalidCriterionArgumentException($key, $criterion, CriterionInterface::class);
            }

            $this->criteria[] = $criterion;
        }
    }

    /**
     * @return list<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface>
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }
}

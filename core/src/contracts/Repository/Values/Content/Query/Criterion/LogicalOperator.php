<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;

/**
 * Note that the class should ideally have been in a Logical namespace, but it would have then be named 'And',
 * and 'And' is a PHP reserved word.
 */
abstract class LogicalOperator implements CriterionInterface
{
    /**
     * The set of criteria combined by the logical operator.
     *
     * @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface[]
     */
    public array $criteria = [];

    /**
     * Creates a Logic operation with the given criteria.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface[] $criteria
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
}

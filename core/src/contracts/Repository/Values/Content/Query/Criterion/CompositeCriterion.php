<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;

abstract class CompositeCriterion extends Criterion
{
    public CriterionInterface $criteria;

    public function __construct(CriterionInterface $criteria)
    {
        $this->criteria = $criteria;
    }

    public function getSpecifications(): array
    {
        throw new NotImplementedException('getSpecifications() not implemented for CompositeCriterion');
    }
}

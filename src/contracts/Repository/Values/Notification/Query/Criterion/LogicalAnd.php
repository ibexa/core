<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion;

final class LogicalAnd extends Criterion
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion[] */
    public array $criteria;

    public function __construct(Criterion ...$criteria)
    {
        $this->criteria = $criteria;
    }
}

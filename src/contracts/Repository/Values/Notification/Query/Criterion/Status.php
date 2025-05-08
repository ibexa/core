<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion;

final class Status extends Criterion
{
    /** @var string[] */
    public array $statuses;

    /**
     * @param string[] $statuses
     */
    public function __construct(array $statuses)
    {
        $this->statuses = $statuses;
    }
}

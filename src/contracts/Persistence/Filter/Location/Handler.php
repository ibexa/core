<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Filter\Location;

use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;

/**
 * Location Filtering ContentHandler.
 *
 * @internal for internal use by Repository
 */
interface Handler
{
    /**
     * @return LazyLocationListIterator
     */
    public function find(Filter $filter): iterable;

    public function count(Filter $filter): int;
}

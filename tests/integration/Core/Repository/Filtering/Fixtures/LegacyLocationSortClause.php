<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering\Fixtures;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;

final class LegacyLocationSortClause extends SortClause implements FilteringSortClause
{
    public function __construct(string $sortDirection)
    {
        parent::__construct('legacy_location_depth', $sortDirection);
    }
}

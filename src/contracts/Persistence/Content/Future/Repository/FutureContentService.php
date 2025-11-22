<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Future\Repository;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;

/**
 * @internal Future version of ContentService to be released in Ibexa Core 5.0. Used to force value proxies to generate correct types. For internal use only.
 */
interface FutureContentService extends ContentService
{
    /**
     * {@inheritDoc}
     */
    public function count(Filter $filter, ?array $languages = null, ?int $limit = null): int;
}

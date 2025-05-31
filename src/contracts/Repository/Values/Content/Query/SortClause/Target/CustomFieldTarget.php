<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target;

final class CustomFieldTarget extends Target
{
    public string $fieldName;

    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }
}

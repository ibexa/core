<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Value;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Value;

final class IsBookmarkedValue extends Value
{
    private ?int $userId;

    public function __construct(?int $userId)
    {
        $this->userId = $userId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
}

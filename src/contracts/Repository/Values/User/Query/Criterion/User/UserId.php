<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\User;

use Ibexa\Contracts\Core\Repository\Values\User\Query\UserCriterionInterface;

/**
 * Matches users by their (content) ID(s).
 */
final readonly class UserId implements UserCriterionInterface
{
    /**
     * @param int|list<int> $value
     */
    public function __construct(
        private int|array $value
    ) {
    }

    /**
     * @return int|list<int>
     */
    public function getValue(): int|array
    {
        return $this->value;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\UserGroup;

use Ibexa\Contracts\Core\Repository\Values\User\Query\UserGroupCriterionInterface;

/**
 * Matches user groups the given user is directly assigned to (parents of the user's locations).
 */
final readonly class UserId implements UserGroupCriterionInterface
{
    public function __construct(
        private int $value
    ) {
    }

    public function getValue(): int
    {
        return $this->value;
    }
}

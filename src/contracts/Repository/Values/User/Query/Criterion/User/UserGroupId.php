<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\User;

use Ibexa\Contracts\Core\Repository\Values\User\Query\UserCriterionInterface;

/**
 * Matches users directly assigned to the given user group (children of the group's main location).
 */
final readonly class UserGroupId implements UserCriterionInterface
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

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\UserGroup;

use Ibexa\Contracts\Core\Repository\Values\User\Query\UserGroupCriterionInterface;

/**
 * Matches direct sub-groups of the given user group (children of the group's main location).
 */
final readonly class ParentUserGroupId implements UserGroupCriterionInterface
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

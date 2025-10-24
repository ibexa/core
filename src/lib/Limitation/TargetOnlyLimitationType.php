<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Type as LimitationTypeInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Core\Repository\Permission\PermissionCriterionResolver;

/**
 * Limitation type which doesn't take $object into consideration while evaluation.
 *
 * @see PermissionCriterionResolver::getPermissionsCriterion
 */
interface TargetOnlyLimitationType extends LimitationTypeInterface
{
    /**
     * Returns criterion based on given $target for use in find() query.
     *
     * @param Limitation $value
     * @param UserReference $currentUser
     * @param array|null $targets
     *
     * @return CriterionInterface
     *
     * @throws InvalidArgumentException
     */
    public function getCriterionByTarget(
        APILimitationValue $value,
        APIUserReference $currentUser,
        ?array $targets
    ): CriterionInterface;
}

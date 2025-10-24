<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Values\User\Policy;
use Ibexa\Contracts\Core\Repository\Values\User\Role as APIRole;

/**
 * This class represents a role.
 *
 * @property-read Policy[] $policies Policies assigned to this role
 *
 * @internal Meant for internal use by Repository, type hint against API object instead.
 */
class Role extends APIRole
{
    /**
     * Policies assigned to this role.
     *
     * @var Policy[]
     */
    protected $policies = [];

    /**
     * Returns the list of policies of this role.
     *
     * @return Policy[]
     */
    public function getPolicies(): iterable
    {
        return $this->policies;
    }
}

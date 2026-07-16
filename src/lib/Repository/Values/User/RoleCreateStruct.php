<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct as APIPolicyCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\RoleCreateStruct as APIRoleCreateStruct;

/**
 * This class is used to create a new role.
 *
 * @internal Meant for internal use by Repository, type hint against API instead.
 */
class RoleCreateStruct extends APIRoleCreateStruct
{
    /**
     * Policies associated with the role.
     *
     * @var PolicyCreateStruct[]
     */
    protected $policies = [];

    /**
     * Returns policies associated with the role.
     *
     * @return PolicyCreateStruct[]
     */
    public function getPolicies(): iterable
    {
        return $this->policies;
    }

    /**
     * Adds a policy to this role.
     *
     * @param PolicyCreateStruct $policyCreateStruct
     */
    public function addPolicy(APIPolicyCreateStruct $policyCreateStruct): void
    {
        $this->policies[] = $policyCreateStruct;
    }
}

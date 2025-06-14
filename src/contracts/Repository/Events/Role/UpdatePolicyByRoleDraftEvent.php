<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Role;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;

final class UpdatePolicyByRoleDraftEvent extends AfterEvent
{
    private RoleDraft $roleDraft;

    private PolicyDraft $policy;

    private PolicyUpdateStruct $policyUpdateStruct;

    private PolicyDraft $updatedPolicyDraft;

    public function __construct(
        PolicyDraft $updatedPolicyDraft,
        RoleDraft $roleDraft,
        PolicyDraft $policy,
        PolicyUpdateStruct $policyUpdateStruct
    ) {
        $this->roleDraft = $roleDraft;
        $this->policy = $policy;
        $this->policyUpdateStruct = $policyUpdateStruct;
        $this->updatedPolicyDraft = $updatedPolicyDraft;
    }

    public function getRoleDraft(): RoleDraft
    {
        return $this->roleDraft;
    }

    public function getPolicy(): PolicyDraft
    {
        return $this->policy;
    }

    public function getPolicyUpdateStruct(): PolicyUpdateStruct
    {
        return $this->policyUpdateStruct;
    }

    public function getUpdatedPolicyDraft(): PolicyDraft
    {
        return $this->updatedPolicyDraft;
    }
}

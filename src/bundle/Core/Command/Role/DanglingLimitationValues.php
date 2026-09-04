<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command\Role;

use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Policy;
use Ibexa\Contracts\Core\Repository\Values\User\Role;

/**
 * @internal
 */
final class DanglingLimitationValues
{
    private Role $role;

    private Policy $policy;

    private Limitation $limitation;

    /** @var string[] */
    private array $danglingValues;

    /**
     * @param string[] $danglingValues
     */
    public function __construct(Role $role, Policy $policy, Limitation $limitation, array $danglingValues)
    {
        $this->role = $role;
        $this->policy = $policy;
        $this->limitation = $limitation;
        $this->danglingValues = $danglingValues;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getPolicy(): Policy
    {
        return $this->policy;
    }

    public function getLimitation(): Limitation
    {
        return $this->limitation;
    }

    /**
     * @return string[]
     */
    public function getDanglingValues(): array
    {
        return $this->danglingValues;
    }

    public function emptiesLimitation(): bool
    {
        return array_diff($this->limitation->limitationValues, $this->danglingValues) === [];
    }
}

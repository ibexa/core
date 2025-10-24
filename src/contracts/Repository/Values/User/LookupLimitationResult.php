<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a LookupLimitation for module and function in the context of current User.
 */
final class LookupLimitationResult extends ValueObject
{
    /** @var bool */
    protected bool $hasAccess;

    /** @var Limitation[] */
    protected array $roleLimitations;

    /** @var LookupPolicyLimitations[] */
    protected array $lookupPolicyLimitations;

    /**
     * @param Limitation[] $roleLimitations
     * @param LookupPolicyLimitations[] $lookupPolicyLimitations
     */
    public function __construct(
        bool $hasAccess,
        array $roleLimitations = [],
        array $lookupPolicyLimitations = []
    ) {
        parent::__construct();

        $this->hasAccess = $hasAccess;
        $this->lookupPolicyLimitations = $lookupPolicyLimitations;
        $this->roleLimitations = $roleLimitations;
    }

    public function hasAccess(): bool
    {
        return $this->hasAccess;
    }

    /**
     * @return Limitation[]
     */
    public function getRoleLimitations(): array
    {
        return $this->roleLimitations;
    }

    /**
     * @return LookupPolicyLimitations[]
     */
    public function getLookupPolicyLimitations(): array
    {
        return $this->lookupPolicyLimitations;
    }
}

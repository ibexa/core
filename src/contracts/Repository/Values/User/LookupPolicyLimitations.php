<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a result of lookup limitation for module and function in the context of current User.
 */
final class LookupPolicyLimitations extends ValueObject
{
    /** @var Policy */
    protected $policy;

    /** @var Limitation[] */
    protected $limitations;

    /**
     * @param Policy $policy
     * @param Limitation[] $limitations
     */
    public function __construct(
        Policy $policy,
        array $limitations = []
    ) {
        parent::__construct();

        $this->policy = $policy;
        $this->limitations = $limitations;
    }
}

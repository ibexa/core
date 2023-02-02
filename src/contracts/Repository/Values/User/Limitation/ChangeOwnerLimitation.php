<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User\Limitation;

use Ibexa\Contracts\Core\Repository\Values\User\Limitation;

final class ChangeOwnerLimitation extends Limitation
{
    public const IDENTIFIER = 'ChangeOwner';

    public const LIMITATION_VALUE_SELF = -1;

    /**
     * @param int[] $limitationValues
     */
    public function __construct(array $limitationValues)
    {
        parent::__construct([
            'limitationValues' => $limitationValues,
        ]);
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }
}

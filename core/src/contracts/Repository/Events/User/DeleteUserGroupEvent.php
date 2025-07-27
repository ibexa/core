<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;

final class DeleteUserGroupEvent extends AfterEvent
{
    private UserGroup $userGroup;

    /** @var int[] */
    private array $locations;

    /**
     * @param int[] $locations
     */
    public function __construct(
        array $locations,
        UserGroup $userGroup
    ) {
        $this->userGroup = $userGroup;
        $this->locations = $locations;
    }

    public function getUserGroup(): UserGroup
    {
        return $this->userGroup;
    }

    /**
     * @return int[]
     */
    public function getLocations(): array
    {
        return $this->locations;
    }
}

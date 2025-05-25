<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\User\User;

final class DeleteUserEvent extends AfterEvent
{
    private User $user;

    /** @var int[] */
    private array $locations;

    /**
     * @param int[] $locations
     */
    public function __construct(
        array $locations,
        User $user
    ) {
        $this->user = $user;
        $this->locations = $locations;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return int[]
     */
    public function getLocations(): array
    {
        return $this->locations;
    }
}

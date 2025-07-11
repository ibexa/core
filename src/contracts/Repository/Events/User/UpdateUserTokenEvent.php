<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserTokenUpdateStruct;

final class UpdateUserTokenEvent extends AfterEvent
{
    private User $user;

    private UserTokenUpdateStruct $userTokenUpdateStruct;

    private User $updatedUser;

    public function __construct(
        User $updatedUser,
        User $user,
        UserTokenUpdateStruct $userTokenUpdateStruct
    ) {
        $this->user = $user;
        $this->userTokenUpdateStruct = $userTokenUpdateStruct;
        $this->updatedUser = $updatedUser;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserTokenUpdateStruct(): UserTokenUpdateStruct
    {
        return $this->userTokenUpdateStruct;
    }

    public function getUpdatedUser(): User
    {
        return $this->updatedUser;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\User\User;

final class UpdateUserPasswordEvent extends AfterEvent
{
    private User $user;

    private string $newPassword;

    private User $updatedUser;

    public function __construct(
        User $updatedUser,
        User $user,
        #[\SensitiveParameter]
        string $newPassword
    ) {
        $this->user = $user;
        $this->newPassword = $newPassword;
        $this->updatedUser = $updatedUser;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    public function getUpdatedUser(): User
    {
        return $this->updatedUser;
    }
}

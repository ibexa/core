<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use UnexpectedValueException;

final class BeforeUpdateUserPasswordEvent extends BeforeEvent
{
    private User $user;

    private string $newPassword;

    private ?User $updatedUser = null;

    public function __construct(User $user, string $newPassword)
    {
        $this->user = $user;
        $this->newPassword = $newPassword;
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
        if (!$this->hasUpdatedUser()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasUpdatedUser() or set it using setUpdatedUser() before you call the getter.', User::class));
        }

        return $this->updatedUser;
    }

    public function setUpdatedUser(?User $updatedUser): void
    {
        $this->updatedUser = $updatedUser;
    }

    /**
     * @phpstan-assert-if-true !null $this->updatedUser
     */
    public function hasUpdatedUser(): bool
    {
        return $this->updatedUser instanceof User;
    }
}

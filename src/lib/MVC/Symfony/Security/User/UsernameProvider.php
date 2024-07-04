<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Security\User;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

final class UsernameProvider extends BaseProvider
{
    public function loadUserByUsername(string $username): UserInterface
    {
        try {
            return $this->createSecurityUser(
                $this->userService->loadUserByLogin($username)
            );
        } catch (NotFoundException $e) {
            throw new UserNotFoundException($e->getMessage(), 0, $e);
        }
    }
}

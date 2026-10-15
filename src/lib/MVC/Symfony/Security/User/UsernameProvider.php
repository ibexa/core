<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Security\User;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

final class UsernameProvider extends BaseProvider
{
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            return $this->createSecurityUser(
                $this->userService->loadUserByLogin($identifier)
            );
        } catch (NotFoundException | InvalidArgumentException $e) {
            throw new UserNotFoundException($e->getMessage(), 0, $e);
        }
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }
}

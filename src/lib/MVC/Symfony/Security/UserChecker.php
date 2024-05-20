<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Security;

use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Core\MVC\Symfony\Security\Exception\PasswordExpiredException;
use Ibexa\Core\MVC\Symfony\Security\UserInterface as IbexaUserInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    /** @var \Ibexa\Contracts\Core\Repository\UserService */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof IbexaUserInterface) {
            return;
        }

        if (!$user->getAPIUser()->enabled) {
            $exception = new DisabledException('User account is locked.');
            $exception->setUser($user);

            throw $exception;
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof IbexaUserInterface) {
            return;
        }

        if ($this->userService->getPasswordInfo($user->getAPIUser())->isPasswordExpired()) {
            $exception = new PasswordExpiredException('User account has expired.');
            $exception->setUser($user);

            throw $exception;
        }
    }
}

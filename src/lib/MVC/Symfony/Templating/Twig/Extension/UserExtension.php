<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class UserExtension extends AbstractExtension
{
    private UserService $userService;

    private PermissionResolver $permissionResolver;

    public function __construct(UserService $userService, PermissionResolver $permissionResolver)
    {
        $this->userService = $userService;
        $this->permissionResolver = $permissionResolver;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ibexa_current_user',
                $this->getCurrentUser(...)
            ),
            new TwigFunction(
                'ibexa_is_current_user',
                $this->isCurrentUser(...)
            ),
        ];
    }

    public function getCurrentUser(): User
    {
        return $this->userService->loadUser(
            $this->permissionResolver->getCurrentUserReference()->getUserId()
        );
    }

    public function isCurrentUser(User $user): bool
    {
        return $this->permissionResolver->getCurrentUserReference()->getUserId() === $user->getUserId();
    }
}

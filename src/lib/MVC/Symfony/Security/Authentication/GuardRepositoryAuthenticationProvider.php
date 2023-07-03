<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Security\Authentication;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Core\MVC\Symfony\Security\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Guard\Provider\GuardAuthenticationProvider;

final class GuardRepositoryAuthenticationProvider extends GuardAuthenticationProvider
{
    private PermissionResolver $permissionResolver;

    public function setPermissionResolver(PermissionResolver $permissionResolver): void
    {
        $this->permissionResolver = $permissionResolver;
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        $authenticatedToken = parent::authenticate($token);
        if (empty($authenticatedToken)) {
            throw new AuthenticationException('The token is not supported by this authentication provider.');
        }

        if ($authenticatedToken->getUser() instanceof UserInterface) {
            $this->permissionResolver->setCurrentUserReference(
                $authenticatedToken->getUser()->getAPIUser()
            );
        }

        return $authenticatedToken;
    }
}

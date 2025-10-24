<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Security\User;

use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Core\MVC\Symfony\Security\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Interface adding Ibexa API specific methods to Symfony UserProviderInterface.
 *
 * @extends \Symfony\Component\Security\Core\User\UserProviderInterface<\Ibexa\Core\MVC\Symfony\Security\UserInterface>
 */
interface APIUserProviderInterface extends UserProviderInterface
{
    /**
     * Loads a regular user object, usable by Symfony Security component, from a user object returned by Public API.
     *
     * @param APIUser $apiUser
     *
     * @return User
     */
    public function loadUserByAPIUser(APIUser $apiUser);
}

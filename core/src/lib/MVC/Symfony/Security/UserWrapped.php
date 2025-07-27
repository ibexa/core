<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Security;

use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Core\Repository\Values\User\UserReference;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This class represents a UserWrapped object.
 *
 * It's used when working with multiple user providers
 *
 * It has two properties:
 *     - wrappedUser: containing the originally matched user.
 *     - apiUser: containing the API User (the one from the eZ Repository )
 */
class UserWrapped implements ReferenceUserInterface, EquatableInterface
{
    private UserInterface $wrappedUser;

    private ?APIUser $apiUser = null;

    private APIUserReference $apiUserReference;

    public function __construct(UserInterface $wrappedUser, APIUser $apiUser)
    {
        $this->setWrappedUser($wrappedUser);
        $this->apiUser = $apiUser;
        $this->apiUserReference = new UserReference($apiUser->getUserId());
    }

    public function __toString(): string
    {
        return $this->wrappedUser->getUserIdentifier();
    }

    public function setAPIUser(APIUser $apiUser): void
    {
        $this->apiUser = $apiUser;
        $this->apiUserReference = new UserReference($apiUser->getUserId());
    }

    public function getAPIUser(): APIUser
    {
        if ($this->apiUser === null) {
            throw new LogicException(
                'Attempted to get APIUser before it has been set by UserProvider, APIUser is not serialized to session'
            );
        }

        return $this->apiUser;
    }

    public function getAPIUserReference(): APIUserReference
    {
        return $this->apiUserReference;
    }

    /**
     * @throws \InvalidArgumentException If $wrappedUser is instance of self or User to avoid duplicated APIUser in
     *     session.
     */
    public function setWrappedUser(UserInterface $wrappedUser): void
    {
        if ($wrappedUser instanceof self) {
            throw new InvalidArgumentException('Injecting UserWrapped in itself is not allowed to avoid recursion');
        }

        if ($wrappedUser instanceof User) {
            throw new InvalidArgumentException('Injecting a User into UserWrapped causes duplication of APIUser, which should be avoided for session serialization');
        }

        $this->wrappedUser = $wrappedUser;
    }

    public function getWrappedUser(): UserInterface
    {
        return $this->wrappedUser;
    }

    public function getRoles(): array
    {
        return $this->wrappedUser->getRoles();
    }

    public function eraseCredentials(): void
    {
        $this->wrappedUser->eraseCredentials();
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if ($user instanceof self) {
            $user = $user->wrappedUser;
        }

        return $this->wrappedUser instanceof EquatableInterface ? $this->wrappedUser->isEqualTo($user) : true;
    }

    /**
     * @see \Ibexa\Core\MVC\Symfony\Security\User::__sleep
     */
    public function __sleep(): array
    {
        return ['wrappedUser', 'apiUserReference'];
    }

    public function getUserIdentifier(): string
    {
        return $this->wrappedUser->getUserIdentifier();
    }
}

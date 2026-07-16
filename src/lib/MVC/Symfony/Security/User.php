<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Security;

use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Core\MVC\Symfony\Security\User\BaseProvider;
use Ibexa\Core\Repository\Values\User\UserReference;
use Stringable;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;

class User implements ReferenceUserInterface, EquatableInterface, PasswordAuthenticatedUserInterface, Stringable
{
    private APIUser $user;

    private UserReference $reference;

    /** @var string[] */
    private array $roles;

    /**
     * @param string[] $roles
     */
    public function __construct(
        APIUser $user,
        array $roles = []
    ) {
        $this->user = $user;
        $this->reference = new UserReference($user->getUserId());
        $this->roles = $roles;
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array( 'ROLE_USER' );
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     */
    public function getPassword(): string
    {
        return $this->getAPIUser()->getPasswordHash();
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getUserIdentifier(): string
    {
        return $this->getAPIUser()->getLogin();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void {}

    public function getAPIUserReference(): UserReference
    {
        return $this->reference;
    }

    public function getAPIUser(): APIUser
    {
        if (!$this->user instanceof APIUser) {
            throw new \LogicException(
                'Attempted to get APIUser before it has been set by UserProvider, APIUser is not serialized to session'
            );
        }

        return $this->user;
    }

    public function setAPIUser(APIUser $apiUser): void
    {
        $this->user = $apiUser;
        $this->reference = new UserReference($apiUser->getUserId());
    }

    public function isEqualTo(BaseUserInterface $user): bool
    {
        // Check for the lighter ReferenceUserInterface first
        if ($user instanceof ReferenceUserInterface) {
            return $user->getAPIUserReference()->getUserId() === $this->reference->getUserId();
        } elseif ($user instanceof UserInterface) {
            return $user->getAPIUser()->getUserId() === $this->reference->getUserId();
        }

        return false;
    }

    public function __toString(): string
    {
        return $this->getAPIUser()->getContentInfo()->getName();
    }

    /**
     * Make sure we don't serialize the whole API user object given it's a full fledged api content object. We set
     * (& either way refresh) the user object in {@see BaseProvider::refreshUser}
     * when object wakes back up from session.
     *
     * @return string[]
     */
    public function __sleep(): array
    {
        return ['reference', 'roles'];
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * This token is used when a user has been matched by a foreign user provider.
 * It is injected in SecurityContext to replace the original token as this one holds a new user.
 */
class InteractiveLoginToken extends UsernamePasswordToken
{
    private ?TokenInterface $originalToken = null;

    private string $originalTokenType;

    public function __construct(UserInterface $user, $originalTokenType, $credentials, $providerKey, array $roles = [])
    {
        parent::__construct($user, $credentials, $providerKey, $roles);

        $this->originalTokenType = $originalTokenType;
    }

    public function getOriginalTokenType(): string
    {
        return $this->originalTokenType;
    }

    /**
     * @return array{
     *     string,
     *     mixed,
     *     null|\Symfony\Component\Security\Core\Authentication\Token\TokenInterface
     * } $data
     */
    public function __serialize(): array
    {
        return [
            $this->originalTokenType,
            parent::__serialize(),
            $this->originalToken,
        ];
    }

    /**
     * @param array{
     *     string,
     *     mixed,
     *     2?: \Symfony\Component\Security\Core\Authentication\Token\TokenInterface
     * } $data
     */
    public function __unserialize(array $data): void
    {
        if (isset($data[2])) {
            [$this->originalTokenType, $parentData, $this->originalToken] = $data;
        } else {
            [$this->originalTokenType, $parentData] = $data;
        }

        parent::__unserialize($parentData);
    }

    public function setOriginalToken(TokenInterface $token): void
    {
        $this->originalToken = $token;
    }

    public function getOriginalToken(): ?TokenInterface
    {
        return $this->originalToken;
    }

    public function isAuthenticated(): bool
    {
        if (null !== $this->originalToken) {
            return $this->originalToken->isAuthenticated();
        }

        return parent::isAuthenticated();
    }
}

class_alias(InteractiveLoginToken::class, 'eZ\Publish\Core\MVC\Symfony\Security\InteractiveLoginToken');

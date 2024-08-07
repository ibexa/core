<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Security\Authentication;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Core\MVC\Symfony\Security\Authentication\GuardRepositoryAuthenticationProvider;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Security\Guard\Token\PreAuthenticationGuardToken;

final class GuardRepositoryAuthenticationProviderTest extends TestCase
{
    private GuardRepositoryAuthenticationProvider $authProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $user = self::createMock(UserInterface::class);

        $guardAuthenticator = self::createMock(AuthenticatorInterface::class);
        $guardAuthenticator
            ->method('getUser')
            ->willReturn($user);

        $guardAuthenticator
            ->method('checkCredentials')
            ->willReturn(true);

        $guardAuthenticator
            ->method('createAuthenticatedToken')
            ->willReturn(new PostAuthenticationGuardToken(
                $user,
                'provider-key_authenticator',
                []
            ));

        $this->authProvider = new GuardRepositoryAuthenticationProvider(
            ['authenticator' => $guardAuthenticator],
            self::createMock(UserProviderInterface::class),
            'provider-key',
            self::createMock(UserCheckerInterface::class),
            self::createMock(UserPasswordHasherInterface::class)
        );

        $this->authProvider->setPermissionResolver(
            self::createMock(PermissionResolver::class)
        );
    }

    public function testAuthenticateUnsupportedToken()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('GuardAuthenticationProvider only supports GuardTokenInterface.');

        $anonymousToken = self::getMockBuilder(AnonymousToken::class)
            ->setConstructorArgs(['secret', self::createMock(UserInterface::class)])
            ->getMock();

        $this->authProvider->authenticate($anonymousToken);
    }

    public function testAuthenticateWrongGuardProviderKey()
    {
        self::expectException(AuthenticationException::class);
        self::expectExceptionMessage(sprintf(
            'Token with provider key "%s" did not originate from any of the guard authenticators of provider "%s".',
            'wrong-key_authenticator',
            'provider-key'
        ));

        $guardToken = new PreAuthenticationGuardToken(['test' => 'credentials'], 'wrong-key_authenticator');

        $this->authProvider->authenticate($guardToken);
    }

    public function testAuthenticate()
    {
        $guardToken = new PreAuthenticationGuardToken(['test' => 'credentials'], 'provider-key_authenticator');

        $authenticatedToken = $this->authProvider->authenticate($guardToken);
        self::assertEquals(
            [$guardToken->getGuardProviderKey(), []],
            [$authenticatedToken->getProviderKey(), $authenticatedToken->getCredentials()]
        );
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Security\Authentication;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\User\User as ApiUser;
use Ibexa\Core\MVC\Symfony\Security\Authentication\RememberMeRepositoryAuthenticationProvider;
use Ibexa\Core\MVC\Symfony\Security\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class RememberMeRepositoryAuthenticationProviderTest extends TestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Security\Authentication\RememberMeRepositoryAuthenticationProvider */
    private $authProvider;

    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $permissionResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionResolver = $this->createMock(PermissionResolver::class);
        $this->authProvider = new RememberMeRepositoryAuthenticationProvider(
            $this->createMock(UserCheckerInterface::class),
            'my secret',
            'my provider secret'
        );
        $this->authProvider->setPermissionResolver($this->permissionResolver);
    }

    public function testAuthenticateUnsupportedToken()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('The token is not supported by this authentication provider.');

        $anonymousToken = $this
            ->getMockBuilder(AnonymousToken::class)
            ->setConstructorArgs(['secret', $this->createMock(UserInterface::class)])
            ->getMock();
        $this->authProvider->authenticate($anonymousToken);
    }

    public function testAuthenticateWrongProviderKey()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('The token is not supported by this authentication provider.');

        $user = $this->createMock(UserInterface::class);
        $user
            ->expects(self::any())
            ->method('getRoles')
            ->will(self::returnValue([]));

        $rememberMeToken = $this
            ->getMockBuilder(RememberMeToken::class)
            ->setConstructorArgs([$user, 'wrong provider secret', 'my secret'])
            ->getMock();
        $rememberMeToken
            ->expects(self::any())
            ->method('getProviderKey')
            ->will(self::returnValue('wrong provider secret'));

        $this->authProvider->authenticate($rememberMeToken);
    }

    public function testAuthenticateWrongSecret()
    {
        $this->expectException(AuthenticationException::class);

        $user = $this->createMock(UserInterface::class);
        $user
            ->expects(self::any())
            ->method('getRoles')
            ->will(self::returnValue([]));

        $rememberMeToken = $this
            ->getMockBuilder(RememberMeToken::class)
            ->setConstructorArgs([$user, 'my provider secret', 'the wrong secret'])
            ->getMock();
        $rememberMeToken
            ->expects(self::any())
            ->method('getProviderKey')
            ->will(self::returnValue('my provider secret'));
        $rememberMeToken
            ->expects(self::any())
            ->method('getSecret')
            ->will(self::returnValue('the wrong secret'));

        $this->authProvider->authenticate($rememberMeToken);
    }

    public function testAuthenticate()
    {
        $apiUser = $this->createMock(ApiUser::class);
        $apiUser
            ->expects(self::any())
            ->method('getUserId')
            ->will(self::returnValue(42));

        $tokenUser = new User($apiUser);
        $rememberMeToken = new RememberMeToken($tokenUser, 'my provider secret', 'my secret');

        $authenticatedToken = $this->authProvider->authenticate($rememberMeToken);
        self::assertEquals(
            [$rememberMeToken->getProviderKey(), $rememberMeToken->getSecret(), $rememberMeToken->getUsername()],
            [$authenticatedToken->getProviderKey(), $authenticatedToken->getSecret(), $authenticatedToken->getUsername()]
        );
    }
}

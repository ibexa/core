<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Security\User;

use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\Security\User\BaseProvider;
use Ibexa\Core\MVC\Symfony\Security\User\EmailProvider;
use Ibexa\Core\MVC\Symfony\Security\UserInterface;
use Ibexa\Core\Repository\Values\User\UserReference;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Security\User\EmailProvider
 */
final class EmailProviderTest extends BaseProviderTestCase
{
    protected function buildProvider(): BaseProvider
    {
        return new EmailProvider($this->userService, $this->permissionResolver);
    }

    public function testLoadUserByUsernameUserNotFound(): void
    {
        $username = 'foobar@example.org';
        $this->userService
            ->expects(self::once())
            ->method('loadUserByEmail')
            ->with($username)
            ->willThrowException(new NotFoundException('user', $username));

        $this->expectException(UserNotFoundException::class);
        $this->userProvider->loadUserByIdentifier($username);
    }

    public function testLoadUserByUsername(): void
    {
        $username = 'foobar@example.org';
        $apiUser = $this->createMock(APIUser::class);

        $this->userService
            ->expects(self::once())
            ->method('loadUserByEmail')
            ->with($username)
            ->willReturn($apiUser);

        $user = $this->userProvider->loadUserByIdentifier($username);
        self::assertInstanceOf(UserInterface::class, $user);
        self::assertSame($apiUser, $user->getAPIUser());
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testRefreshUserNotSupported(): void
    {
        $user = $this->createMock(SymfonyUserInterface::class);

        $this->expectException(UnsupportedUserException::class);
        $this->userProvider->refreshUser($user);
    }

    public function testRefreshUser(): void
    {
        $userId = 123;
        $apiUser = $this->buildUserValueObjectStub($userId);
        $user = $this->createUserWrapperMockFromAPIUser($apiUser, $userId);

        $this->permissionResolver
            ->expects(self::once())
            ->method('setCurrentUserReference')
            ->with(new UserReference($apiUser->getUserId()));

        self::assertSame($user, $this->userProvider->refreshUser($user));
    }

    public function testRefreshUserNotFound(): void
    {
        $this->expectException(UsernameNotFoundException::class);

        $userId = 123;
        $apiUser = $this->buildUserValueObjectStub($userId);
        $user = $this->createMock(UserInterface::class);
        $user
            ->expects(self::once())
            ->method('getAPIUser')
            ->willReturn($apiUser);

        $this->userService
            ->expects(self::once())
            ->method('loadUser')
            ->with($userId)
            ->willThrowException(new NotFoundException('user', 'foo'));

        $this->userProvider->refreshUser($user);
    }
}

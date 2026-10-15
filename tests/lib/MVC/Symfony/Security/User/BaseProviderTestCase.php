<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Security\User;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\Security\User as MVCUser;
use Ibexa\Core\MVC\Symfony\Security\User\BaseProvider;
use Ibexa\Core\MVC\Symfony\Security\UserInterface;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\User\User;
use Ibexa\Core\Repository\Values\User\UserReference;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

abstract class BaseProviderTestCase extends TestCase
{
    protected UserService & MockObject $userService;

    protected PermissionResolver & MockObject $permissionResolver;

    protected BaseProvider $userProvider;

    abstract protected function buildProvider(): BaseProvider;

    abstract protected function getUserIdentifier(): string;

    abstract protected function getUserServiceMethod(): string;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = $this->createMock(UserService::class);
        $this->permissionResolver = $this->createMock(PermissionResolver::class);
        $this->userProvider = $this->buildProvider();
    }

    /**
     * @phpstan-return list<array{class-string<SymfonyUserInterface>, bool}>
     */
    public function supportsClassProvider(): array
    {
        return [
            [SymfonyUserInterface::class, false],
            [MVCUser::class, true],
            [get_class($this->createMock(MVCUser::class)), true],
        ];
    }

    /**
     * @dataProvider supportsClassProvider
     *
     * @phpstan-param class-string<SymfonyUserInterface> $class
     */
    public function testSupportsClass(
        string $class,
        bool $supports
    ): void {
        self::assertSame($supports, $this->userProvider->supportsClass($class));
    }

    public function testLoadUserByAPIUser(): void
    {
        $apiUser = $this->createMock(APIUser::class);

        $user = $this->userProvider->loadUserByAPIUser($apiUser);

        self::assertInstanceOf(MVCUser::class, $user);
        self::assertSame($apiUser, $user->getAPIUser());
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testRefreshUserNotFound(): void
    {
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

        $this->expectException(UserNotFoundException::class);
        $this->userProvider->refreshUser($user);
    }

    public function testRefreshUserNotSupported(): void
    {
        $user = $this->createMock(SymfonyUserInterface::class);

        $this->expectException(UnsupportedUserException::class);
        $this->userProvider->refreshUser($user);
    }

    protected function createUserWrapperMockFromAPIUser(
        User $apiUser,
        int $userId
    ): UserInterface & MockObject {
        $refreshedAPIUser = clone $apiUser;
        $user = $this->createMock(UserInterface::class);
        $user
            ->expects(self::once())
            ->method('getAPIUser')
            ->willReturn($apiUser)
        ;
        $user
            ->expects(self::once())
            ->method('setAPIUser')
            ->with($refreshedAPIUser)
        ;

        $this->userService
            ->expects(self::once())
            ->method('loadUser')
            ->with($userId)
            ->willReturn($refreshedAPIUser)
        ;

        return $user;
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

    public function testLoadUserByUsername(): void
    {
        $username = $this->getUserIdentifier();
        $apiUser = $this->createMock(APIUser::class);

        $this->userService
            ->expects(self::once())
            ->method($this->getUserServiceMethod())
            ->with($username)
            ->willReturn($apiUser);

        $user = $this->userProvider->loadUserByIdentifier($username);
        self::assertInstanceOf(UserInterface::class, $user);
        self::assertSame($apiUser, $user->getAPIUser());
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testLoadUserByUsernameUserNotFound(): void
    {
        $username = $this->getUserIdentifier();
        $this->userService
            ->expects(self::once())
            ->method($this->getUserServiceMethod())
            ->with($username)
            ->willThrowException(new NotFoundException('user', $username));

        $this->expectException(UserNotFoundException::class);
        $this->userProvider->loadUserByIdentifier($username);
    }

    final protected function buildUserValueObjectStub(int $userId): User
    {
        return new User(
            [
                'content' => new Content(
                    [
                        'versionInfo' => new VersionInfo(
                            ['contentInfo' => new ContentInfo(['id' => $userId])]
                        ),
                    ]
                ),
            ]
        );
    }
}

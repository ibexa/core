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
use Ibexa\Core\MVC\Symfony\Security\User as MVCUser;
use Ibexa\Core\MVC\Symfony\Security\User\BaseProvider;
use Ibexa\Core\MVC\Symfony\Security\UserInterface;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

abstract class BaseProviderTestCase extends TestCase
{
    protected UserService & MockObject $userService;

    protected PermissionResolver & MockObject $permissionResolver;

    protected BaseProvider $userProvider;

    abstract protected function buildProvider(): BaseProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = $this->createMock(UserService::class);
        $this->permissionResolver = $this->createMock(PermissionResolver::class);
        $this->userProvider = $this->buildProvider();
    }

    public function testLoadUserByUsernameAlreadyUserObject(): void
    {
        $user = $this->createMock(UserInterface::class);
        self::assertSame($user, $this->userProvider->loadUserByUsername($user));
    }

    /**
     * @phpstan-return list<array{class-string, bool}>
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
     * @phpstan-param class-string $class
     */
    public function testSupportsClass(string $class, bool $supports): void
    {
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

    protected function createUserWrapperMockFromAPIUser(User $apiUser, int $userId): UserInterface & MockObject
    {
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

    protected function buildUserValueObjectStub(int $userId): User
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

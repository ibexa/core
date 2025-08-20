<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Exception;
use Ibexa\Contracts\Core\Persistence\User\Handler as PersistenceUserHandler;
use Ibexa\Contracts\Core\Persistence\User\RoleAssignment;
use Ibexa\Contracts\Core\Repository\ContentService as APIContentService;
use Ibexa\Contracts\Core\Repository\PasswordHashService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\UserService as APIUserService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo as APIContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Repository\User\PasswordValidatorInterface;
use Ibexa\Core\Repository\UserService;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;

/**
 * @covers \Ibexa\Core\Repository\UserService
 */
class UserTest extends BaseServiceMockTest
{
    private const MOCKED_USER_ID = 42;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testDeleteUser(): void
    {
        $repository = $this->getRepositoryMock();
        $userService = $this->getPartlyMockedUserService(['loadUser']);
        $contentService = $this->createMock(APIContentService::class);
        /* @var \PHPUnit\Framework\MockObject\MockObject $userHandler */
        $userHandler = $this->getPersistenceMock()->userHandler();

        $user = $this->createMock(APIUser::class);
        $contentInfo = $this->createMock(APIContentInfo::class);
        $this->mockDeleteUserFlow($repository, $userService, $contentService, $user, $contentInfo, $userHandler);

        $contentService->expects(self::once())->method('deleteContent')->with($contentInfo);
        $userHandler->expects(self::once())->method('delete')->with(self::MOCKED_USER_ID);
        $repository->expects(self::once())->method('commit');

        $userService->deleteUser($user);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testDeleteUserWithRollback(): void
    {
        $repository = $this->getRepositoryMock();
        $userService = $this->getPartlyMockedUserService(['loadUser']);
        $contentService = $this->createMock(APIContentService::class);
        /* @var \Ibexa\Contracts\Core\Persistence\User\Handler&\PHPUnit\Framework\MockObject\MockObject $userHandler */
        $userHandler = $this->getPersistenceMock()->userHandler();

        $user = $this->createMock(APIUser::class);
        $contentInfo = $this->createMock(APIContentInfo::class);
        $this->mockDeleteUserFlow($repository, $userService, $contentService, $user, $contentInfo, $userHandler);

        $exception = new Exception();
        $contentService->expects(self::once())
            ->method('deleteContent')
            ->with($contentInfo)
            ->willThrowException($exception);

        $repository->expects(self::once())->method('rollback');

        $this->expectExceptionObject($exception);
        $userService->deleteUser($user);
    }

    /**
     * Returns the User service to test with $methods mocked.
     *
     * Injected Repository comes from {@see getRepositoryMock()} and persistence handler from {@see getPersistenceMock()}
     *
     * @param string[] $methods
     *
     * @return \Ibexa\Contracts\Core\Repository\UserService&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPartlyMockedUserService(?array $methods = null): APIUserService
    {
        return $this->getMockBuilder(UserService::class)
            ->onlyMethods($methods)
            ->setConstructorArgs(
                [
                    $this->getRepositoryMock(),
                    $this->getPermissionResolverMock(),
                    $this->getPersistenceMock()->userHandler(),
                    $this->getPersistenceMock()->locationHandler(),
                    $this->createMock(PasswordHashService::class),
                    $this->createMock(PasswordValidatorInterface::class),
                    $this->createMock(ConfigResolverInterface::class),
                ]
            )
            ->getMock();
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Repository&\PHPUnit\Framework\MockObject\MockObject $repository
     * @param \Ibexa\Contracts\Core\Repository\UserService&\PHPUnit\Framework\MockObject\MockObject $userService
     * @param \Ibexa\Contracts\Core\Repository\ContentService&\PHPUnit\Framework\MockObject\MockObject $contentService
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User&\PHPUnit\Framework\MockObject\MockObject $user
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo&\PHPUnit\Framework\MockObject\MockObject $contentInfo
     * @param \Ibexa\Contracts\Core\Persistence\User\Handler&\PHPUnit\Framework\MockObject\MockObject $userHandler
     */
    private function mockDeleteUserFlow(
        Repository $repository,
        APIUserService $userService,
        APIContentService $contentService,
        User $user,
        APIContentInfo $contentInfo,
        PersistenceUserHandler $userHandler
    ): void {
        $loadedUser = $this->createMock(APIUser::class);
        $versionInfo = $this->createMock(APIVersionInfo::class);

        $user->method('__get')->with('id')->willReturn(self::MOCKED_USER_ID);
        $versionInfo->method('getContentInfo')->willReturn($contentInfo);
        $loadedUser->method('getVersionInfo')->willReturn($versionInfo);
        $loadedUser->method('__get')->with('id')->willReturn(self::MOCKED_USER_ID);

        $userService->method('loadUser')->with(self::MOCKED_USER_ID)->willReturn($loadedUser);

        $userHandler
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::MOCKED_USER_ID)
            ->willReturn([new RoleAssignment(['id' => 1])]);

        $userHandler->method('removeRoleAssignment')->with(1);

        $repository->expects(self::once())->method('beginTransaction');
        $repository->expects(self::once())->method('getContentService')->willReturn($contentService);
    }
}

class_alias(UserTest::class, 'eZ\Publish\Core\Repository\Tests\Service\Mock\UserTest');

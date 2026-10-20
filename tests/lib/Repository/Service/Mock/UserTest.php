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
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(UserService::class)]
class UserTest extends BaseServiceMockTest
{
    private const MOCKED_USER_ID = 42;

    /**
     * @throws UnauthorizedException
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

        $contentService->expects($this->once())->method('deleteContent')->with($contentInfo);
        $userHandler->expects($this->once())->method('delete')->with(self::MOCKED_USER_ID);
        $repository->expects($this->once())->method('commit');

        $userService->deleteUser($user);
    }

    /**
     * @throws UnauthorizedException
     */
    public function testDeleteUserWithRollback(): void
    {
        $repository = $this->getRepositoryMock();
        $userService = $this->getPartlyMockedUserService(['loadUser']);
        $contentService = $this->createMock(APIContentService::class);
        /* @var PersistenceUserHandler&MockObject $userHandler */
        $userHandler = $this->getPersistenceMock()->userHandler();

        $user = $this->createMock(APIUser::class);
        $contentInfo = $this->createMock(APIContentInfo::class);
        $this->mockDeleteUserFlow($repository, $userService, $contentService, $user, $contentInfo, $userHandler);

        $exception = new Exception();
        $contentService->expects($this->once())
            ->method('deleteContent')
            ->with($contentInfo)
            ->willThrowException($exception);

        $repository->expects($this->once())->method('rollback');

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
     * @return APIUserService&MockObject
     */
    protected function getPartlyMockedUserService(?array $methods = null): APIUserService
    {
        return $this->getMockBuilder(UserService::class)
            ->onlyMethods(array_values($methods))
            ->setConstructorArgs(
                [
                    $this->getRepositoryMock(),
                    $this->getPermissionResolverMock(),
                    $this->getPersistenceMock()->userHandler(),
                    $this->getPersistenceMock()->locationHandler(),
                    self::createStub(PasswordHashService::class),
                    self::createStub(PasswordValidatorInterface::class),
                    self::createStub(ConfigResolverInterface::class),
                ]
            )
            ->getMock();
    }

    private function mockDeleteUserFlow(
        Repository & MockObject $repository,
        APIUserService & MockObject $userService,
        APIContentService & MockObject $contentService,
        User & MockObject $user,
        APIContentInfo & MockObject $contentInfo,
        PersistenceUserHandler & MockObject $userHandler
    ): void {
        $loadedUser = $this->createMock(APIUser::class);
        $versionInfo = $this->createMock(APIVersionInfo::class);

        $user->method('__get')->with('id')->willReturn(self::MOCKED_USER_ID);
        $versionInfo->method('getContentInfo')->willReturn($contentInfo);
        $loadedUser->method('getVersionInfo')->willReturn($versionInfo);
        $loadedUser->method('__get')->with('id')->willReturn(self::MOCKED_USER_ID);

        $userService->method('loadUser')->with(self::MOCKED_USER_ID)->willReturn($loadedUser);

        $userHandler
            ->expects($this->once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::MOCKED_USER_ID)
            ->willReturn([new RoleAssignment(['id' => 1])]);

        $userHandler->method('removeRoleAssignment')->with(1);

        $repository->expects($this->once())->method('beginTransaction');
        $repository->expects($this->once())->method('getContentService')->willReturn($contentService);
    }
}

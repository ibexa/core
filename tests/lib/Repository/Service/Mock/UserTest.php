<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Exception;
use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Ibexa\Contracts\Core\Persistence\User\Handler as PersistenceUserHandler;
use Ibexa\Contracts\Core\Persistence\User\RoleAssignment;
use Ibexa\Contracts\Core\Repository\ContentService as APIContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\PasswordHashService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\UserService as APIUserService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo as APIContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentList;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationList;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Repository\User\PasswordValidatorInterface;
use Ibexa\Core\Repository\UserService;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\Repository\UserService
 */
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

        $contentService->expects(self::once())->method('deleteContent')->with($contentInfo);
        $userHandler->expects(self::once())->method('delete')->with(self::MOCKED_USER_ID);
        $repository->expects(self::once())->method('commit');

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
        /* @var PersistenceUserHandler&\PHPUnit\Framework\MockObject\MockObject $userHandler */
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

    public function testFindUsersFailsWhenUsersOfThePageCannotBeLoaded(): void
    {
        $locationService = self::createStub(LocationService::class);
        $locationService->method('find')->willReturn(new LocationList([
            'totalCount' => 3,
            'locations' => [
                new Location(['contentInfo' => new APIContentInfo(['id' => 10])]),
                new Location(['contentInfo' => new APIContentInfo(['id' => 11])]),
            ],
        ]));

        // the users were deleted between finding their Locations and loading their content
        $contentService = self::createStub(APIContentService::class);
        $contentService->method('loadContentListByContentInfo')->willReturn([]);

        $repository = $this->getRepositoryMock();
        $repository->method('getLocationService')->willReturn($locationService);
        $repository->method('getContentService')->willReturn($contentService);

        $this->expectException(BadStateException::class);
        $this->expectExceptionMessage(
            'users 10, 11 matched the filter but were deleted or changed before they could be loaded'
        );

        $this->createUserService()->findUsers(new Filter());
    }

    public function testFindUserGroupsFailsWhenUserGroupsOfThePageCannotBeLoaded(): void
    {
        $contentService = self::createStub(APIContentService::class);
        $contentService->method('find')->willReturn(new ContentList(3, [
            $this->createUserGroupContentStub(20, 200),
            $this->createUserGroupContentStub(21, 210),
        ]));
        $this->getRepositoryMock()->method('getContentService')->willReturn($contentService);

        // the main Location of user group 21 was deleted between finding the user groups and loading their Locations
        $this->getPersistenceMockHandler('Content\\Location\\Handler')->method('loadList')->willReturnMap([
            [[200, 210], null, true, [200 => new SPILocation(['id' => 200, 'parentId' => 5])]],
            [[5], null, true, [5 => new SPILocation(['id' => 5, 'contentId' => 4])]],
        ]);

        $this->expectException(BadStateException::class);
        $this->expectExceptionMessage(
            'user groups 21 matched the filter but were deleted or changed before they could be loaded'
        );

        $this->createUserService()->findUserGroups(new Filter());
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
     * @param Repository&MockObject $repository
     * @param APIUserService&MockObject $userService
     * @param APIContentService&MockObject $contentService
     * @param APIUser&MockObject $user
     * @param APIContentInfo&MockObject $contentInfo
     * @param PersistenceUserHandler&MockObject $userHandler
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

    private function createUserService(): UserService
    {
        $configResolver = self::createStub(ConfigResolverInterface::class);
        $configResolver->method('getParameter')->willReturn(['user']);

        return new UserService(
            $this->getRepositoryMock(),
            $this->getPermissionResolverMock(),
            $this->getPersistenceMock()->userHandler(),
            $this->getPersistenceMock()->locationHandler(),
            self::createStub(PasswordHashService::class),
            self::createStub(PasswordValidatorInterface::class),
            $configResolver
        );
    }

    private function createUserGroupContentStub(
        int $contentId,
        int $mainLocationId
    ): APIContent {
        $content = self::createStub(APIContent::class);
        $content->method('getId')->willReturn($contentId);
        $content->method('getContentInfo')->willReturn(
            new APIContentInfo(['id' => $contentId, 'mainLocationId' => $mainLocationId])
        );

        return $content;
    }
}

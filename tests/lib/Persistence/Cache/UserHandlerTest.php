<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\User;
use Ibexa\Contracts\Core\Persistence\User\Handler as SPIUserHandler;
use Ibexa\Contracts\Core\Persistence\User\Policy;
use Ibexa\Contracts\Core\Persistence\User\Role;
use Ibexa\Contracts\Core\Persistence\User\RoleAssignment;
use Ibexa\Contracts\Core\Persistence\User\RoleCreateStruct;
use Ibexa\Contracts\Core\Persistence\User\RoleUpdateStruct;
use Ibexa\Core\Persistence\Cache\UserHandler;
use Ibexa\Core\Persistence\Legacy\Content\Location\Handler as SPILocationHandler;

/**
 * Test case for Persistence\Cache\UserHandler.
 */
class UserHandlerTest extends AbstractInMemoryCacheHandlerTestCase
{
    public function getHandlerMethodName(): string
    {
        return 'userHandler';
    }

    public function getHandlerClassName(): string
    {
        return SPIUserHandler::class;
    }

    public function providerForUnCachedMethods(): array
    {
        $user = new User(['id' => 14, 'login' => 'otto', 'email' => 'otto@ibexa.co']);
        $policy = new Policy(['id' => 13, 'roleId' => 9]);
        $userToken = new User\UserTokenUpdateStruct(['userId' => 14, 'hashKey' => '4irj8t43r']);
        $escapedLogin = str_replace('@', '_A', $user->login);
        $escapedEmail = str_replace('@', '_A', $user->email);

        // string $method, array $arguments, array? $tagGeneratingArguments, array? $keyGeneratingArguments, array? $tags, array? $key, returned $returnValue, bool $callInnerHandler
        return [
            [
                'create',
                [$user],
                [
                    ['content', [14], false],
                ],
                [
                    ['user', [14], true],
                    ['user_with_by_login_suffix', [$escapedLogin], true],
                    ['user_with_by_email_suffix', [$escapedEmail], true],
                    ['users_with_by_email_suffix', [$escapedEmail], true],
                ],
                ['c-14'],
                [
                    'ibx-u-14',
                    'ibx-u-' . $escapedLogin . '-bl',
                    'ibx-u-' . $escapedEmail . '-be',
                    'ibx-us-' . $escapedEmail . '-be',
                ],
                $user,
                false,
            ],
            [
                'update',
                [$user],
                [
                    ['content', [14], false],
                    ['user', [14], false],
                ],
                [
                    ['user_with_by_email_suffix', [$escapedEmail], true],
                    ['users_with_by_email_suffix', [$escapedEmail], true],
                ],
                ['c-14', 'u-14'],
                [
                    'ibx-u-' . $escapedEmail . '-be',
                    'ibx-us-' . $escapedEmail . '-be',
                ],
                $user,
                false,
            ],
            [
                'updateUserToken',
                [$userToken],
                [
                    ['user_with_account_key_suffix', [14], false],
                ],
                [
                    ['user_with_by_account_key_suffix', ['4irj8t43r'], true],
                ],
                ['u-14-ak'],
                ['ibx-u-4irj8t43r-bak'],
            ],
            ['expireUserToken', ['4irj8t43r'], null, [['user_with_by_account_key_suffix', ['4irj8t43r'], true]], null, ['ibx-u-4irj8t43r-bak']],
            [
                'delete',
                [14],
                [
                    ['content', [14], false],
                    ['user', [14], false],
                ],
                null,
                ['c-14', 'u-14'],
                null,
                null,
                false,
            ],
            ['countRoleAssignments', [9], null, [], null, [], 1],
            ['createRole', [new RoleCreateStruct()]],
            ['createRoleDraft', [new RoleCreateStruct()]],
            ['loadRole', [9, 1]],
            ['loadRoleByIdentifier', ['member', 1]],
            ['loadRoleDraftByRoleId', [9]],
            ['loadRoles', []],
            ['updateRole', [new RoleUpdateStruct(['id' => 9])], [['role', [9], false]], null, ['r-9']],
            [
                'deleteRole',
                [9],
                [
                    ['role', [9], false],
                    ['role_assignment_role_list', [9], false],
                ],
                null,
                ['r-9', 'rarl-9'],
            ],
            ['deleteRole', [9, 1]],
            ['addPolicyByRoleDraft', [9, $policy]],
            ['addPolicy', [9, $policy], [['role', [9], false]], null, ['r-9']],
            [
                'updatePolicy',
                [$policy],
                [
                    ['policy', [13], false],
                    ['role', [9], false],
                ],
                null,
                ['p-13', 'r-9'],
            ],
            [
                'deletePolicy',
                [13, 9],
                [
                    ['policy', [13], false],
                    ['role', [9], false],
                ],
                null,
                ['p-13', 'r-9'],
            ],
            [
                'unassignRole',
                [14, 9],
                [
                    ['role_assignment_group_list', [14], false],
                    ['role_assignment_role_list', [9], false],
                ],
                null,
                ['ragl-14', 'rarl-9'],
            ],
        ];
    }

    public function providerForCachedLoadMethodsHit(): array
    {
        $user = new User(['id' => 14]);
        $role = new Role(['id' => 9]);
        $roleAssignment = new RoleAssignment(['id' => 11, 'roleId' => 9, 'contentId' => 14]);
        $calls = [['locationHandler', Location\Handler::class, 'loadLocationsByContent', [new Location(['pathString' => '/1/2/43/'])]]];

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            ['load', [14], 'ibx-u-14', null, null, [['user', [], true]], ['ibx-u'], $user],
            [
                'loadByLogin',
                ['admin'],
                'ibx-u-admin-bl',
                null,
                null,
                [
                    ['user', [], true],
                    ['by_login_suffix', [], false],
                ],
                ['ibx-u', 'bl'],
                $user,
            ],
            [
                'loadByEmail',
                ['admin@link.invalid'],
                'ibx-u-admin_Alink.invalid-be',
                null,
                null,
                [
                    ['user', [], true],
                    ['by_email_suffix', [], false],
                ],
                ['ibx-u', 'be'],
                $user,
            ],
            [
                'loadUserByToken',
                ['hash'],
                'ibx-u-hash-bak',
                null,
                null,
                [
                    ['user', [], true],
                    ['by_account_key_suffix', [], false],
                ],
                ['ibx-u', '-bak'],
                $user,
            ],
            ['loadRole', [9], 'ibx-r-9', null, null, [['role', [], true]], ['ibx-r'], $role],
            [
                'loadRoleByIdentifier',
                ['member'],
                'ibx-r-member-bi',
                null,
                null,
                [
                    ['role', [], true],
                    ['by_identifier_suffix', [], false],
                ],
                ['ibx-r', '-bi'],
                $role,
            ],
            ['loadRoleAssignment', [11], 'ibx-ra-11', null, null, [['role_assignment', [], true]], ['ibx-ra'], $roleAssignment],
            ['loadRoleAssignmentsByRoleId', [$role->id], 'ibx-ra-9-bro', null, null, [['role_assignment_with_by_role_suffix', [9], true]], ['ibx-ra-9-bro'], [$roleAssignment]],
            [
                'loadRoleAssignmentsByRoleIdWithOffsetAndLimit',
                [9, 0, 10],
                'ibx-ra-9-bro-0-10',
                null,
                null,
                [['role_assignment_with_by_role_offset_limit_suffix', [9, 0, 10], true]],
                ['ibx-ra-9-bro-0-10'],
                [$roleAssignment],
            ],
            [
                'loadRoleAssignmentsByGroupId',
                [14],
                'ibx-ra-14-bg',
                null,
                null,
                [
                    ['role_assignment_with_by_group_suffix', [14], true],
                ],
                ['ibx-ra-14-bg'],
                [$roleAssignment],
                false,
                $calls,
            ],
            [
                'loadRoleAssignmentsByGroupId',
                [14, true],
                'ibx-ra-14-bgi',
                null,
                null,
                [['role_assignment_with_by_group_inherited_suffix', [14], true]],
                ['ibx-ra-14-bgi'],
                [$roleAssignment],
                false,
                $calls,
            ],
        ];
    }

    public function providerForCachedLoadMethodsMiss(): array
    {
        $user = new User(['id' => 14]);
        $role = new Role(['id' => 9]);
        $roleAssignment = new RoleAssignment(['id' => 11, 'roleId' => 9, 'contentId' => 14]);
        $calls = [['locationHandler', Location\Handler::class, 'loadLocationsByContent', [new Location(['pathString' => '/1/2/43/'])]]];

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            [
                'load',
                [14],
                'ibx-u-14',
                [
                    ['content', [14], false],
                    ['user', [14], false],
                ],
                ['c-14', 'u-14'],
                [
                    ['user', [], true],
                ],
                ['ibx-u'],
                $user,
            ],
            [
                'loadByLogin',
                ['admin'],
                'ibx-u-admin-bl',
                [
                    ['content', [14], false],
                    ['user', [14], false],
                ],
                ['c-14', 'u-14'],
                [
                    ['user', [], true],
                    ['by_login_suffix', [], false],
                ],
                ['ibx-u', 'bl'],
                $user,
            ],
            [
                'loadByEmail',
                ['admin@link.invalid'],
                'ibx-u-admin_Alink.invalid-be',
                [
                    ['content', [14], false],
                    ['user', [14], false],
                ],
                ['c-14', 'u-14'],
                [
                    ['user', [], true],
                    ['by_email_suffix', [], false],
                ],
                ['ibx-u', 'be'],
                $user,
            ],
            [
                'loadUserByToken',
                ['hash'],
                'ibx-u-hash-bak',
                [
                    ['content', [14], false],
                    ['user', [14], false],
                    ['user_with_account_key_suffix', [14], false],
                ],
                ['c-14', 'u-14', 'u-14-bak'],
                [
                    ['user', [], true],
                    ['by_account_key_suffix', [], false],
                ],
                ['ibx-u', '-bak'],
                $user,
            ],
            [
                'loadRole',
                [9],
                'ibx-r-9',
                [
                    ['role', [9], false],
                ],
                ['r-9'],
                [
                    ['role', [], true],
                ],
                ['ibx-r'],
                $role,
            ],
            [
                'loadRoleByIdentifier',
                ['member'],
                'ibx-r-member-bi',
                [
                    ['role', [9], false],
                ],
                ['r-9'],
                [
                    ['role', [], true],
                    ['by_identifier_suffix', [], false],
                ],
                ['ibx-r', '-bi'],
                $role,
            ],
            [
                'loadRoleAssignment',
                [11],
                'ibx-ra-11',
                [
                    ['role_assignment', [11], false],
                    ['role_assignment_group_list', [14], false],
                    ['role_assignment_role_list', [9], false],
                ],
                ['ra-11', 'ragl-14', 'rarl-9'],
                [
                    ['role_assignment', [], true],
                ],
                ['ibx-ra'],
                $roleAssignment,
            ],
            [
                'loadRoleAssignmentsByRoleId',
                [9],
                'ibx-ra-9-bro',
                [
                    ['role_assignment_role_list', [9], false],
                    ['role', [9], false],
                    ['role_assignment', [11], false],
                    ['role_assignment_group_list', [14], false],
                    ['role_assignment_role_list', [9], false],
                ],
                ['rarl-9', 'r-9', 'ra-11', 'ragl-14', 'rarl-9'],
                [
                    ['role_assignment_with_by_role_suffix', [9], true],
                ],
                ['ibx-ra-9-bro'],
                [$roleAssignment],
            ],
            [
                'loadRoleAssignmentsByRoleIdWithOffsetAndLimit',
                [9, 0, 10],
                'ibx-ra-9-bro-0-10',
                [
                    ['role_assignment_role_list', [9], false],
                    ['role', [9], false],
                    ['role_assignment', [11], false],
                    ['role_assignment_group_list', [14], false],
                    ['role_assignment_role_list', [9], false],
                ],
                ['rarl-9', 'r-9', 'ra-11', 'ragl-14', 'rarl-9'],
                [
                    ['role_assignment_with_by_role_offset_limit_suffix', [9, 0, 10], true],
                ],
                ['ibx-ra-9-bro-0-10'],
                [$roleAssignment],
            ],
            [
                'loadRoleAssignmentsByGroupId',
                [14],
                'ibx-ra-14-bg',
                [
                    ['role_assignment_group_list', [14], false],
                    ['location_path', ['2'], false],
                    ['location_path', ['43'], false],
                    ['role_assignment', [11], false],
                    ['role_assignment_group_list', [14], false],
                    ['role_assignment_role_list', [9], false],
                ],
                ['ragl-14', 'lp-2', 'lp-43', 'ra-11', 'ragl-14', 'rarl-9'],
                [
                    ['role_assignment_with_by_group_suffix', [14], true],
                ],
                ['ibx-ra-14-bg'],
                [$roleAssignment],
                false,
                $calls,
            ],
            [
                'loadRoleAssignmentsByGroupId',
                [14, true],
                'ibx-ra-14-bgi',
                [
                    ['role_assignment_group_list', [14], false],
                    ['location_path', ['2'], false],
                    ['location_path', ['43'], false],
                    ['role_assignment', [11], false],
                    ['role_assignment_group_list', [14], false],
                    ['role_assignment_role_list', [9], false],
                ],
                ['ragl-14', 'lp-2', 'lp-43', 'ra-11', 'ragl-14', 'rarl-9'],
                [
                    ['role_assignment_with_by_group_inherited_suffix', [14], true],
                ],
                ['ibx-ra-14-bgi'],
                [$roleAssignment],
                false,
                $calls,
            ],
        ];
    }

    public function testPublishRoleDraftFromExistingRole()
    {
        $this->loggerMock->expects(self::once())->method('logCall');
        $innerHandlerMock = $this->createMock(SPIUserHandler::class);

        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method('userHandler')
            ->willReturn($innerHandlerMock);

        $roleDraftId = 33;
        $originalRoleId = 30;

        $innerHandlerMock
            ->expects(self::once())
            ->method('loadRole')
            ->with($roleDraftId, Role::STATUS_DRAFT)
            ->willReturn(new Role(['originalId' => $originalRoleId]));

        $innerHandlerMock
            ->expects(self::once())
            ->method('publishRoleDraft')
            ->with($roleDraftId);

        $roleTag = 'r-' . $originalRoleId;

        $this->cacheIdentifierGeneratorMock
            ->expects(self::once())
            ->method('generateTag')
            ->with('role', [$originalRoleId], false)
            ->willReturn($roleTag);

        $this->cacheMock
            ->expects(self::once())
            ->method('invalidateTags')
            ->with([$roleTag]);

        $this->cacheMock
            ->expects(self::never())
            ->method('deleteItem');

        $handler = $this->persistenceCacheHandler->userHandler();
        $handler->publishRoleDraft($roleDraftId);
    }

    public function testPublishNewRoleDraft()
    {
        $this->loggerMock->expects(self::once())->method('logCall');
        $innerHandlerMock = $this->createMock(SPIUserHandler::class);
        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method('userHandler')
            ->willReturn($innerHandlerMock);
        $roleDraftId = 33;
        $innerHandlerMock
            ->expects(self::at(0))
            ->method('loadRole')
            ->with($roleDraftId, Role::STATUS_DRAFT)
            ->willReturn(new Role(['originalId' => -1]));
        $innerHandlerMock
            ->expects(self::at(1))
            ->method('publishRoleDraft')
            ->with($roleDraftId);
        $this->cacheMock
            ->expects(self::never())
            ->method(self::anything());
        $handler = $this->persistenceCacheHandler->userHandler();
        $handler->publishRoleDraft($roleDraftId);
    }

    public function testAssignRole()
    {
        $innerUserHandlerMock = $this->createMock(SPIUserHandler::class);
        $innerLocationHandlerMock = $this->createMock(SPILocationHandler::class);

        $contentId = 14;
        $roleId = 9;

        $this->loggerMock->expects(self::once())->method('logCall');

        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method('userHandler')
            ->willReturn($innerUserHandlerMock);

        $innerUserHandlerMock
            ->expects(self::once())
            ->method('assignRole')
            ->with($contentId, $roleId)
            ->willReturn(null);

        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method('locationHandler')
            ->willReturn($innerLocationHandlerMock);

        $innerLocationHandlerMock
            ->expects(self::once())
            ->method('loadLocationsByContent')
            ->with($contentId)
            ->willReturn([new Location(['id' => '43'])]);

        $tags = ['ragl-14', 'rarl-9', 'lp-43'];

        $this->cacheIdentifierGeneratorMock
            ->expects(self::exactly(3))
            ->method('generateTag')
            ->withConsecutive(
                ['role_assignment_group_list', [14], false],
                ['role_assignment_role_list', [9], false],
                ['location_path', [43], false]
            )
            ->willReturnOnConsecutiveCalls(...$tags);

        $this->cacheMock
            ->expects(self::once())
            ->method('invalidateTags')
            ->with($tags);

        $this->cacheMock
            ->expects(self::never())
            ->method('deleteItem');

        $handler = $this->persistenceCacheHandler->userHandler();
        $handler->assignRole($contentId, $roleId);
    }

    public function testRemoveRoleAssignment(): void
    {
        $handler = $this->persistenceCacheHandler->userHandler();
        $methodName = 'removeRoleAssignment';

        $innerHandler = $this->createMock(SPIUserHandler::class);
        $this->persistenceHandlerMock->method('userHandler')->willReturn($innerHandler);
        $roleAssignmentId = 1;
        $contentId = 2;
        $roleId = 3;
        $innerHandler
            ->method('loadRoleAssignment')
            ->willReturn(
                new RoleAssignment(['id' => $roleAssignmentId, 'contentId' => $contentId, 'roleId' => $roleId])
            );

        $this->loggerMock->method('logCall')->with(
            UserHandler::class . "::$methodName",
            [
                'assignment' => $roleAssignmentId,
                'contentId' => $contentId,
                'roleId' => $roleId,
            ]
        );
        $innerHandler->method($methodName)->with($roleAssignmentId);

        $tags = [
            "ra-$roleAssignmentId",
            "ragl-$contentId",
            "rarl-$roleId",
        ];
        $this->cacheIdentifierGeneratorMock
            ->expects(self::exactly(count($tags)))
            ->method('generateTag')
            ->withConsecutive(['role_assignment'], ['role_assignment_group_list'], ['role_assignment_role_list'])
            ->willReturnOnConsecutiveCalls(...$tags);

        $this->cacheMock->method('invalidateTags')->with($tags);

        $handler->removeRoleAssignment($roleAssignmentId);
    }
}

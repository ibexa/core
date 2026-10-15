<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\UserService;

use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * Builds the following tree under the main "Users" group (content ID 4):
 *
 * - group "groupA": users "alice", "bob", "dave"
 *   - group "groupA1": user "erin"
 *   - group "groupA2"
 * - group "groupB": users "bob", "carol"
 *
 * Users "bob" and "carol" have their main Location in "groupA" and "groupB" respectively.
 */
abstract class AbstractFindTestCase extends RepositoryTestCase
{
    protected const MAIN_USER_GROUP_ID = 4;
    protected const ADMINISTRATOR_USERS_GROUP_ID = 12;
    protected const NON_EXISTENT_ID = 999999;

    /** @var array<string, int> content IDs of the created users and user groups, by alias */
    protected array $ids = [];

    /** @var array<string, \Ibexa\Contracts\Core\Repository\Values\User\UserGroup> */
    private array $userGroups = [];

    protected function setUp(): void
    {
        parent::setUp();

        $userService = $this->getIbexaTestCore()->getUserService();
        $mainGroup = $userService->loadUserGroup(self::MAIN_USER_GROUP_ID);

        $groupA = $this->createUserGroupWithAlias('groupA', $mainGroup);
        $groupA1 = $this->createUserGroupWithAlias('groupA1', $groupA);
        $this->createUserGroupWithAlias('groupA2', $groupA);
        $groupB = $this->createUserGroupWithAlias('groupB', $mainGroup);

        $this->createUserWithAlias('alice', [$groupA]);
        $this->createUserWithAlias('bob', [$groupA, $groupB]);
        $this->createUserWithAlias('carol', [$groupB]);
        $this->createUserWithAlias('dave', [$groupA]);
        $this->createUserWithAlias('erin', [$groupA1]);
    }

    /**
     * @param list<string> $aliases
     *
     * @return list<int>
     */
    protected function getIds(array $aliases): array
    {
        return array_map(fn (string $alias): int => $this->ids[$alias], $aliases);
    }

    /**
     * Logs in a new user (assigned to the main "Users" group) allowed to read content in the subtree of "groupA" only.
     */
    protected function loginAsUserLimitedToGroupASubtree(): void
    {
        $roleService = $this->getIbexaTestCore()->getRoleService();
        $locationService = $this->getIbexaTestCore()->getLocationService();

        $roleCreateStruct = $roleService->newRoleCreateStruct('read_group_a_subtree');
        $roleCreateStruct->addPolicy($roleService->newPolicyCreateStruct('content', 'read'));
        $roleDraft = $roleService->createRole($roleCreateStruct);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        $groupALocation = $locationService->loadLocation(
            $this->userGroups['groupA']->getVersionInfo()->getContentInfo()->getMainLocationId() ?? 0
        );

        $restrictedUser = $this->createUser('restricted', 'Restricted', 'User');
        $roleService->assignRoleToUser(
            $role,
            $restrictedUser,
            new SubtreeLimitation(['limitationValues' => [$groupALocation->getPathString()]])
        );

        $this->getIbexaTestCore()->getPermissionResolver()->setCurrentUserReference($restrictedUser);
    }

    private function createUserGroupWithAlias(string $alias, UserGroup $parentGroup): UserGroup
    {
        $userService = $this->getIbexaTestCore()->getUserService();

        $createStruct = $userService->newUserGroupCreateStruct('eng-GB');
        $createStruct->setField('name', $alias);

        $userGroup = $userService->createUserGroup($createStruct, $parentGroup);
        $this->ids[$alias] = $userGroup->getId();
        $this->userGroups[$alias] = $userGroup;

        return $userGroup;
    }

    /**
     * @param non-empty-list<\Ibexa\Contracts\Core\Repository\Values\User\UserGroup> $userGroups
     */
    private function createUserWithAlias(string $alias, array $userGroups): User
    {
        $userService = $this->getIbexaTestCore()->getUserService();

        $user = $this->createUser($alias, ucfirst($alias), 'Doe', $userGroups[0]);
        foreach (array_slice($userGroups, 1) as $userGroup) {
            $userService->assignUserToUserGroup($user, $userGroup);
        }

        $this->ids[$alias] = $user->getId();

        return $user;
    }
}

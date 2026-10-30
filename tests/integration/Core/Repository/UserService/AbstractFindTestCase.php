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
 * Users "bob" and "carol" have their main Location in "groupA" and "groupB" respectively. "bob" is assigned to "groupB"
 * after "carol" is created, so in "groupB" the Location order differs from the content ID order.
 */
abstract class AbstractFindTestCase extends RepositoryTestCase
{
    protected const MAIN_USER_GROUP_ID = 4;
    protected const ADMINISTRATOR_USERS_GROUP_ID = 12;
    protected const NON_EXISTENT_ID = 999999;

    /** @var array<string, int> content IDs of the created users and user groups, by alias */
    protected array $ids = [];

    /** @var array<string, int> main Location IDs of the created users and user groups, by alias */
    protected array $mainLocationIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $userService = $this->getIbexaTestCore()->getUserService();
        $mainGroup = $userService->loadUserGroup(self::MAIN_USER_GROUP_ID);

        $groupA = $this->createUserGroupWithAlias('groupA', $mainGroup);
        $groupA1 = $this->createUserGroupWithAlias('groupA1', $groupA);
        $this->createUserGroupWithAlias('groupA2', $groupA);
        $groupB = $this->createUserGroupWithAlias('groupB', $mainGroup);

        $this->createUserWithAlias('alice', $groupA);
        $bob = $this->createUserWithAlias('bob', $groupA);
        $this->createUserWithAlias('carol', $groupB);
        $userService->assignUserToUserGroup($bob, $groupB);
        $this->createUserWithAlias('dave', $groupA);
        $this->createUserWithAlias('erin', $groupA1);
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
     * Logs in a new user (assigned to the main "Users" group) allowed to read content in the subtree of the given
     * user group only.
     */
    protected function loginAsUserLimitedToSubtreeOf(string $userGroupAlias): void
    {
        $roleService = $this->getIbexaTestCore()->getRoleService();
        $locationService = $this->getIbexaTestCore()->getLocationService();

        $roleCreateStruct = $roleService->newRoleCreateStruct('read_' . $userGroupAlias . '_subtree');
        $roleCreateStruct->addPolicy($roleService->newPolicyCreateStruct('content', 'read'));
        $roleDraft = $roleService->createRole($roleCreateStruct);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        $userGroupLocation = $locationService->loadLocation($this->mainLocationIds[$userGroupAlias]);

        $restrictedUser = $this->createUser('restricted', 'Restricted', 'User');
        $roleService->assignRoleToUser(
            $role,
            $restrictedUser,
            new SubtreeLimitation(['limitationValues' => [$userGroupLocation->getPathString()]])
        );

        $this->getIbexaTestCore()->getPermissionResolver()->setCurrentUserReference($restrictedUser);
    }

    private function createUserGroupWithAlias(
        string $alias,
        UserGroup $parentGroup
    ): UserGroup {
        $userService = $this->getIbexaTestCore()->getUserService();

        $createStruct = $userService->newUserGroupCreateStruct('eng-GB');
        $createStruct->setField('name', $alias);

        $userGroup = $userService->createUserGroup($createStruct, $parentGroup);
        $this->ids[$alias] = $userGroup->getId();
        $this->mainLocationIds[$alias] = $userGroup->getVersionInfo()->getContentInfo()->getMainLocationId() ?? 0;

        return $userGroup;
    }

    private function createUserWithAlias(
        string $alias,
        UserGroup $userGroup
    ): User {
        $user = $this->createUser($alias, ucfirst($alias), 'Doe', $userGroup);

        $this->ids[$alias] = $user->getId();
        $this->mainLocationIds[$alias] = $user->getVersionInfo()->getContentInfo()->getMainLocationId() ?? 0;

        return $user;
    }
}

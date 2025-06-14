<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Values\User\Limitation;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\UserGroupLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;

/**
 * @covers \Ibexa\Contracts\Core\Repository\Values\User\Limitation\UserGroupLimitation
 *
 * @group integration
 * @group limitation
 */
class UserGroupLimitationTest extends BaseLimitationTestCase
{
    public function testUserGroupLimitationAllow()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();
        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();
        $currentUser = $userService->loadUser($permissionResolver->getCurrentUserReference()->getUserId());

        $userGroup = $this->prepareUserGroup();

        // Assign system user and example user to same group
        $userService->assignUserToUserGroup($user, $userGroup);
        $userService->assignUserToUserGroup($currentUser, $userGroup);

        $draft = $this->prepareLimitationAndContent($user, $userGroup);
        /* END: Use Case */

        self::assertEquals(
            'An awesome wiki page',
            $draft->getFieldValue('title')->text
        );
    }

    public function testUserGroupLimitationForbid()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        $userGroup = $this->prepareUserGroup();

        // Assign example user to new group
        $userService->assignUserToUserGroup($user, $userGroup);

        $this->prepareLimitationAndContent($user, $userGroup);
        /* END: Use Case */
    }

    /**
     * Prepares the UserGroup fixture.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup
     */
    protected function prepareUserGroup()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $parentUserGroupId = $this->generateId('location', 4);
        /* BEGIN: Inline */
        $userGroupCreate = $userService->newUserGroupCreateStruct('eng-GB');
        $userGroupCreate->setField('name', 'Shared wiki');

        $userGroup = $userService->createUserGroup(
            $userGroupCreate,
            $userService->loadUserGroup(
                $parentUserGroupId
            )
        );
        /* END: Inline */

        return $userGroup;
    }

    /**
     * Prepares the limitation fixture.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroup
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     *
     * @throws \ErrorException
     */
    protected function prepareLimitationAndContent(User $user, UserGroup $userGroup)
    {
        $repository = $this->getRepository();

        $contentService = $repository->getContentService();
        $permissionResolver = $repository->getPermissionResolver();

        /* BEGIN: Inline */
        $roleService = $repository->getRoleService();

        $role = $roleService->loadRoleByIdentifier('Editor');
        $roleDraft = $roleService->createRoleDraft($role);
        // Search for the new policy instance
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft $policy */
        $editPolicy = null;
        foreach ($roleDraft->getPolicies() as $policy) {
            if ('content' != $policy->module || 'edit' != $policy->function) {
                continue;
            }
            $editPolicy = $policy;
            break;
        }

        if (null === $editPolicy) {
            throw new \ErrorException(
                'Cannot find mandatory policy test fixture content::edit.'
            );
        }

        // Give read access for the user section
        $policyUpdate = $roleService->newPolicyUpdateStruct();
        $policyUpdate->addLimitation(
            new UserGroupLimitation(
                [
                    'limitationValues' => [true],
                ]
            )
        );
        $roleService->updatePolicyByRoleDraft(
            $roleDraft,
            $editPolicy,
            $policyUpdate
        );
        $roleService->publishRoleDraft($roleDraft);

        $roleService->assignRoleToUserGroup($role, $userGroup);

        $content = $this->createWikiPage();
        $contentId = $content->id;

        $permissionResolver->setCurrentUserReference($user);

        $draft = $contentService->createContentDraft(
            $contentService->loadContentInfo($contentId)
        );
        /* END: Inline */

        return $draft;
    }
}

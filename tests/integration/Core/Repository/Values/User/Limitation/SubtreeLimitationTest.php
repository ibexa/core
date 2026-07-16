<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Values\User\Limitation;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;

/**
 * @covers \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation
 *
 * @group integration
 * @group limitation
 */
class SubtreeLimitationTest extends BaseLimitationTestCase
{
    /**
     * Tests a combination of SubtreeLimitation, SectionLimitation and
     * the ContentTypeLimitation.
     *
     * @see ContentTypeLimitation
     * @see SectionLimitation
     * @see SubtreeLimitation
     */
    public function testSubtreeLimitationAllow()
    {
        $repository = $this->getRepository();

        $userGroupId = $this->generateId('content', 13);
        /* BEGIN: Use Case */
        $subtree = '/1/5/';

        $this->prepareLimitation($subtree);

        $userService = $repository->getUserService();
        $contentService = $repository->getContentService();

        $contentUpdate = $contentService->newContentUpdateStruct();
        $contentUpdate->setField('name', 'Ibexa Editors');

        $userGroup = $userService->loadUserGroup($userGroupId);

        $groupUpdate = $userService->newUserGroupUpdateStruct();
        $groupUpdate->contentUpdateStruct = $contentUpdate;

        $userService->updateUserGroup($userGroup, $groupUpdate);
        /* END: Use Case */

        self::assertEquals(
            'Ibexa Editors',
            $userService->loadUserGroup($userGroupId)
                ->getFieldValue('name')
                ->text
        );
    }

    /**
     * Tests a combination of SubtreeLimitation, SectionLimitation and
     * the ContentTypeLimitation.
     *
     * @see ContentTypeLimitation
     * @see SectionLimitation
     * @see SubtreeLimitation
     */
    public function testSubtreeLimitationForbid()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $userGroupId = $this->generateId('content', 13);

        /* BEGIN: Use Case */
        $subtree = '/1/5/12/';

        $this->prepareLimitation($subtree);

        $userService = $repository->getUserService();

        // This call will fail with an UnauthorizedException
        $userService->loadUserGroup($userGroupId);
        /* END: Use Case */
    }

    /**
     * Prepares the Subtree limitation for the test user.
     *
     * @param string $subtree
     *
     * @throws \ErrorException
     */
    protected function prepareLimitation($subtree)
    {
        $repository = $this->getRepository();

        $userTypeId = $this->generateId('contentType', 4);
        $groupTypeId = $this->generateId('contentType', 3);

        $standardSectionId = $this->generateId('section', 1);
        $userSectionId = $this->generateId('section', 2);

        /* BEGIN: Inline */
        $user = $this->createUserVersion1();

        $roleService = $repository->getRoleService();
        $permissionResolver = $repository->getPermissionResolver();

        $role = $roleService->loadRoleByIdentifier('Editor');
        $roleDraft = $roleService->createRoleDraft($role);
        // Search for the new policy instance
        /** @var PolicyDraft $policy */
        $editPolicy = null;
        foreach ($roleDraft->getPolicies() as $policy) {
            if ('content' != $policy->module || 'read' != $policy->function) {
                continue;
            }
            $editPolicy = $policy;
            break;
        }

        if (null === $editPolicy) {
            throw new \ErrorException('No content:read policy found.');
        }

        // Give read access for the user section
        $policyUpdate = $roleService->newPolicyUpdateStruct();
        $policyUpdate->addLimitation(
            new SectionLimitation(
                [
                    'limitationValues' => [
                        $standardSectionId,
                        $userSectionId,
                    ],
                ]
            )
        );
        $roleService->updatePolicyByRoleDraft(
            $roleDraft,
            $editPolicy,
            $policyUpdate
        );

        // Allow subtree access and user+user-group edit
        $policyCreate = $roleService->newPolicyCreateStruct('content', 'edit');
        $policyCreate->addLimitation(
            new ContentTypeLimitation(
                ['limitationValues' => [$userTypeId, $groupTypeId]]
            )
        );
        $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $policyCreate
        );
        $roleService->publishRoleDraft($roleDraft);

        $roleService->assignRoleToUser(
            $role,
            $user,
            new SubtreeLimitation(
                ['limitationValues' => [$subtree]]
            )
        );

        $permissionResolver->setCurrentUserReference($user);
        /* END: Inline */
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Issue EZP-22840.
 */
class EZP22840RoleLimitations extends BaseTestCase
{
    /**
     * Test Subtree Role Assignment Limitation against state/assign.
     */
    public function testSubtreeRoleAssignLimitation()
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();
        $notLockedState = $this->generateId('objectstate', 2);
        $contentId = $this->generateId('content', 57);
        $objectStateService = $repository->getObjectStateService();
        $permissionResolver = $repository->getPermissionResolver();

        // Get user assigned to editor role
        $user = $this->createUserVersion1();

        // Create and assign limited state:assign policy
        $policyCreate = $roleService->newPolicyCreateStruct('state', 'assign');
        $policyCreate->addLimitation(
            new NewObjectStateLimitation(
                [
                    'limitationValues' => [
                        $notLockedState,
                    ],
                ]
            )
        );
        $roleDraft = $roleService->createRoleDraft(
            $roleService->loadRoleByIdentifier('Editor')
        );

        $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $policyCreate
        );

        $roleService->publishRoleDraft($roleDraft);

        // set current user and get objects needed for the test
        $permissionResolver->setCurrentUserReference($user);
        $objectState = $objectStateService->loadObjectState($notLockedState);
        $contentInfo = $repository->getContentService()->loadContentInfo($contentId);

        // try to assign object state to root object
        $objectStateService->setContentState($contentInfo, $objectState->getObjectStateGroup(), $objectState);
    }

    /**
     * Test Section Role Assignment Limitation against user/login.
     */
    public function testSectionRoleAssignLimitation()
    {
        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        // Get user assigned to editor role with section limitation
        $user = $this->createCustomUserVersion1(
            'Section Editor',
            'Editor',
            new SectionLimitation(['limitationValues' => ['2']])
        );

        // set as current user
        $permissionResolver->setCurrentUserReference($user);

        // try to login
        self::assertTrue(
            $permissionResolver->canUser('user', 'login', new SiteAccess('test')),
            'Could not verify that user can login with section limitation'
        );
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Values\User\Limitation;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewObjectStateLimitation;

/**
 * @covers \Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewObjectStateLimitation
 *
 * @group integration
 * @group limitation
 */
class NewObjectStateLimitationTest extends BaseLimitationTestCase
{
    public function testNewObjectStateLimitationAllow()
    {
        $repository = $this->getRepository();
        $notLockedState = $this->generateId('objectstate', 2);

        $objectStateService = $repository->getObjectStateService();
        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();
        $draft = $this->createWikiPageDraft();

        $roleService = $repository->getRoleService();
        $permissionResolver = $repository->getPermissionResolver();

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

        $role = $this->addPolicyToRole('Editor', $policyCreate);

        $roleService->assignRoleToUser($role, $user);

        $permissionResolver->setCurrentUserReference($user);

        $objectState = $objectStateService->loadObjectState($notLockedState);

        $objectStateService->setContentState($draft->contentInfo, $objectState->getObjectStateGroup(), $objectState);
        /* END: Use Case */
    }

    /**
     * Tests a NewObjectStateLimitation.
     *
     * @covers \Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewObjectStateLimitation
     *
     * @throws \ErrorException if a mandatory test fixture not exists.
     */
    public function testNewObjectStateLimitationForbid()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $lockedState = $this->generateId('objectstate', 1);
        $notLockedState = $this->generateId('objectstate', 2);

        $objectStateService = $repository->getObjectStateService();
        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();
        $draft = $this->createWikiPageDraft();

        $roleService = $repository->getRoleService();
        $permissionResolver = $repository->getPermissionResolver();

        // Create and assign limited state:assign policy
        $policyCreate = $roleService->newPolicyCreateStruct('state', 'assign');
        $policyCreate->addLimitation(
            new NewObjectStateLimitation(
                [
                    'limitationValues' => [
                        $lockedState,
                    ],
                ]
            )
        );

        $role = $roleService->loadRoleByIdentifier('Editor');
        $roleDraft = $roleService->createRoleDraft($role);
        $roleService->addPolicyByRoleDraft($roleDraft, $policyCreate);
        $roleService->publishRoleDraft($roleDraft);

        $roleService->assignRoleToUser($role, $user);

        $permissionResolver->setCurrentUserReference($user);

        $objectState = $objectStateService->loadObjectState($notLockedState);

        $objectStateService->setContentState($draft->contentInfo, $objectState->getObjectStateGroup(), $objectState);
        /* END: Use Case */
    }
}

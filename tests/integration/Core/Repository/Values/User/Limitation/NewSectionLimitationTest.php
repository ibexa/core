<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Values\User\Limitation;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewSectionLimitation;

#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Contracts\Core\Repository\Values\User\Limitation\NewSectionLimitation::class)]
#[\PHPUnit\Framework\Attributes\Group('integration')]
#[\PHPUnit\Framework\Attributes\Group('limitation')]
class NewSectionLimitationTest extends BaseLimitationTestCase
{
    public function testNewSectionLimitationAllow()
    {
        $repository = $this->getRepository();

        $sectionId = $this->generateId('section', 6);
        $contentId = $this->generateId('content', 58);
        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        $roleService = $repository->getRoleService();
        $permissionResolver = $repository->getPermissionResolver();

        $policyCreate = $roleService->newPolicyCreateStruct('section', 'assign');
        $policyCreate->addLimitation(
            new NewSectionLimitation(
                ['limitationValues' => [$sectionId]]
            )
        );

        $role = $roleService->loadRoleByIdentifier('Editor');
        $roleDraft = $roleService->createRoleDraft($role);
        $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $policyCreate
        );
        $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $roleService->newPolicyCreateStruct('section', 'view')
        );
        $roleService->publishRoleDraft($roleDraft);

        $roleService->assignRoleToUser($role, $user);

        $permissionResolver->setCurrentUserReference($user);

        $contentService = $repository->getContentService();
        $contentInfo = $contentService->loadContentInfo($contentId);

        $sectionService = $repository->getSectionService();
        $sectionService->assignSection(
            $contentInfo,
            $sectionService->loadSection($sectionId)
        );
        /* END: Use Case */

        self::assertSame(
            $sectionId,
            $contentService->loadContentInfo($contentId)->getSectionId()
        );
    }

    public function testNewSectionLimitationForbid()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $contentId = $this->generateId('content', 58);
        $sectionId = $this->generateId('section', 6);
        $otherSectionId = $this->generateId('section', 1);
        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        $roleService = $repository->getRoleService();

        $policyCreate = $roleService->newPolicyCreateStruct('section', 'assign');
        $policyCreate->addLimitation(
            new NewSectionLimitation(
                ['limitationValues' => [$sectionId]]
            )
        );

        $role = $roleService->loadRoleByIdentifier('Editor');
        $roleDraft = $roleService->createRoleDraft($role);
        $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $policyCreate
        );
        $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $roleService->newPolicyCreateStruct('section', 'view')
        );
        $roleService->publishRoleDraft($roleDraft);

        $roleService->assignRoleToUser($role, $user);

        $permissionResolver->setCurrentUserReference($user);

        $contentService = $repository->getContentService();
        $contentInfo = $contentService->loadContentInfo($contentId);

        $sectionService = $repository->getSectionService();
        $sectionService->assignSection(
            $contentInfo,
            $sectionService->loadSection($otherSectionId)
        );
        /* END: Use Case */
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Values\User\Limitation;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\RoleService;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ChangeOwnerLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use RuntimeException;

/**
 * @covers \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ChangeOwnerLimitation
 *
 * @group integration
 * @group limitation
 */
final class ChangeOwnerLimitationTest extends BaseLimitationTestCase
{
    private PermissionResolver $permissionResolver;

    private RoleService $roleService;

    private ContentService $contentService;

    protected function setUp(): void
    {
        $repository = $this->getRepository();

        $this->permissionResolver = $repository->getPermissionResolver();
        $this->roleService = $repository->getRoleService();
        $this->contentService = $repository->getContentService();
    }

    /**
     * @return array<array{
     *     0: \Ibexa\Contracts\Core\Repository\Values\User\User,
     *     1: int,
     *     2: int[]
     * }>
     */
    public function getDataForGrantedAccess(): array
    {
        $otherUserId1 = 123;
        $otherUserId2 = 456;

        return [
            [null, []],
            [null, [-1]],
            [null, [$otherUserId1, $otherUserId2, -1]],
            [$otherUserId1, [$otherUserId1, $otherUserId2, -1]],
            [$otherUserId1, []],
        ];
    }

    /**
     * @return array<array{
     *     0: \Ibexa\Contracts\Core\Repository\Values\User\User,
     *     1: int,
     *     2: int[]
     * }>
     */
    public function getDataForDeniedAccess(): array
    {
        $otherUserId1 = 123;
        $otherUserId2 = 456;
        $otherUserId3 = 789;

        return [
            [$otherUserId1, [-1]],
            [$otherUserId1, [$otherUserId2, $otherUserId3]],
        ];
    }

    /**
     * @dataProvider getDataForGrantedAccess
     *
     * @param int[] $limitationValues
     */
    public function testChangeOwnerLimitationAllowed(?int $ownerId, array $limitationValues): void
    {
        $currentUser = $this->createUserVersion1('current_user', null, null, 42);
        $ownerId = $ownerId ?? $currentUser->id;

        $this->createTestCaseDraft($currentUser, $ownerId, $limitationValues);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @dataProvider getDataForDeniedAccess
     *
     * @param int[] $limitationValues
     */
    public function testChangeOwnerLimitationDenied(?int $ownerId, array $limitationValues): void
    {
        $currentUser = $this->createUserVersion1('current_user', null, null, 42);
        $ownerId = $ownerId ?? $currentUser->id;

        $this->expectException(UnauthorizedException::class);
        $this->createTestCaseDraft($currentUser, $ownerId, $limitationValues);
    }

    /**
     * @param int[] $limitationValues
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    private function createTestCaseDraft(
        User $currentUser,
        int $ownerId,
        array $limitationValues
    ): void {
        $role = $this->updateRoleWithChangeOwnerLimitation(
            $this->roleService->loadRoleByIdentifier('Anonymous'),
            $limitationValues
        );
        $this->roleService->assignRoleToUser($role, $currentUser);
        $this->permissionResolver->setCurrentUserReference($currentUser);

        $contentCreateStruct = $this->createWikiPageContentCreateStruct($ownerId);
        $locationCreateStruct = $this->createWikiPageLocationCreateStruct($this->generateId('location', 2));

        $this->contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
    }

    /**
     * @throws \RuntimeException
     */
    private function getContentCreatePolicyDraft(RoleDraft $role): PolicyDraft
    {
        foreach ($role->getPolicies() as $policy) {
            if ($policy->module === 'content' && $policy->function === 'create') {
                return $policy;
            }
        }

        throw new RuntimeException('No content:create policy found.');
    }

    private function hasContentCreatePolicy(RoleDraft $role): bool
    {
        foreach ($role->getPolicies() as $policy) {
            if ($policy->module === 'content' && $policy->function === 'create') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int[] $limitationValues
     */
    private function updateRoleWithChangeOwnerLimitation(
        Role $role,
        array $limitationValues
    ): Role {
        $roleDraft = $this->roleService->createRoleDraft($role);

        if (!$this->hasContentCreatePolicy($roleDraft)) {
            $createPolicyStruct = $this->roleService->newPolicyCreateStruct('content', 'create');
            $roleDraft = $this->roleService->addPolicyByRoleDraft($roleDraft, $createPolicyStruct);
        }

        $contentCreatePolicyDraft = $this->getContentCreatePolicyDraft($roleDraft);

        $policyUpdate = $this->roleService->newPolicyUpdateStruct();
        $policyUpdate->addLimitation(
            new ChangeOwnerLimitation($limitationValues)
        );
        $this->roleService->updatePolicyByRoleDraft(
            $roleDraft,
            $contentCreatePolicyDraft,
            $policyUpdate
        );
        $this->roleService->publishRoleDraft($roleDraft);

        return $this->roleService->loadRoleByIdentifier($role->identifier);
    }
}

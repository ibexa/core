<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Integration\Core\Repository\Values\User\Limitation;

use ErrorException;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\RoleService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ChangeOwnerLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;
use Ibexa\Contracts\Core\Repository\Values\User\User;

/**
 * @covers \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ChangeOwnerLimitation
 * @group integration
 * @group limitation
 */
final class ChangeOwnerLimitationTest extends BaseLimitationTest
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
    public function allowedProvider(): array
    {
        $otherUser1 = $this->createUserVersion1('other_user_1');
        $otherUser2 = $this->createUserVersion1('other_user_2');

        return [
            [null, []],
            [null, [-1]],
            [null, [$otherUser1->id, $otherUser2->id, -1]],
            [$otherUser1->id, [$otherUser1->id, $otherUser2->id, -1]],
            [$otherUser1->id, []],
        ];
    }

    /**
     * @return array<array{
     *     0: \Ibexa\Contracts\Core\Repository\Values\User\User,
     *     1: int,
     *     2: int[]
     * }>
     */
    public function deniedProvider(): array
    {
        $otherUser1 = $this->createUserVersion1('other_user_1');
        $otherUser2 = $this->createUserVersion1('other_user_2');
        $otherUser3 = $this->createUserVersion1('other_user_3');

        return [
            [$otherUser1->id, [-1]],
            [$otherUser1->id, [$otherUser2->id, $otherUser3->id]],
        ];
    }

    /**
     * @dataProvider allowedProvider
     *
     * @param int[] $limitationValues
     */
    public function testChangeOwnerLimitationAllowed(?int $ownerId, array $limitationValues): void
    {
        /* BEGIN: Use Case */
        $currentUser = $this->createUserVersion1('current_user', null, null, 42);
        $ownerId = $ownerId ?? $currentUser->id;

        $draft = $this->createTestCaseDraft($currentUser, $ownerId, $limitationValues);
        /* END: Use Case */

        self::assertInstanceOf(Content::class, $draft);
    }

    /**
     * @dataProvider deniedProvider
     *
     * @param int[] $limitationValues
     */
    public function testChangeOwnerLimitationDenied(?int $ownerId, array $limitationValues): void
    {
        self::expectException(UnauthorizedException::class);

        /* BEGIN: Use Case */
        $currentUser = $this->createUserVersion1('current_user', null, null, 42);
        $ownerId = $ownerId ?? $currentUser->id;

        $this->createTestCaseDraft($currentUser, $ownerId, $limitationValues);
        /* END: Use Case */
    }

    /**
     * @param int[] $limitationValues
     */
    private function createTestCaseDraft(
        User $currentUser,
        int $ownerId,
        array $limitationValues
    ): Content {
        $role = $this->updateRoleWithChangeOwnerLimitation(
            $this->roleService->loadRoleByIdentifier('Anonymous'),
            $limitationValues
        );
        $this->roleService->assignRoleToUser($role, $currentUser);
        $this->permissionResolver->setCurrentUserReference($currentUser);

        $contentCreateStruct = $this->createWikiPageContentCreateStruct($ownerId);
        $locationCreateStruct = $this->createWikiPageLocationCreateStruct($this->generateId('location', 2));

        return $this->contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
    }

    /**
     * @throws \ErrorException
     */
    private function getContentCreatePolicyDraft(RoleDraft $role): PolicyDraft
    {
        foreach ($role->getPolicies() as $policy) {
            if ($policy->module === 'content' && $policy->function === 'create') {
                return $policy;
            }
        }

        throw new ErrorException('No content:create policy found.');
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

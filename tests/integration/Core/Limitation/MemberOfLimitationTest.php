<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Limitation;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\MemberOfLimitation;
use Ibexa\Core\Limitation\MemberOfLimitationType;
use Ibexa\Tests\Integration\Core\Repository\Limitation\PermissionResolver\BaseLimitationIntegrationTestCase;

final class MemberOfLimitationTest extends BaseLimitationIntegrationTestCase
{
    private const ADMIN_GROUP_ID = 14;
    private const USERS_GROUP_ID = 4;

    public function userPermissionLimitationProvider(): array
    {
        $allowInAdministratorsLimitation = new MemberOfLimitation();
        $allowInAdministratorsLimitation->limitationValues[] = self::ADMIN_GROUP_ID;

        $allowInUsersLimitation = new MemberOfLimitation();
        $allowInUsersLimitation->limitationValues[] = self::USERS_GROUP_ID;

        $allowInSelfGroupLimitation = new MemberOfLimitation();
        $allowInSelfGroupLimitation->limitationValues[] = MemberOfLimitationType::SELF_USER_GROUP;

        return [
            [[$allowInAdministratorsLimitation], false],
            [[$allowInUsersLimitation], true],
            [[$allowInSelfGroupLimitation], true],
        ];
    }

    /**
     * @dataProvider userPermissionLimitationProvider
     */
    public function testCanUserAssignRoleToUser(array $limitations, bool $expectedResult): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();
        $userService = $repository->getUserService();

        $adminRoleThatWillBeSet = $roleService->loadRoleByIdentifier('Administrator');
        $this->loginAsEditorUserWithLimitations('role', 'assign', $limitations);

        $this->assertCanUser(
            $expectedResult,
            'role',
            'assign',
            $limitations,
            $userService->loadUser($this->permissionResolver->getCurrentUserReference()->getUserId()),
            [$adminRoleThatWillBeSet]
        );

        $this->assertCanUser(
            $expectedResult,
            'role',
            'assign',
            $limitations,
            $repository->sudo(
                static function (Repository $repository) {
                    return $repository->getUserService()->loadUserGroup(self::USERS_GROUP_ID);
                },
                $repository
            ),
            [$adminRoleThatWillBeSet]
        );
    }
}

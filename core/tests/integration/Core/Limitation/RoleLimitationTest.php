<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Limitation;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\UserRoleLimitation;
use Ibexa\Tests\Integration\Core\Repository\Limitation\PermissionResolver\BaseLimitationIntegrationTestCase;

final class RoleLimitationTest extends BaseLimitationIntegrationTestCase
{
    private const USERS_GROUP_ID = 4;

    public function userPermissionLimitationProvider(): array
    {
        $allowEditorLimitation = new UserRoleLimitation();
        $roleService = $this->getRepository()->getRoleService();
        $allowEditorLimitation->limitationValues[] = $roleService->loadRoleByIdentifier('Editor')->id;

        $allowAdministratorLimitation = new UserRoleLimitation();
        $allowAdministratorLimitation->limitationValues[] = $roleService->loadRoleByIdentifier('Administrator')->id;

        return [
            [[$allowEditorLimitation], false],
            [[$allowAdministratorLimitation], true],
        ];
    }

    /**
     * @dataProvider userPermissionLimitationProvider
     */
    public function testCanUserAssignRole(array $limitations, bool $expectedResult): void
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

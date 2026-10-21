<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\QueryType\BuiltIn;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ParentLocationId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Subtree;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Visibility;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Core\QueryType\BuiltIn\ChildrenQueryType;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Core\QueryType\BuiltIn\AbstractQueryType
 */
final class ChildrenQueryTypeTest extends RepositoryTestCase
{
    private const TREE_ROOT_PATH_STRING = '/1/2/';

    public function testGetQueryForUserWithoutReadAccessToTreeRoot(): void
    {
        $locationService = $this->getIbexaTestCore()->getLocationService();
        $permissionResolver = $this->getIbexaTestCore()->getPermissionResolver();

        $folderLocation = $this->createFolder(['eng-GB' => 'Blog'])->getVersionInfo()->getContentInfo()->getMainLocation();
        self::assertNotNull($folderLocation);
        $treeRootLocation = $locationService->loadLocation(self::CONTENT_TREE_ROOT_ID);

        $permissionResolver->setCurrentUserReference(
            $this->createUserWithSubtreeReadAccess($folderLocation->pathString)
        );

        self::assertFalse(
            $permissionResolver->canUser('content', 'read', $treeRootLocation->getContentInfo(), [$treeRootLocation])
        );

        $query = $this->getIbexaTestCore()
            ->getServiceByClassName(ChildrenQueryType::class)
            ->getQuery([
                'location' => $locationService->loadLocation($folderLocation->id),
            ]);

        self::assertEquals(
            new LogicalAnd([
                new ParentLocationId($folderLocation->id),
                new Visibility(Visibility::VISIBLE),
                new Subtree(self::TREE_ROOT_PATH_STRING),
            ]),
            $query->filter
        );
    }

    private function createUserWithSubtreeReadAccess(string $pathString): User
    {
        $roleService = $this->getIbexaTestCore()->getRoleService();

        $policyCreateStruct = $roleService->newPolicyCreateStruct('content', 'read');
        $policyCreateStruct->addLimitation(
            new SubtreeLimitation(['limitationValues' => [$pathString]])
        );

        $roleCreateStruct = $roleService->newRoleCreateStruct('subtree_reader');
        $roleCreateStruct->addPolicy($policyCreateStruct);

        $roleDraft = $roleService->createRole($roleCreateStruct);
        $roleService->publishRoleDraft($roleDraft);

        $user = $this->createUser('subtree_reader', 'Subtree', 'Reader');
        $roleService->assignRoleToUser($roleService->loadRole($roleDraft->id), $user);

        return $user;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\RoleService;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\LanguageLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Policy;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Contracts\Core\Repository\Values\User\RoleAssignment;
use Ibexa\Contracts\Core\Repository\Values\User\RoleCopyStruct;
use Ibexa\Contracts\Core\Repository\Values\User\RoleCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;
use Ibexa\Contracts\Core\Repository\Values\User\RoleUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupRoleAssignment;
use Ibexa\Contracts\Core\Repository\Values\User\UserRoleAssignment;
use Ibexa\Core\Repository\RoleService as CoveredRoleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test case for operations in the RoleService using in memory storage.
 *
 * The following IDs from the default Ibexa community edition database are used in
 * this test:
 *
 * <ul>
 *   <li>
 *     ContentType
 *     <ul>
 *       <li><strong>28</strong>: File</li>
 *       <li><strong>29</strong>: Flash</li>
 *       <li><strong>30</strong>: Image</li>
 *     </ul>
 *   </li>
 * <ul>
 */
#[CoversClass(CoveredRoleService::class)]
#[Group('role')]
class RoleServiceTest extends BaseTestCase
{
    /**
     * Test for the newRoleCreateStruct() method.
     */
    public function testNewRoleCreateStruct(): void
    {
        $repository = $this->getRepository();

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        self::assertInstanceOf(RoleCreateStruct::class, $roleCreate);
    }

    public function testNewRoleCopyStruct(): void
    {
        $repository = $this->getRepository();

        $roleService = $repository->getRoleService();
        $roleCopy = $roleService->newRoleCopyStruct('copiedRole');

        self::assertSame('copiedRole', $roleCopy->newIdentifier);
        self::assertSame([], $roleCopy->getPolicies());
    }

    /**
     * Test for the newRoleCreateStruct() method.
     */
    #[Depends('testNewRoleCreateStruct')]
    public function testNewRoleCreateStructSetsNamePropertyOnStruct(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        /* END: Use Case */

        self::assertEquals('roleName', $roleCreate->identifier);
    }

    /**
     * Test for the createRole() method.
     */
    #[Depends('testNewRoleCreateStruct')]
    public function testCreateRole()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $role = $roleService->createRole($roleCreate);

        /* END: Use Case */

        self::assertInstanceOf(
            RoleDraft::class,
            $role
        );

        return [
            'createStruct' => $roleCreate,
            'role' => $role,
        ];
    }

    /**
     * Test for the createRole() method.
     */
    #[Depends('testCreateRole')]
    public function testRoleCreateStructValues(array $data)
    {
        $createStruct = $data['createStruct'];
        $role = $data['role'];

        self::assertEquals(
            [
                'identifier' => $createStruct->identifier,
                'policies' => $createStruct->policies,
            ],
            [
                'identifier' => $role->identifier,
                'policies' => $role->policies,
            ]
        );
        self::assertNotNull($role->id);

        return $data;
    }

    /**
     * Test for the createRole() method.
     */
    #[Depends('testNewRoleCreateStruct')]
    public function testCreateRoleWithPolicy()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        // Create new subtree limitation
        $limitation = new SubtreeLimitation(
            [
                'limitationValues' => ['/1/2/'],
            ]
        );

        // Create policy create struct and add limitation to it
        $policyCreate = $roleService->newPolicyCreateStruct('content', 'read');
        $policyCreate->addLimitation($limitation);

        // Add policy create struct to role create struct
        $roleCreate->addPolicy($policyCreate);

        $role = $roleService->createRole($roleCreate);

        /* END: Use Case */

        self::assertInstanceOf(
            RoleDraft::class,
            $role
        );

        return [
            'createStruct' => $roleCreate,
            'role' => $role,
        ];
    }

    /**
     * Test for the createRole() method.
     */
    #[Depends('testCreateRoleWithPolicy')]
    public function testRoleCreateStructValuesWithPolicy(array $data)
    {
        $createStruct = $data['createStruct'];
        $role = $data['role'];

        self::assertEquals(
            [
                'identifier' => $createStruct->identifier,
                'policy_module' => $createStruct->policies[0]->module,
                'policy_function' => $createStruct->policies[0]->function,
                'policy_limitation' => array_values($createStruct->policies[0]->limitations),
            ],
            [
                'identifier' => $role->identifier,
                'policy_module' => $role->policies[0]->module,
                'policy_function' => $role->policies[0]->function,
                'policy_limitation' => array_values($role->policies[0]->limitations),
            ]
        );
        self::assertNotNull($role->id);

        return $data;
    }

    /**
     * Test creating a role with multiple policies.
     */
    public function testCreateRoleWithMultiplePolicies(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        $limitation1 = new Limitation\ContentTypeLimitation();
        $limitation1->limitationValues = ['1', '3', '13'];

        $limitation2 = new Limitation\SectionLimitation();
        $limitation2->limitationValues = ['2', '3'];

        $limitation3 = new Limitation\OwnerLimitation();
        $limitation3->limitationValues = ['1', '2'];

        $limitation4 = new Limitation\UserGroupLimitation();
        $limitation4->limitationValues = ['1'];

        $policyCreateStruct1 = $roleService->newPolicyCreateStruct('content', 'read');
        $policyCreateStruct1->addLimitation($limitation1);
        $policyCreateStruct1->addLimitation($limitation2);

        $policyCreateStruct2 = $roleService->newPolicyCreateStruct('content', 'edit');
        $policyCreateStruct2->addLimitation($limitation3);
        $policyCreateStruct2->addLimitation($limitation4);

        $roleCreateStruct = $roleService->newRoleCreateStruct('ultimate_permissions');
        $roleCreateStruct->addPolicy($policyCreateStruct1);
        $roleCreateStruct->addPolicy($policyCreateStruct2);

        $createdRole = $roleService->createRole($roleCreateStruct);

        self::assertInstanceOf(Role::class, $createdRole);
        self::assertGreaterThan(0, $createdRole->id);

        $this->assertPropertiesCorrect(
            [
                'identifier' => $roleCreateStruct->identifier,
            ],
            $createdRole
        );

        self::assertCount(2, $createdRole->getPolicies());

        foreach ($createdRole->getPolicies() as $policy) {
            self::assertInstanceOf(Policy::class, $policy);
            self::assertGreaterThan(0, $policy->id);
            self::assertEquals($createdRole->id, $policy->roleId);

            self::assertCount(2, $policy->getLimitations());

            foreach ($policy->getLimitations() as $limitation) {
                self::assertInstanceOf(Limitation::class, $limitation);

                if ($policy->module == 'content' && $policy->function == 'read') {
                    switch ($limitation->getIdentifier()) {
                        case Limitation::CONTENTTYPE:
                            self::assertEquals($limitation1->limitationValues, $limitation->limitationValues);
                            break;

                        case Limitation::SECTION:
                            self::assertEquals($limitation2->limitationValues, $limitation->limitationValues);
                            break;

                        default:
                            self::fail('Created role contains limitations not defined with create struct');
                    }
                } elseif ($policy->module == 'content' && $policy->function == 'edit') {
                    switch ($limitation->getIdentifier()) {
                        case Limitation::OWNER:
                            self::assertEquals($limitation3->limitationValues, $limitation->limitationValues);
                            break;

                        case Limitation::USERGROUP:
                            self::assertEquals($limitation4->limitationValues, $limitation->limitationValues);
                            break;

                        default:
                            self::fail('Created role contains limitations not defined with create struct');
                    }
                } else {
                    self::fail('Created role contains policy not defined with create struct');
                }
            }
        }
    }

    /**
     * Test for the createRoleDraft() method.
     */
    #[Depends('testNewRoleCreateStruct')]
    public function testCreateRoleDraft(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);
        $newRoleDraft = $roleService->createRoleDraft($role);

        /* END: Use Case */

        self::assertInstanceOf(
            RoleDraft::class,
            $newRoleDraft
        );
    }

    /**
     * Test for the createRole() method.
     */
    #[Depends('testCreateRole')]
    public function testCreateRoleThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('Editor');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        // This call will fail with an InvalidArgumentException, because Editor exists
        $roleService->createRole($roleCreate);

        /* END: Use Case */
    }

    /**
     * Test for the createRoleDraft() method.
     */
    #[Depends('testCreateRoleDraft')]
    public function testCreateRoleDraftThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('Editor');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);
        $roleService->createRoleDraft($role); // First role draft

        // This call will fail with an InvalidArgumentException, because there is already a draft
        $roleService->createRoleDraft($role);

        /* END: Use Case */
    }

    /**
     * Test for the createRole() method.
     */
    public function testCreateRoleThrowsLimitationValidationException(): void
    {
        $this->expectException(LimitationValidationException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Create new role create struct
        $roleCreate = $roleService->newRoleCreateStruct('Lumberjack');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        // Create new subtree limitation
        $limitation = new SubtreeLimitation(
            [
                'limitationValues' => ['/mountain/forest/tree/42/'],
            ]
        );

        // Create policy create struct and add limitation to it
        $policyCreate = $roleService->newPolicyCreateStruct('content', 'remove');
        $policyCreate->addLimitation($limitation);

        // Add policy create struct to role create struct
        $roleCreate->addPolicy($policyCreate);

        // This call will fail with an LimitationValidationException, because subtree
        // "/mountain/forest/tree/42/" does not exist
        $roleService->createRole($roleCreate);
        /* END: Use Case */
    }

    /**
     * Test for the createRole() method.
     */
    #[Depends('testNewRoleCreateStruct')]
    public function testCreateRoleInTransactionWithRollback(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();

        $repository->beginTransaction();

        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $createdRoleId = $roleService->createRole($roleCreate)->id;

        $repository->rollback();

        try {
            // This call will fail with a "NotFoundException"
            $role = $roleService->loadRole($createdRoleId);
        } catch (NotFoundException $e) {
            return;
        }
        /* END: Use Case */

        self::fail('Role object still exists after rollback.');
    }

    /**
     * Test for the createRoleDraft() method.
     */
    #[Depends('testNewRoleCreateStruct')]
    public function testCreateRoleDraftInTransactionWithRollback(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();

        $repository->beginTransaction();

        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $createdRoleId = $roleService->createRole($roleCreate)->id;

        $repository->rollback();

        try {
            // This call will fail with a "NotFoundException"
            $role = $roleService->loadRoleDraft($createdRoleId);
        } catch (NotFoundException $e) {
            return;
        }
        /* END: Use Case */

        self::fail('Role draft object still exists after rollback.');
    }

    public static function providerForCopyRoleTests(): array
    {
        $repository = static::resolveRepository();
        $roleService = $repository->getRoleService();

        $roleCreateStruct = $roleService->newRoleCreateStruct('newRole');
        $roleCopyStruct = $roleService->newRoleCopyStruct('copiedRole');

        $policyCreateStruct1 = $roleService->newPolicyCreateStruct('content', 'read');
        $policyCreateStruct2 = $roleService->newPolicyCreateStruct('content', 'edit');

        $roleLimitations = [
            new SectionLimitation(['limitationValues' => [2]]),
            new SubtreeLimitation(['limitationValues' => ['/1/2/']]),
        ];

        $policyCreateStruct1WithLimitations = $roleService->newPolicyCreateStruct('content', 'read');
        foreach ($roleLimitations as $roleLimitation) {
            $policyCreateStruct1WithLimitations->addLimitation($roleLimitation);
        }

        return [
            'without-policies' => [
                $roleCreateStruct,
                $roleCopyStruct,
                [],
            ],
            'with-policies' => [
                $roleCreateStruct,
                $roleCopyStruct,
                [$policyCreateStruct1, $policyCreateStruct2],
            ],
            'with-limitations' => [
                $roleCreateStruct,
                $roleCopyStruct,
                [$policyCreateStruct1WithLimitations, $policyCreateStruct2],
            ],
        ];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException
     */
    #[Depends('testNewRoleCopyStruct')]
    #[Depends('testLoadRoleByIdentifier')]
    #[DataProvider('providerForCopyRoleTests')]
    public function testCopyRole(RoleCreateStruct $roleCreateStruct, RoleCopyStruct $roleCopyStruct): void
    {
        $repository = $this->getRepository();

        $roleService = $repository->getRoleService();

        $roleDraft = $roleService->createRole($roleCreateStruct);

        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        $copiedRole = $roleService->copyRole($role, $roleCopyStruct);

        // Now verify that our change was saved
        $role = $roleService->loadRoleByIdentifier('copiedRole');

        self::assertEquals($role->id, $copiedRole->id);
        self::assertEquals('copiedRole', $role->identifier);
        self::assertEmpty($role->getPolicies());
    }

    /**
     * Test for the copyRole() method with added policies.
     *
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct[] $policies
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException
     */
    #[Depends('testNewRoleCopyStruct')]
    #[Depends('testLoadRoleByIdentifier')]
    #[DataProvider('providerForCopyRoleTests')]
    public function testCopyRoleWithPolicies(
        RoleCreateStruct $roleCreateStruct,
        RoleCopyStruct $roleCopyStruct,
        array $policies
    ): void {
        $repository = $this->getRepository();

        $roleService = $repository->getRoleService();

        foreach ($policies as $policy) {
            $roleCreateStruct->addPolicy($policy);
        }

        $roleDraft = $roleService->createRole($roleCreateStruct);

        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        $copiedRole = $roleService->copyRole($role, $roleCopyStruct);

        // Now verify that our change was saved
        $role = $roleService->loadRoleByIdentifier('copiedRole');

        self::assertEquals($role->getPolicies(), $copiedRole->getPolicies());
    }

    /**
     * Test for the copyRole() method with added policies and limitations.
     *
     *
     *
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct[] $policies
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException
     */
    #[Depends('testNewRoleCopyStruct')]
    #[Depends('testLoadRoleByIdentifier')]
    #[DataProvider('providerForCopyRoleTests')]
    public function testCopyRoleWithPoliciesAndLimitations(
        RoleCreateStruct $roleCreateStruct,
        RoleCopyStruct $roleCopyStruct,
        array $policies
    ): void {
        $repository = $this->getRepository();

        $roleService = $repository->getRoleService();

        foreach ($policies as $policy) {
            $roleCreateStruct->addPolicy($policy);
        }

        $roleDraft = $roleService->createRole($roleCreateStruct);

        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        $copiedRole = $roleService->copyRole($role, $roleCopyStruct);

        // Now verify that our change was saved
        $role = $roleService->loadRoleByIdentifier('copiedRole');

        $limitations = [];
        foreach ($role->getPolicies() as $policy) {
            $limitations[$policy->function] = $policy->getLimitations();
        }

        $limitationsCopied = [];
        foreach ($copiedRole->getPolicies() as $policy) {
            $limitationsCopied[$policy->function] = $policy->getLimitations();
        }

        self::assertEquals($role->getPolicies(), $copiedRole->getPolicies());
        foreach ($limitations as $policy => $limitation) {
            self::assertEquals($limitation, $limitationsCopied[$policy]);
        }
    }

    /**
     * Test for the loadRole() method.
     */
    #[Depends('testCreateRole')]
    public function testLoadRole(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);

        // Load the newly created role by its ID
        $role = $roleService->loadRole($roleDraft->id);

        /* END: Use Case */

        self::assertEquals('roleName', $role->identifier);
    }

    /**
     * Test for the loadRoleDraft() method.
     */
    #[Depends('testCreateRoleDraft')]
    public function testLoadRoleDraft(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);

        // Load the newly created role by its ID
        $role = $roleService->loadRoleDraft($roleDraft->id);

        /* END: Use Case */

        self::assertEquals('roleName', $role->identifier);
    }

    public function testLoadRoleDraftByRoleId(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $role = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($role);

        // Now create a new draft based on the role
        $newDraft = $roleService->createRoleDraft($role);
        $loadedRoleDraft = $roleService->loadRoleDraftByRoleId($role->id);

        /* END: Use Case */

        self::assertEquals('roleName', $role->identifier);
        self::assertInstanceOf(RoleDraft::class, $loadedRoleDraft);
        self::assertEquals($newDraft, $loadedRoleDraft);
    }

    /**
     * Test for the loadRole() method.
     */
    #[Depends('testLoadRole')]
    public function testLoadRoleThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        $nonExistingRoleId = $this->generateId('role', self::DB_INT_MAX);
        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();

        // This call will fail with a NotFoundException, because no such role exists.
        $roleService->loadRole($nonExistingRoleId);

        /* END: Use Case */
    }

    /**
     * Test for the loadRoleDraft() method.
     */
    #[Depends('testLoadRoleDraft')]
    public function testLoadRoleDraftThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        $nonExistingRoleId = $this->generateId('role', self::DB_INT_MAX);
        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();

        // This call will fail with a NotFoundException, because no such role exists.
        $roleService->loadRoleDraft($nonExistingRoleId);

        /* END: Use Case */
    }

    public function testLoadRoleDraftByRoleIdThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        $nonExistingRoleId = $this->generateId('role', self::DB_INT_MAX);
        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();

        // This call will fail with a NotFoundException, because no such role exists.
        $roleService->loadRoleDraftByRoleId($nonExistingRoleId);

        /* END: Use Case */
    }

    /**
     * Test for the loadRoleByIdentifier() method.
     */
    #[Depends('testCreateRole')]
    public function testLoadRoleByIdentifier(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);

        // Load the newly created role by its identifier
        $role = $roleService->loadRoleByIdentifier('roleName');

        /* END: Use Case */

        self::assertEquals('roleName', $role->identifier);
    }

    /**
     * Test for the loadRoleByIdentifier() method.
     */
    #[Depends('testLoadRoleByIdentifier')]
    public function testLoadRoleByIdentifierThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        $roleService = $repository->getRoleService();

        // This call will fail with a NotFoundException, because no such role exists.
        $roleService->loadRoleByIdentifier('MissingRole');

        /* END: Use Case */
    }

    /**
     * Test for the loadRoles() method.
     */
    #[Depends('testCreateRole')]
    public function testLoadRoles(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */

        // First create a custom role
        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('roleName');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);

        // Now load all available roles
        $roles = $roleService->loadRoles();

        foreach ($roles as $role) {
            if ($role->identifier === 'roleName') {
                break;
            }
        }

        /* END: Use Case */

        self::assertEquals('roleName', $role->identifier);
    }

    /**
     * Test for the loadRoles() method.
     */
    #[Depends('testLoadRoles')]
    public function testLoadRolesReturnsExpectedSetOfDefaultRoles(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        $roles = $roleService->loadRoles();

        $roleNames = [];
        foreach ($roles as $role) {
            $roleNames[] = $role->identifier;
        }
        /* END: Use Case */

        self::assertEqualsCanonicalizing(
            [
                'Administrator',
                'Anonymous',
                'Editor',
                'Member',
                'Partner',
            ],
            $roleNames
        );
    }

    /**
     * Test for the newRoleUpdateStruct() method.
     */
    public function testNewRoleUpdateStruct(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $roleUpdate = $roleService->newRoleUpdateStruct('newRole');
        /* END: Use Case */

        self::assertInstanceOf(RoleUpdateStruct::class, $roleUpdate);
    }

    /**
     * Test for the updateRoleDraft() method.
     */
    #[Depends('testNewRoleUpdateStruct')]
    #[Depends('testLoadRoleDraft')]
    public function testUpdateRoleDraft(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);

        $roleUpdate = $roleService->newRoleUpdateStruct();
        $roleUpdate->identifier = 'updatedRole';

        $updatedRole = $roleService->updateRoleDraft($roleDraft, $roleUpdate);
        /* END: Use Case */

        // Now verify that our change was saved
        $role = $roleService->loadRoleDraft($updatedRole->id);

        self::assertEquals($role->identifier, 'updatedRole');
    }

    /**
     * Test for the updateRoleDraft() method.
     */
    #[Depends('testUpdateRoleDraft')]
    public function testUpdateRoleDraftThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);

        $roleUpdate = $roleService->newRoleUpdateStruct();
        $roleUpdate->identifier = 'Editor';

        // This call will fail with an InvalidArgumentException, because Editor is a predefined role
        $roleService->updateRoleDraft($roleDraft, $roleUpdate);
        /* END: Use Case */
    }

    /**
     * Test for the deleteRole() method.
     */
    #[Depends('testCreateRole')]
    #[Depends('testLoadRoles')]
    public function testDeleteRole(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        $roleService->deleteRole($role);
        /* END: Use Case */

        self::assertCount(5, $roleService->loadRoles());
    }

    /**
     * Test for the deleteRoleDraft() method.
     */
    #[Depends('testLoadRoleDraft')]
    public function testDeleteRoleDraft(): void
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);
        $roleID = $roleDraft->id;
        $roleService->deleteRoleDraft($roleDraft);

        // This call will fail with a NotFoundException, because the draft no longer exists
        $roleService->loadRoleDraft($roleID);
        /* END: Use Case */
    }

    /**
     * Test for the newPolicyCreateStruct() method.
     */
    public function testNewPolicyCreateStruct(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $policyCreate = $roleService->newPolicyCreateStruct('content', 'create');
        /* END: Use Case */

        self::assertInstanceOf(PolicyCreateStruct::class, $policyCreate);
    }

    /**
     * Test for the newPolicyCreateStruct() method.
     */
    #[Depends('testNewPolicyCreateStruct')]
    public function testNewPolicyCreateStructSetsStructProperties(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $policyCreate = $roleService->newPolicyCreateStruct('content', 'create');
        /* END: Use Case */

        self::assertEquals(
            ['content', 'create'],
            [$policyCreate->module, $policyCreate->function]
        );
    }

    /**
     * Test for the addPolicyByRoleDraft() method.
     */
    #[Depends('testCreateRoleDraft')]
    #[Depends('testNewPolicyCreateStruct')]
    public function testAddPolicyByRoleDraft(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);

        $roleDraft = $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $roleService->newPolicyCreateStruct('content', 'delete')
        );
        $roleDraft = $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $roleService->newPolicyCreateStruct('content', 'create')
        );
        /* END: Use Case */

        $actual = [];
        foreach ($roleDraft->getPolicies() as $policy) {
            $actual[] = [
                'module' => $policy->module,
                'function' => $policy->function,
            ];
        }
        usort(
            $actual,
            static function ($p1, $p2): int {
                return strcasecmp($p1['function'], $p2['function']);
            }
        );

        self::assertEquals(
            [
                [
                    'module' => 'content',
                    'function' => 'create',
                ],
                [
                    'module' => 'content',
                    'function' => 'delete',
                ],
            ],
            $actual
        );
    }

    /**
     * Test for the addPolicyByRoleDraft() method.
     *
     * @return array [\Ibexa\Contracts\Core\Repository\Values\User\RoleDraft, \Ibexa\Contracts\Core\Repository\Values\User\Policy]
     */
    #[Depends('testAddPolicyByRoleDraft')]
    public function testAddPolicyByRoleDraftUpdatesRole()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);

        $policyCreate = $roleService->newPolicyCreateStruct('content', 'create');
        $roleDraft = $roleService->addPolicyByRoleDraft($roleDraft, $policyCreate);

        $policy = null;
        foreach ($roleDraft->getPolicies() as $policy) {
            if ($policy->module === 'content' && $policy->function === 'create') {
                break;
            }
        }
        /* END: Use Case */

        self::assertInstanceOf(
            Policy::class,
            $policy
        );

        return [$roleDraft, $policy];
    }

    /**
     * Test for the addPolicyByRoleDraft() method.
     *
     * @param array $roleAndPolicy
     */
    #[Depends('testAddPolicyByRoleDraftUpdatesRole')]
    public function testAddPolicyByRoleDraftSetsPolicyProperties($roleAndPolicy): void
    {
        list($role, $policy) = $roleAndPolicy;

        self::assertEquals(
            [$role->id, 'content', 'create'],
            [$policy->roleId, $policy->module, $policy->function]
        );
    }

    /**
     * Test for the addPolicyByRoleDraft() method.
     */
    #[Depends('testNewPolicyCreateStruct')]
    #[Depends('testCreateRoleDraft')]
    public function testAddPolicyByRoleDraftThrowsLimitationValidationException(): void
    {
        $this->expectException(LimitationValidationException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        $roleCreate = $roleService->newRoleCreateStruct('Lumberjack');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);

        // Create new subtree limitation
        $limitation = new SubtreeLimitation(
            [
                'limitationValues' => ['/mountain/forest/tree/42/'],
            ]
        );

        // Create policy create struct and add limitation to it
        $policyCreateStruct = $roleService->newPolicyCreateStruct('content', 'remove');
        $policyCreateStruct->addLimitation($limitation);

        // This call will fail with an LimitationValidationException, because subtree
        // "/mountain/forest/tree/42/" does not exist
        $roleService->addPolicyByRoleDraft($roleDraft, $policyCreateStruct);
        /* END: Use Case */
    }

    /**
     * Test for the createRole() method.
     */
    #[Depends('testAddPolicyByRoleDraftUpdatesRole')]
    public function testCreateRoleWithAddPolicy(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Instantiate a new create struct
        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        // Add some role policies
        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct(
                'content',
                'read'
            )
        );
        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct(
                'content',
                'translate'
            )
        );

        // Create new role instance
        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        $policies = [];
        foreach ($role->getPolicies() as $policy) {
            $policies[] = ['module' => $policy->module, 'function' => $policy->function];
        }
        /* END: Use Case */
        array_multisort($policies);

        self::assertEquals(
            [
                [
                    'module' => 'content',
                    'function' => 'read',
                ],
                [
                    'module' => 'content',
                    'function' => 'translate',
                ],
            ],
            $policies
        );
    }

    /**
     * Test for the createRoleDraft() method.
     */
    #[Depends('testAddPolicyByRoleDraftUpdatesRole')]
    public function testCreateRoleDraftWithAddPolicy(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Instantiate a new create struct
        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        // Add some role policies
        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct(
                'content',
                'read'
            )
        );
        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct(
                'content',
                'translate'
            )
        );

        // Create new role instance
        $roleDraft = $roleService->createRole($roleCreate);

        $policies = [];
        foreach ($roleDraft->getPolicies() as $policy) {
            $policies[] = ['module' => $policy->module, 'function' => $policy->function];
        }
        /* END: Use Case */

        self::assertEquals(
            [
                [
                    'module' => 'content',
                    'function' => 'read',
                ],
                [
                    'module' => 'content',
                    'function' => 'translate',
                ],
            ],
            $policies
        );
    }

    /**
     * Test for the newPolicyUpdateStruct() method.
     */
    public function testNewPolicyUpdateStruct(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $policyUpdate = $roleService->newPolicyUpdateStruct();
        /* END: Use Case */

        self::assertInstanceOf(
            PolicyUpdateStruct::class,
            $policyUpdate
        );
    }

    public function testUpdatePolicyByRoleDraftNoLimitation(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Instantiate new policy create
        $policyCreate = $roleService->newPolicyCreateStruct('foo', 'bar');

        // Instantiate a role create and add the policy create
        $roleCreate = $roleService->newRoleCreateStruct('myRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleCreate->addPolicy($policyCreate);

        // Create a new role instance.
        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        $roleDraft = $roleService->createRoleDraft($role);
        // Search for the new policy instance
        $policy = null;
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft $policy */
        foreach ($roleDraft->getPolicies() as $policy) {
            if ($policy->module === 'foo' && $policy->function === 'bar') {
                break;
            }
        }

        // Create an update struct
        $policyUpdate = $roleService->newPolicyUpdateStruct();

        // Update the the policy
        $policy = $roleService->updatePolicyByRoleDraft(
            $roleDraft,
            $policy,
            $policyUpdate
        );
        $roleService->publishRoleDraft($roleDraft);

        /* END: Use Case */

        self::assertInstanceOf(
            Policy::class,
            $policy
        );

        self::assertEquals([], $policy->getLimitations());
    }

    /**
     * @return array
     */
    #[Depends('testAddPolicyByRoleDraft')]
    #[Depends('testNewPolicyUpdateStruct')]
    public function testUpdatePolicyByRoleDraft()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Instantiate new policy create
        $policyCreate = $roleService->newPolicyCreateStruct('content', 'translate');

        // Add some limitations for the new policy
        $policyCreate->addLimitation(
            new LanguageLimitation(
                [
                    'limitationValues' => ['eng-GB', 'eng-US'],
                ]
            )
        );

        // Instantiate a role create and add the policy create
        $roleCreate = $roleService->newRoleCreateStruct('myRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleCreate->addPolicy($policyCreate);

        // Create a new role instance.
        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        $roleDraft = $roleService->createRoleDraft($role);
        // Search for the new policy instance
        $policy = null;
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft $policy */
        foreach ($roleDraft->getPolicies() as $policy) {
            if ($policy->module === 'content' && $policy->function === 'translate') {
                break;
            }
        }

        // Create an update struct and set a modified limitation
        $policyUpdate = $roleService->newPolicyUpdateStruct();
        $policyUpdate->addLimitation(
            new ContentTypeLimitation(
                [
                    'limitationValues' => [29, 30],
                ]
            )
        );

        // Update the the policy
        $policy = $roleService->updatePolicyByRoleDraft(
            $roleDraft,
            $policy,
            $policyUpdate
        );
        $roleService->publishRoleDraft($roleDraft);

        /* END: Use Case */

        self::assertInstanceOf(
            Policy::class,
            $policy
        );

        return [$roleService->loadRole($role->id), $policy];
    }

    /**
     * @param array $roleAndPolicy
     */
    #[Depends('testUpdatePolicyByRoleDraft')]
    public function testUpdatePolicyUpdatesLimitations($roleAndPolicy)
    {
        list($role, $policy) = $roleAndPolicy;

        self::assertEquals(
            [
                new ContentTypeLimitation(
                    [
                        'limitationValues' => [29, 30],
                    ]
                ),
            ],
            $policy->getLimitations()
        );

        return $role;
    }

    /**
     * Test for the updatePolicy() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Role $role
     */
    #[Depends('testUpdatePolicyUpdatesLimitations')]
    public function testUpdatePolicyUpdatesRole($role): void
    {
        $limitations = [];
        foreach ($role->getPolicies() as $policy) {
            foreach ($policy->getLimitations() as $limitation) {
                $limitations[] = $limitation;
            }
        }

        self::assertCount(1, $limitations);
        self::assertInstanceOf(
            Limitation::class,
            $limitations[0]
        );

        $expectedData = [
            'limitationValues' => [29, 30],
        ];
        $this->assertPropertiesCorrectUnsorted(
            $expectedData,
            $limitations[0]
        );
    }

    /**
     * Test for the updatePolicy() method.
     */
    #[Depends('testAddPolicyByRoleDraft')]
    #[Depends('testNewPolicyCreateStruct')]
    #[Depends('testNewPolicyUpdateStruct')]
    #[Depends('testNewRoleCreateStruct')]
    #[Depends('testCreateRole')]
    public function testUpdatePolicyByRoleDraftThrowsLimitationValidationException(): void
    {
        $this->expectException(LimitationValidationException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Instantiate new policy create
        $policyCreate = $roleService->newPolicyCreateStruct('content', 'remove');

        // Add some limitations for the new policy
        $policyCreate->addLimitation(
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/1/2/'],
                ]
            )
        );

        // Instantiate a role create and add the policy create
        $roleCreate = $roleService->newRoleCreateStruct('myRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleCreate->addPolicy($policyCreate);

        // Create a new role instance.
        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);
        $roleDraft = $roleService->createRoleDraft($role);
        // Search for the new policy instance
        $policy = null;
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft $policy */
        foreach ($roleDraft->getPolicies() as $policy) {
            if ($policy->module === 'content' && $policy->function === 'remove') {
                break;
            }
        }

        // Create an update struct and set a modified limitation
        $policyUpdate = $roleService->newPolicyUpdateStruct();
        $policyUpdate->addLimitation(
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/mountain/forest/tree/42/'],
                ]
            )
        );

        // This call will fail with an LimitationValidationException, because subtree
        // "/mountain/forest/tree/42/" does not exist
        $policy = $roleService->updatePolicyByRoleDraft(
            $roleDraft,
            $policy,
            $policyUpdate
        );
        /* END: Use Case */
    }

    /**
     * Test for the removePolicyByRoleDraft() method.
     */
    #[Depends('testAddPolicyByRoleDraft')]
    public function testRemovePolicyByRoleDraft(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Instantiate a new role create
        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        // Create a new role with two policies
        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $roleService->newPolicyCreateStruct('content', 'create')
        );
        $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $roleService->newPolicyCreateStruct('content', 'delete')
        );

        // Delete all policies from the new role
        foreach ($roleDraft->getPolicies() as $policy) {
            $roleDraft = $roleService->removePolicyByRoleDraft($roleDraft, $policy);
        }
        /* END: Use Case */

        self::assertSame([], $roleDraft->getPolicies());
    }

    /**
     * Test for the addPolicyByRoleDraft() method.
     */
    public function testAddPolicyWithRoleAssignment(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $userService = $repository->getUserService();

        /* Create new user group */
        $mainGroupId = $this->generateId('group', 4);
        $parentUserGroup = $userService->loadUserGroup($mainGroupId);
        $userGroupCreate = $userService->newUserGroupCreateStruct('eng-GB');
        $userGroupCreate->setField('name', 'newUserGroup');
        $userGroup = $userService->createUserGroup($userGroupCreate, $parentUserGroup);

        /* Create Role */
        $roleCreate = $roleService->newRoleCreateStruct('newRole');
        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);

        $role = $roleService->loadRole($roleDraft->id);
        $roleService->assignRoleToUserGroup($role, $userGroup);

        $roleAssignmentsBeforeNewPolicy = [...$roleService->getRoleAssignments($role)][0];

        /* Add new policy to existing role */
        $roleUpdateDraft = $roleService->createRoleDraft($role);
        $roleUpdateDraft = $roleService->addPolicyByRoleDraft(
            $roleUpdateDraft,
            $roleService->newPolicyCreateStruct('content', 'create')
        );
        $roleService->publishRoleDraft($roleUpdateDraft);

        $roleAfterUpdate = $roleService->loadRole($role->id);
        $roleAssignmentsAfterNewPolicy = [...$roleService->getRoleAssignments($roleAfterUpdate)][0];
        /* END: Use Case */

        self::assertNotEquals($roleAssignmentsBeforeNewPolicy->getId(), $roleAssignmentsAfterNewPolicy->getId());
    }

    /**
     * Test loading user/group role assignments.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroupRoleAssignment
     */
    public function testLoadRoleAssignment()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $user = $repository->getUserService()->loadUser(14);

        // Check initial empty assignments (also warms up potential cache to validate it is correct below)
        self::assertCount(0, $roleService->getRoleAssignmentsForUser($user));

        // Assignment to user group
        $groupRoleAssignment = $roleService->loadRoleAssignment(25);

        // Assignment to user
        $role = $roleService->loadRole(2);
        $roleService->assignRoleToUser($role, $user);
        $userRoleAssignments = iterator_to_array($roleService->getRoleAssignmentsForUser($user));

        $userRoleAssignment = $roleService->loadRoleAssignment($userRoleAssignments[0]->getId());
        /* END: Use Case */

        self::assertInstanceOf(UserGroupRoleAssignment::class, $groupRoleAssignment);

        self::assertEquals(
            [
                12,
                2,
                25,
            ],
            [
                $groupRoleAssignment->userGroup->id,
                $groupRoleAssignment->role->id,
                $groupRoleAssignment->id,
            ]
        );

        self::assertInstanceOf(UserRoleAssignment::class, $userRoleAssignment);
        self::assertEquals(14, $userRoleAssignment->user->id);

        return $groupRoleAssignment;
    }

    /**
     * Test for the getRoleAssignments() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\RoleAssignment[]
     */
    #[Depends('testLoadRoleByIdentifier')]
    public function testGetRoleAssignments()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Load the editor role
        $role = $roleService->loadRoleByIdentifier('Editor');

        // Load all assigned users and user groups
        $roleAssignments = iterator_to_array($roleService->getRoleAssignments($role));

        /* END: Use Case */

        self::assertCount(2, $roleAssignments);
        self::assertInstanceOf(
            UserGroupRoleAssignment::class,
            $roleAssignments[0]
        );
        self::assertInstanceOf(
            UserGroupRoleAssignment::class,
            $roleAssignments[1]
        );

        return $roleAssignments;
    }

    /**
     * Test for the getRoleAssignments() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\RoleAssignment[] $roleAssignments
     */
    #[Depends('testGetRoleAssignments')]
    public function testGetRoleAssignmentsContainExpectedLimitation(array $roleAssignments): void
    {
        self::assertEquals(
            'Subtree',
            reset($roleAssignments)->limitation->getIdentifier()
        );
    }

    public function testLoadRoleAssignments(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        $role = $this->createRoleWithPolicies('testLoadRoleAssignments', []);
        $user = $this->createUser('test', 'Test', 'Test');
        $user2 = $this->createUser('test2', 'Test2', 'Test2');

        $roleService->assignRoleToUser($role, $user);
        $roleService->assignRoleToUser($role, $user2);

        $loadedRole = $roleService->loadRole($role->id);

        $roleAssignments = iterator_to_array($roleService->loadRoleAssignments($loadedRole, 0, 1));

        self::assertCount(1, $roleAssignments);
        self::assertInstanceOf(UserRoleAssignment::class, $roleAssignments[0]);
    }

    public function testLoadRoleAssignmentsWithDeletedUser(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();
        $userService = $repository->getUserService();

        $role = $roleService->loadRoleByIdentifier('Editor');
        $roleAssignments = $roleService->loadRoleAssignments($role);
        $expectedCount = count($roleAssignments);

        // Adding user should add '1' to the assignments count
        $newUser = $this->createUser('login', 'Test', 'Test');
        $roleService->assignRoleToUser($role, $newUser);
        ++$expectedCount;

        $roleAssignments = $roleService->loadRoleAssignments($role);

        self::assertCount($expectedCount, $roleAssignments);

        // Removing user should subtract '1' from the assignments count
        $userService->deleteUser($newUser);
        --$expectedCount;

        $roleAssignments = $roleService->loadRoleAssignments($role);

        self::assertCount($expectedCount, $roleAssignments);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCountRoleAssignmentsAfterRemovingRoleAssignment(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        $role = $roleService->loadRoleByIdentifier('Editor');
        $newUser = $this->createUser('login', 'Test', 'Test');
        $roleService->assignRoleToUser($role, $newUser);
        $roleAssignmentsCount = $roleService->countRoleAssignments($role);

        $userRoleAssignment = $this->loadRoleAssignmentForUser($roleService, $role, $newUser);
        $roleService->removeRoleAssignment($userRoleAssignment);

        self::assertEquals($roleAssignmentsCount - 1, $roleService->countRoleAssignments($role));
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCountRoleAssignmentsAfterDeletingUser(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();
        $userService = $repository->getUserService();

        $role = $roleService->loadRoleByIdentifier('Editor');
        $newUser = $this->createUser('login', 'Test', 'Test');
        $roleService->assignRoleToUser($role, $newUser);

        $roleAssignmentsCount = $roleService->countRoleAssignments($role);

        $userService->deleteUser($newUser);
        $afterUserDeleteCount = $roleService->countRoleAssignments($role);

        self::assertEquals($roleAssignmentsCount - 1, $afterUserDeleteCount);
    }

    /**
     * Test for the assignRoleToUser() method.
     */
    #[Depends('testGetRoleAssignments')]
    public function testAssignRoleToUser(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Load the existing "Administrator" role
        $role = $roleService->loadRoleByIdentifier('Administrator');

        // Assign the "Administrator" role to the newly created user
        $roleService->assignRoleToUser($role, $user);

        // The assignments array will contain the new role<->user assignment
        $roleAssignments = $roleService->getRoleAssignments($role);
        /* END: Use Case */

        // Administrator + Example User
        self::assertCount(2, $roleAssignments);
    }

    /**
     * Test for the assignRoleToUser() method.
     */
    #[Depends('testAssignRoleToUser')]
    public function testAssignRoleToUserWithRoleLimitation(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Load the existing "Anonymous" role
        $role = $roleService->loadRoleByIdentifier('Anonymous');

        // Assign the "Anonymous" role to the newly created user
        $roleService->assignRoleToUser(
            $role,
            $user,
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/1/43/'],
                ]
            )
        );

        // The assignments array will contain the new role<->user assignment
        $roleAssignments = $roleService->getRoleAssignments($role);
        /* END: Use Case */

        // Members + Partners + Anonymous + Example User
        self::assertCount(4, $roleAssignments);

        // Get the role limitation
        $roleLimitation = null;
        foreach ($roleAssignments as $roleAssignment) {
            $roleLimitation = $roleAssignment->getRoleLimitation();
            if ($roleLimitation) {
                self::assertInstanceOf(
                    UserRoleAssignment::class,
                    $roleAssignment
                );
                break;
            }
        }

        self::assertEquals(
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/1/43/'],
                ]
            ),
            $roleLimitation
        );

        // Test again to see values being merged
        $roleService->assignRoleToUser(
            $role,
            $user,
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/1/43/', '/1/2/'],
                ]
            )
        );

        // The assignments array will contain the new role<->user assignment
        $roleAssignments = $roleService->getRoleAssignments($role);

        // Members + Partners + Anonymous + Example User
        self::assertCount(5, $roleAssignments);

        // Get the role limitation
        $roleLimitations = [];
        foreach ($roleAssignments as $roleAssignment) {
            $roleLimitation = $roleAssignment->getRoleLimitation();
            if ($roleLimitation) {
                self::assertInstanceOf(
                    UserRoleAssignment::class,
                    $roleAssignment
                );
                $roleLimitations[] = $roleLimitation;
            }
        }
        array_multisort($roleLimitations);

        self::assertEquals(
            [
                new SubtreeLimitation(
                    [
                        'limitationValues' => ['/1/2/'],
                    ]
                ),
                new SubtreeLimitation(
                    [
                        'limitationValues' => ['/1/43/'],
                    ]
                ),
            ],
            $roleLimitations
        );
    }

    /**
     * Test for the assignRoleToUser() method.
     */
    #[Depends('testAssignRoleToUser')]
    #[Depends('testLoadRoleByIdentifier')]
    public function testAssignRoleToUserWithRoleLimitationThrowsLimitationValidationException(): void
    {
        $this->expectException(LimitationValidationException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Load the existing "Anonymous" role
        $role = $roleService->loadRoleByIdentifier('Anonymous');

        // Get current user
        $permissionResolver = $this->getRepository()->getPermissionResolver();
        $userService = $repository->getUserService();
        $currentUser = $userService->loadUser($permissionResolver->getCurrentUserReference()->getUserId());

        // Assign the "Anonymous" role to the current user
        // This call will fail with an LimitationValidationException, because subtree "/lorem/ipsum/42/"
        // does not exists
        $roleService->assignRoleToUser(
            $role,
            $currentUser,
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/lorem/ipsum/42/'],
                ]
            )
        );
        /* END: Use Case */
    }

    /**
     * Test for the assignRoleToUser() method.
     *
     * Makes sure assigning role several times throws.
     */
    #[Depends('testAssignRoleToUser')]
    #[Depends('testLoadRoleByIdentifier')]
    public function testAssignRoleToUserThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Load the existing "Anonymous" role
        $role = $roleService->loadRoleByIdentifier('Anonymous');

        // Get current user
        $permissionResolver = $this->getRepository()->getPermissionResolver();
        $userService = $repository->getUserService();
        $currentUser = $userService->loadUser($permissionResolver->getCurrentUserReference()->getUserId());

        // Assign the "Anonymous" role to the current user
        try {
            $roleService->assignRoleToUser(
                $role,
                $currentUser
            );
        } catch (Exception $e) {
            self::fail('Got exception at first valid attempt to assign role');
        }

        // Re-Assign the "Anonymous" role to the current user
        // This call will fail with an InvalidArgumentException, because limitation is already assigned
        $roleService->assignRoleToUser(
            $role,
            $currentUser
        );
        /* END: Use Case */
    }

    /**
     * Test for the assignRoleToUser() method.
     *
     * Makes sure assigning role several times with same limitations throws.
     */
    #[Depends('testAssignRoleToUser')]
    #[Depends('testLoadRoleByIdentifier')]
    public function testAssignRoleToUserWithRoleLimitationThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();

        // Load the existing "Anonymous" role
        $role = $roleService->loadRoleByIdentifier('Anonymous');

        // Get current user
        $permissionResolver = $this->getRepository()->getPermissionResolver();
        $userService = $repository->getUserService();
        $currentUser = $userService->loadUser($permissionResolver->getCurrentUserReference()->getUserId());

        // Assign the "Anonymous" role to the current user
        try {
            $roleService->assignRoleToUser(
                $role,
                $currentUser,
                new SubtreeLimitation(
                    [
                        'limitationValues' => ['/1/43/', '/1/2/'],
                    ]
                )
            );
        } catch (Exception $e) {
            self::fail('Got exception at first valid attempt to assign role');
        }

        // Re-Assign the "Anonymous" role to the current user
        // This call will fail with an InvalidArgumentException, because limitation is already assigned
        $roleService->assignRoleToUser(
            $role,
            $currentUser,
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/1/43/'],
                ]
            )
        );
        /* END: Use Case */
    }

    /**
     * Test for the removeRoleAssignment() method.
     */
    #[Depends('testAssignRoleToUser')]
    public function testRemoveRoleAssignment(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Load the existing "Member" role
        $role = $roleService->loadRoleByIdentifier('Member');

        // Assign the "Member" role to the newly created user
        $roleService->assignRoleToUser($role, $user);

        // Unassign user from role
        $roleAssignments = $roleService->getRoleAssignmentsForUser($user);
        foreach ($roleAssignments as $roleAssignment) {
            if ($roleAssignment->role->id === $role->id) {
                $roleService->removeRoleAssignment($roleAssignment);
            }
        }
        // The assignments array will not contain the new role<->user assignment
        $roleAssignments = $roleService->getRoleAssignments($role);
        /* END: Use Case */

        // Members + Editors + Partners
        self::assertCount(3, $roleAssignments);
    }

    /**
     * Test for the getRoleAssignmentsForUser() method.
     */
    #[Depends('testAssignRoleToUser')]
    #[Depends('testCreateRoleWithAddPolicy')]
    public function testGetRoleAssignmentsForUserDirect(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Instantiate a role create and add some policies
        $roleCreate = $roleService->newRoleCreateStruct('Example Role');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct('user', 'login')
        );
        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct('content', 'read')
        );
        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct('content', 'edit')
        );

        // Create the new role instance
        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        // Check inital empty assigments (also warms up potential cache to validate it is correct below)
        self::assertCount(0, $roleService->getRoleAssignmentsForUser($user));
        self::assertCount(0, $roleService->getRoleAssignments($role));

        // Assign role to new user
        $roleService->assignRoleToUser($role, $user);

        // Load the currently assigned role
        $roleAssignments = $roleService->getRoleAssignmentsForUser($user);
        /* END: Use Case */

        self::assertCount(1, $roleAssignments);
        self::assertInstanceOf(
            UserRoleAssignment::class,
            reset($roleAssignments)
        );
        self::assertCount(1, $roleService->getRoleAssignments($role));
    }

    /**
     * Test for the getRoleAssignmentsForUser() method.
     */
    #[Depends('testAssignRoleToUser')]
    #[Depends('testCreateRoleWithAddPolicy')]
    public function testGetRoleAssignmentsForUserEmpty(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        /* BEGIN: Use Case */
        $permissionResolver = $this->getRepository()->getPermissionResolver();
        $userService = $repository->getUserService();
        $adminUser = $userService->loadUser($permissionResolver->getCurrentUserReference()->getUserId());

        // Load the currently assigned role
        $roleAssignments = $roleService->getRoleAssignmentsForUser($adminUser);
        /* END: Use Case */

        self::assertCount(0, $roleAssignments);
    }

    /**
     * Test for the getRoleAssignmentsForUser() method.
     */
    #[Depends('testAssignRoleToUser')]
    #[Depends('testCreateRoleWithAddPolicy')]
    public function testGetRoleAssignmentsForUserInherited(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        /* BEGIN: Use Case */
        $permissionResolver = $this->getRepository()->getPermissionResolver();
        $userService = $repository->getUserService();
        $adminUser = $userService->loadUser($permissionResolver->getCurrentUserReference()->getUserId());

        // Load the currently assigned role + inherited role assignments
        $roleAssignments = $roleService->getRoleAssignmentsForUser($adminUser, true);
        /* END: Use Case */

        self::assertCount(1, $roleAssignments);
        self::assertInstanceOf(
            UserGroupRoleAssignment::class,
            reset($roleAssignments)
        );
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     */
    #[Depends('testGetRoleAssignments')]
    public function testAssignRoleToUserGroup(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();

        // Load the existing "Administrator" role
        $role = $roleService->loadRoleByIdentifier('Administrator');

        // Assign the "Administrator" role to the newly created user group
        $roleService->assignRoleToUserGroup($role, $userGroup);

        // The assignments array will contain the new role<->group assignment
        $roleAssignments = $roleService->getRoleAssignments($role);
        /* END: Use Case */

        // Administrator + Example Group
        self::assertCount(2, $roleAssignments);
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     *
     * Related issue: EZP-29113
     */
    public function testAssignRoleToUserGroupAffectsRoleAssignmentsForUser(): void
    {
        $roleService = $this->getRepository()->getRoleService();

        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();
        $user = $this->createUser('user', 'John', 'Doe', $userGroup);

        $initRoleAssignments = $roleService->getRoleAssignmentsForUser($user, true);

        // Load the existing "Administrator" role
        $role = $roleService->loadRoleByIdentifier('Administrator');

        // Assign the "Administrator" role to the newly created user group
        $roleService->assignRoleToUserGroup($role, $userGroup);

        $updatedRoleAssignments = $roleService->getRoleAssignmentsForUser($user, true);
        /* END: Use Case */

        self::assertEmpty($initRoleAssignments);
        self::assertCount(1, $updatedRoleAssignments);
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     */
    #[Depends('testAssignRoleToUserGroup')]
    public function testAssignRoleToUserGroupWithRoleLimitation(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();

        // Load the existing "Anonymous" role
        $role = $roleService->loadRoleByIdentifier('Anonymous');

        // Assign the "Anonymous" role to the newly created user group
        $roleService->assignRoleToUserGroup(
            $role,
            $userGroup,
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/1/43/'],
                ]
            )
        );

        // The assignments array will contain the new role<->group assignment
        $roleAssignments = $roleService->getRoleAssignments($role);
        /* END: Use Case */

        // Members + Partners + Anonymous + Example Group
        self::assertCount(4, $roleAssignments);

        // Get the role limitation
        $roleLimitation = null;
        foreach ($roleAssignments as $roleAssignment) {
            $roleLimitation = $roleAssignment->getRoleLimitation();
            if ($roleLimitation) {
                break;
            }
        }

        self::assertEquals(
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/1/43/'],
                ]
            ),
            $roleLimitation
        );

        // Test again to see values being merged
        $roleService->assignRoleToUserGroup(
            $role,
            $userGroup,
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/1/43/', '/1/2/'],
                ]
            )
        );

        // The assignments array will contain the new role<->user assignment
        $roleAssignments = $roleService->getRoleAssignments($role);

        // Members + Partners + Anonymous + Example User
        self::assertCount(5, $roleAssignments);

        // Get the role limitation
        $roleLimitations = [];
        foreach ($roleAssignments as $roleAssignment) {
            $roleLimitation = $roleAssignment->getRoleLimitation();
            if ($roleLimitation) {
                self::assertInstanceOf(
                    UserGroupRoleAssignment::class,
                    $roleAssignment
                );
                $roleLimitations[] = $roleLimitation;
            }
        }
        array_multisort($roleLimitations);

        self::assertEquals(
            [
                new SubtreeLimitation(
                    [
                        'limitationValues' => ['/1/2/'],
                    ]
                ),
                new SubtreeLimitation(
                    [
                        'limitationValues' => ['/1/43/'],
                    ]
                ),
            ],
            $roleLimitations
        );
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     */
    #[Depends('testLoadRoleByIdentifier')]
    #[Depends('testAssignRoleToUserGroup')]
    public function testAssignRoleToUserGroupWithRoleLimitationThrowsLimitationValidationException(): void
    {
        $this->expectException(LimitationValidationException::class);

        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Use Case */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();
        $roleService = $repository->getRoleService();

        $userGroup = $userService->loadUserGroup($mainGroupId);

        // Load the existing "Anonymous" role
        $role = $roleService->loadRoleByIdentifier('Anonymous');

        // Assign the "Anonymous" role to the newly created user group
        // This call will fail with an LimitationValidationException, because subtree "/lorem/ipsum/42/"
        // does not exists
        $roleService->assignRoleToUserGroup(
            $role,
            $userGroup,
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/lorem/ipsum/42/'],
                ]
            )
        );
        /* END: Use Case */
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     *
     * Makes sure assigning role several times throws.
     */
    #[Depends('testLoadRoleByIdentifier')]
    #[Depends('testAssignRoleToUserGroup')]
    public function testAssignRoleToUserGroupThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Use Case */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();
        $roleService = $repository->getRoleService();

        $userGroup = $userService->loadUserGroup($mainGroupId);

        // Load the existing "Anonymous" role
        $role = $roleService->loadRoleByIdentifier('Anonymous');

        // Assign the "Anonymous" role to the newly created user group
        try {
            $roleService->assignRoleToUserGroup(
                $role,
                $userGroup
            );
        } catch (Exception $e) {
            self::fail('Got exception at first valid attempt to assign role');
        }

        // Re-Assign the "Anonymous" role to the newly created user group
        // This call will fail with an InvalidArgumentException, because role is already assigned
        $roleService->assignRoleToUserGroup(
            $role,
            $userGroup
        );
        /* END: Use Case */
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     *
     * Makes sure assigning role several times with same limitations throws.
     */
    #[Depends('testLoadRoleByIdentifier')]
    #[Depends('testAssignRoleToUserGroup')]
    public function testAssignRoleToUserGroupWithRoleLimitationThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Use Case */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();
        $roleService = $repository->getRoleService();

        $userGroup = $userService->loadUserGroup($mainGroupId);

        // Load the existing "Anonymous" role
        $role = $roleService->loadRoleByIdentifier('Anonymous');

        // Assign the "Anonymous" role to the newly created user group
        try {
            $roleService->assignRoleToUserGroup(
                $role,
                $userGroup,
                new SubtreeLimitation(
                    [
                        'limitationValues' => ['/1/43/', '/1/2/'],
                    ]
                )
            );
        } catch (Exception $e) {
            self::fail('Got exception at first valid attempt to assign role');
        }

        // Re-Assign the "Anonymous" role to the newly created user group
        // This call will fail with an InvalidArgumentException, because limitation is already assigned
        $roleService->assignRoleToUserGroup(
            $role,
            $userGroup,
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/1/43/'],
                ]
            )
        );
        /* END: Use Case */
    }

    /**
     * Test for the removeRoleAssignment() method.
     */
    #[Depends('testAssignRoleToUserGroup')]
    public function testRemoveRoleAssignmentFromUserGroup(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();

        // Load the existing "Member" role
        $role = $roleService->loadRoleByIdentifier('Member');

        // Assign the "Member" role to the newly created user group
        $roleService->assignRoleToUserGroup($role, $userGroup);

        // Unassign group from role
        $roleAssignments = $roleService->getRoleAssignmentsForUserGroup($userGroup);

        // This call will fail with an "UnauthorizedException"
        foreach ($roleAssignments as $roleAssignment) {
            if ($roleAssignment->role->id === $role->id) {
                $roleService->removeRoleAssignment($roleAssignment);
            }
        }
        // The assignments array will not contain the new role<->group assignment
        $roleAssignments = $roleService->getRoleAssignments($role);
        /* END: Use Case */

        // Members + Editors + Partners
        self::assertCount(3, $roleAssignments);
    }

    /**
     * Test unassigning role by assignment.
     */
    public function testUnassignRoleByAssignment(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        $role = $roleService->loadRole(2);
        $user = $repository->getUserService()->loadUser(14);

        $originalAssignmentCount = count($roleService->getRoleAssignmentsForUser($user));

        $roleService->assignRoleToUser($role, $user);
        $newAssignmentCount = count($roleService->getRoleAssignmentsForUser($user));
        self::assertEquals($originalAssignmentCount + 1, $newAssignmentCount);

        $assignments = iterator_to_array($roleService->getRoleAssignmentsForUser($user));
        $roleService->removeRoleAssignment($assignments[0]);
        $finalAssignmentCount = count($roleService->getRoleAssignmentsForUser($user));
        self::assertEquals($newAssignmentCount - 1, $finalAssignmentCount);
    }

    /**
     * Test unassigning role by assignment.
     *
     * But on current admin user so he lacks access to read roles.
     */
    public function testUnassignRoleByAssignmentThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        try {
            $adminUserGroup = $repository->getUserService()->loadUserGroup(12);
            $assignments = iterator_to_array($roleService->getRoleAssignmentsForUserGroup($adminUserGroup));
            $roleService->removeRoleAssignment($assignments[0]);
        } catch (Exception $e) {
            self::fail(
                'Unexpected exception: ' . $e->getMessage() . " \n[" . $e->getFile() . ' (' . $e->getLine() . ')]'
            );
        }

        $roleService->removeRoleAssignment($assignments[0]);
    }

    /**
     * Test unassigning role by non-existing assignment.
     */
    public function testUnassignRoleByAssignmentThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        try {
            $editorsUserGroup = $repository->getUserService()->loadUserGroup(13);
            $assignments = iterator_to_array($roleService->getRoleAssignmentsForUserGroup($editorsUserGroup));
            $roleService->removeRoleAssignment($assignments[0]);
        } catch (Exception $e) {
            self::fail(
                'Unexpected exception: ' . $e->getMessage() . " \n[" . $e->getFile() . ' (' . $e->getLine() . ')]'
            );
        }

        $roleService->removeRoleAssignment($assignments[0]);
    }

    /**
     * Test for the getRoleAssignmentsForUserGroup() method.
     */
    #[Depends('testAssignRoleToUserGroup')]
    #[Depends('testCreateRoleWithAddPolicy')]
    public function testGetRoleAssignmentsForUserGroup(): void
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();

        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();

        // Instantiate a role create and add some policies
        $roleCreate = $roleService->newRoleCreateStruct('Example Role');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct('user', 'login')
        );
        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct('content', 'read')
        );
        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct('content', 'edit')
        );

        // Create the new role instance
        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        // Assign role to new user group
        $roleService->assignRoleToUserGroup($role, $userGroup);

        // Load the currently assigned role
        $roleAssignments = $roleService->getRoleAssignmentsForUserGroup($userGroup);
        /* END: Use Case */

        self::assertCount(1, $roleAssignments);
        self::assertInstanceOf(
            UserGroupRoleAssignment::class,
            reset($roleAssignments)
        );
    }

    /**
     * Test for the getRoleAssignmentsForUser() method.
     */
    #[Depends('testAssignRoleToUser')]
    #[Depends('testAssignRoleToUserGroup')]
    public function testLoadPoliciesByUserId(): void
    {
        $repository = $this->getRepository();

        $anonUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonUserId is the ID of the "Anonymous" user.

        $userService = $repository->getUserService();
        $roleService = $repository->getRoleService();

        // Load "Anonymous" user
        $user = $userService->loadUser($anonUserId);

        // Instantiate a role create and add some policies
        $roleCreate = $roleService->newRoleCreateStruct('User Role');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct('notification', 'use')
        );
        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct('user', 'password')
        );
        $roleCreate->addPolicy(
            $roleService->newPolicyCreateStruct('user', 'selfedit')
        );

        // Create the new role instance
        $roleDraft = $roleService->createRole($roleCreate);
        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRole($roleDraft->id);

        // Assign role to anon user
        $roleService->assignRoleToUser($role, $user);
        $roleAssignments = $roleService->getRoleAssignmentsForUser($user, true);

        $policies = [];
        foreach ($roleAssignments as $roleAssignment) {
            $policies[] = $roleAssignment->getRole()->getPolicies();
        }
        $policies = array_merge(...$policies);

        $simplePolicyList = [];
        foreach ($policies as $simplePolicy) {
            $simplePolicyList[] = [$simplePolicy->roleId, $simplePolicy->module, $simplePolicy->function];
        }
        /* END: Use Case */
        array_multisort($simplePolicyList);

        self::assertEquals(
            [
                [1, 'content', 'pdf'],
                [1, 'content', 'read'],
                [1, 'content', 'read'],
                [1, 'rss', 'feed'],
                [1, 'user', 'login'],
                [1, 'user', 'login'],
                [1, 'user', 'login'],
                [1, 'user', 'login'],
                [$role->id, 'notification', 'use'],
                [$role->id, 'user', 'password'],
                [$role->id, 'user', 'selfedit'],
            ],
            $simplePolicyList
        );
    }

    /**
     * Test for the publishRoleDraft() method.
     */
    #[Depends('testCreateRoleDraft')]
    public function testPublishRoleDraft(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);

        $roleDraft = $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $roleService->newPolicyCreateStruct('content', 'delete')
        );
        $roleDraft = $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $roleService->newPolicyCreateStruct('content', 'create')
        );

        $roleService->publishRoleDraft($roleDraft);
        /* END: Use Case */

        self::assertInstanceOf(
            Role::class,
            $roleService->loadRoleByIdentifier($roleCreate->identifier)
        );
    }

    /**
     * Test for the publishRoleDraft() method.
     */
    #[Depends('testCreateRoleDraft')]
    #[Depends('testAddPolicyByRoleDraft')]
    public function testPublishRoleDraftAddPolicies(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $roleService = $repository->getRoleService();
        $roleCreate = $roleService->newRoleCreateStruct('newRole');

        // @todo uncomment when support for multilingual names and descriptions is added EZP-24776
        // $roleCreate->mainLanguageCode = 'eng-GB';

        $roleDraft = $roleService->createRole($roleCreate);

        $roleDraft = $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $roleService->newPolicyCreateStruct('content', 'delete')
        );
        $roleDraft = $roleService->addPolicyByRoleDraft(
            $roleDraft,
            $roleService->newPolicyCreateStruct('content', 'create')
        );

        $roleService->publishRoleDraft($roleDraft);
        $role = $roleService->loadRoleByIdentifier($roleCreate->identifier);
        /* END: Use Case */

        $actual = [];
        foreach ($role->getPolicies() as $policy) {
            $actual[] = [
                'module' => $policy->module,
                'function' => $policy->function,
            ];
        }
        usort(
            $actual,
            static function ($p1, $p2): int {
                return strcasecmp($p1['function'], $p2['function']);
            }
        );

        self::assertEquals(
            [
                [
                    'module' => 'content',
                    'function' => 'create',
                ],
                [
                    'module' => 'content',
                    'function' => 'delete',
                ],
            ],
            $actual
        );
    }

    /**
     * Create a user group fixture in a variable named <b>$userGroup</b>,.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup
     */
    private function createUserGroupVersion1()
    {
        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Inline */
        // $mainGroupId is the ID of the main "Users" group

        $roleService = $repository->getRoleService();
        $userService = $repository->getUserService();

        // Load main group
        $parentUserGroup = $userService->loadUserGroup($mainGroupId);

        // Instantiate a new create struct
        $userGroupCreate = $userService->newUserGroupCreateStruct('eng-GB');
        $userGroupCreate->setField('name', 'Example Group');

        // Create the new user group
        $userGroup = $userService->createUserGroup(
            $userGroupCreate,
            $parentUserGroup
        );
        /* END: Inline */

        return $userGroup;
    }

    private function loadRoleAssignmentForUser(RoleService $roleService, Role $role, User $newUser): UserRoleAssignment
    {
        [$userRoleAssignment] = array_values(
            array_filter(
                (array)$roleService->getRoleAssignments($role),
                static function (RoleAssignment $roleAssignment) use ($newUser): bool {
                    return $roleAssignment instanceof UserRoleAssignment
                        && $roleAssignment->getUser()->login === $newUser->login;
                }
            )
        );

        return $userRoleAssignment;
    }
}

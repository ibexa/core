<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Ibexa\Contracts\Core\Limitation\Type;
use Ibexa\Contracts\Core\Persistence\User\Policy;
use Ibexa\Contracts\Core\Persistence\User\Role;
use Ibexa\Contracts\Core\Persistence\User\RoleAssignment;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\Base\Exceptions\NotFound\LimitationNotFoundException;
use Ibexa\Core\Repository\Permission\PermissionResolver;
use Ibexa\Core\Repository\Repository as CoreRepository;
use Ibexa\Core\Repository\Values\User\UserReference;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Mock test case for PermissionResolver.
 *
 * @todo Move to "Tests/Permission/"
 */
class PermissionTest extends BaseServiceMockTest
{
    /**
     * @return array<mixed>
     */
    public static function providerForTestHasAccessReturnsTrue(): array
    {
        return [
            [
                [
                    25 => self::createRole(
                        [
                            ['dummy-module', 'dummy-function', 'dummy-limitation'],
                            ['dummy-module2', 'dummy-function2', 'dummy-limitation2'],
                        ],
                        25
                    ),
                    26 => self::createRole(
                        [
                            ['*', 'dummy-function', 'dummy-limitation'],
                        ],
                        26
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 25,
                        ]
                    ),
                    new RoleAssignment(
                        [
                            'roleId' => 26,
                        ]
                    ),
                ],
            ],
            [
                [
                    27 => self::createRole(
                        [
                            ['dummy-module', '*', 'dummy-limitation'],
                        ],
                        27
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 27,
                        ]
                    ),
                ],
            ],
            [
                [
                    28 => self::createRole(
                        [
                            ['dummy-module', 'dummy-function', '*'],
                        ],
                        28
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 28,
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the hasAccess() method.
     */
    #[DataProvider('providerForTestHasAccessReturnsTrue')]
    public function testHasAccessReturnsTrue(array $roles, array $roleAssignments): void
    {
        /** @var $userHandlerMock \PHPUnit\Framework\MockObject\MockObject */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $mockedService = $this->getPermissionResolverMock(null);

        $userHandlerMock
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::equalTo(10), self::equalTo(true))
            ->will(self::returnValue($roleAssignments));

        $this->mockLoadRoleSequence($userHandlerMock, $roleAssignments, $roles);

        $result = $mockedService->hasAccess('dummy-module', 'dummy-function');

        self::assertTrue($result);
    }

    /**
     * @return array<mixed>
     */
    public static function providerForTestHasAccessReturnsFalse(): array
    {
        return [
            [[], []],
            [
                [
                    29 => self::createRole(
                        [
                            ['dummy-module', 'dummy-function', 'dummy-limitation'],
                        ],
                        29
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 29,
                        ]
                    ),
                ],
            ],
            [
                [
                    30 => self::createRole(
                        [
                            ['dummy-module', '*', 'dummy-limitation'],
                        ],
                        30
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 30,
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the hasAccess() method.
     */
    #[DataProvider('providerForTestHasAccessReturnsFalse')]
    public function testHasAccessReturnsFalse(array $roles, array $roleAssignments): void
    {
        /** @var $userHandlerMock \PHPUnit\Framework\MockObject\MockObject */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $service = $this->getPermissionResolverMock(null);

        $userHandlerMock
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::equalTo(10), self::equalTo(true))
            ->will(self::returnValue($roleAssignments));

        $this->mockLoadRoleSequence($userHandlerMock, $roleAssignments, $roles);

        $result = $service->hasAccess('dummy-module2', 'dummy-function2');

        self::assertFalse($result);
    }

    /**
     * Test for the sudo() & hasAccess() method.
     */
    public function testHasAccessReturnsFalseButSudoSoTrue(): void
    {
        /** @var $userHandlerMock \PHPUnit\Framework\MockObject\MockObject */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $service = $this->getPermissionResolverMock(null);
        $repositoryMock = $this->getRepositoryMock();
        $repositoryMock
            ->expects(self::any())
            ->method('getPermissionResolver')
            ->will(self::returnValue($service));

        $userHandlerMock
            ->expects(self::never())
            ->method(self::anything());

        $result = $service->sudo(
            static function (Repository $repo) {
                return $repo->getPermissionResolver()->hasAccess('dummy-module', 'dummy-function');
            },
            $repositoryMock
        );

        self::assertTrue($result);
    }

    /**
     * @return array
     */
    public static function providerForTestHasAccessReturnsPermissionSets(): array
    {
        return [
            [
                [
                    31 => self::createRole(
                        [
                            ['dummy-module', 'dummy-function', 'test-limitation'],
                        ],
                        31
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 31,
                        ]
                    ),
                ],
            ],
            [
                [
                    31 => self::createRole(
                        [
                            ['dummy-module', 'dummy-function', 'test-limitation'],
                        ],
                        31
                    ),
                    32 => self::createRole(
                        [
                            ['dummy-module', 'dummy-function', 'test-limitation2'],
                        ],
                        32
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 31,
                        ]
                    ),
                    new RoleAssignment(
                        [
                            'roleId' => 32,
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the hasAccess() method.
     */
    #[DataProvider('providerForTestHasAccessReturnsPermissionSets')]
    public function testHasAccessReturnsPermissionSets(array $roles, array $roleAssignments): void
    {
        /** @var $userHandlerMock \PHPUnit\Framework\MockObject\MockObject */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $roleDomainMapper = $this->getRoleDomainMapperMock(['buildDomainPolicyObject']);
        $permissionResolverMock = $this->getPermissionResolverMock(['getCurrentUserReference']);

        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->will(self::returnValue(new UserReference(14)));

        $userHandlerMock
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::isInt(), self::equalTo(true))
            ->will(self::returnValue($roleAssignments));

        $this->mockLoadRoleSequence($userHandlerMock, $roleAssignments, $roles);

        $permissionSets = [];
        $expectedBuildDomainPolicyObjectCalls = [];
        /* @var $roleAssignments \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[] */
        foreach ($roleAssignments as $i => $roleAssignment) {
            $permissionSet = ['limitation' => null];
            foreach ($roles[$roleAssignment->roleId]->policies as $k => $policy) {
                $policyName = 'policy-' . $i . '-' . $k;
                $permissionSet['policies'][] = $policyName;

                $expectedBuildDomainPolicyObjectCalls[] = [$policy, $policyName];
            }

            if (!empty($permissionSet['policies'])) {
                $permissionSets[] = $permissionSet;
            }
        }

        if ($expectedBuildDomainPolicyObjectCalls !== []) {
            $buildDomainPolicyObjectMatcher = self::exactly(count($expectedBuildDomainPolicyObjectCalls));
            $roleDomainMapper
                ->expects($buildDomainPolicyObjectMatcher)
                ->method('buildDomainPolicyObject')
                ->willReturnCallback(static function ($policy) use ($buildDomainPolicyObjectMatcher, $expectedBuildDomainPolicyObjectCalls) {
                    [$expectedPolicy, $policyName] = $expectedBuildDomainPolicyObjectCalls[$buildDomainPolicyObjectMatcher->numberOfInvocations() - 1];
                    self::assertSame($expectedPolicy, $policy);

                    return $policyName;
                });
        }

        /* @var $repositoryMock \Ibexa\Core\Repository\Repository */
        self::assertEquals(
            $permissionSets,
            $permissionResolverMock->hasAccess('dummy-module', 'dummy-function')
        );
    }

    /**
     * @return array
     */
    public static function providerForTestHasAccessReturnsLimitationNotFoundException(): array
    {
        return [
            [
                [
                    31 => self::createRole(
                        [
                            ['dummy-module', 'dummy-function', 'notfound'],
                        ],
                        31
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 31,
                        ]
                    ),
                ],
            ],
            [
                [
                    31 => self::createRole(
                        [
                            ['dummy-module', 'dummy-function', 'test-limitation'],
                        ],
                        31
                    ),
                    32 => self::createRole(
                        [
                            ['dummy-module', 'dummy-function', 'notfound'],
                        ],
                        32
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 31,
                        ]
                    ),
                    new RoleAssignment(
                        [
                            'roleId' => 32,
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the hasAccess() method.
     */
    #[DataProvider('providerForTestHasAccessReturnsLimitationNotFoundException')]
    public function testHasAccessReturnsLimitationNotFoundException(array $roles, array $roleAssignments): void
    {
        $this->expectException(LimitationNotFoundException::class);

        /** @var $userHandlerMock \PHPUnit\Framework\MockObject\MockObject */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $roleDomainMapper = $this->getRoleDomainMapperMock();
        $permissionResolverMock = $this->getPermissionResolverMock(['getCurrentUserReference']);

        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->will(self::returnValue(new UserReference(14)));

        $userHandlerMock
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::isInt(), self::equalTo(true))
            ->will(self::returnValue($roleAssignments));

        $this->mockLoadRoleSequence($userHandlerMock, $roleAssignments, $roles);

        $expectedBuildDomainPolicyObjectCalls = [];
        /* @var $roleAssignments \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[] */
        foreach ($roleAssignments as $i => $roleAssignment) {
            $permissionSet = ['limitation' => null];
            foreach ($roles[$roleAssignment->roleId]->policies as $k => $policy) {
                $policyName = 'policy-' . $i . '-' . $k;
                if ($policy->limitations === 'notfound') {
                    $expectedBuildDomainPolicyObjectCalls[] = [$policy, null];
                } else {
                    $permissionSet['policies'][] = $policyName;
                    $expectedBuildDomainPolicyObjectCalls[] = [$policy, $policyName];
                }

                if ($policy->limitations === 'notfound') {
                    break 2; // no more execution after exception
                }
            }
        }

        $buildDomainPolicyObjectMatcher = self::exactly(count($expectedBuildDomainPolicyObjectCalls));
        $roleDomainMapper
            ->expects($buildDomainPolicyObjectMatcher)
            ->method('buildDomainPolicyObject')
            ->willReturnCallback(static function ($policy) use ($buildDomainPolicyObjectMatcher, $expectedBuildDomainPolicyObjectCalls) {
                [$expectedPolicy, $policyName] = $expectedBuildDomainPolicyObjectCalls[$buildDomainPolicyObjectMatcher->numberOfInvocations() - 1];
                self::assertSame($expectedPolicy, $policy);

                if ($policyName === null) {
                    throw new LimitationNotFoundException('notfound');
                }

                return $policyName;
            });

        $permissionResolverMock->hasAccess('dummy-module', 'dummy-function');
    }

    /**
     * @return array
     */
    public static function providerForTestHasAccessReturnsInvalidArgumentValueException(): array
    {
        return [
            [
                [
                    31 => self::createRole(
                        [
                            ['test-module', 'test-function', '*'],
                        ],
                        31
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 31,
                        ]
                    ),
                ],
            ],
            [
                [
                    31 => self::createRole(
                        [
                            ['other-module', 'test-function', '*'],
                        ],
                        31
                    ),
                    32 => self::createRole(
                        [
                            ['test-module', 'other-function', '*'],
                        ],
                        32
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 31,
                        ]
                    ),
                    new RoleAssignment(
                        [
                            'roleId' => 32,
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the hasAccess() method.
     */
    #[DataProvider('providerForTestHasAccessReturnsInvalidArgumentValueException')]
    public function testHasAccessReturnsInvalidArgumentValueException(array $roles, array $roleAssignments): void
    {
        $this->expectException(InvalidArgumentValue::class);

        $permissionResolverMock = $this->getPermissionResolverMock(['getCurrentUserReference']);

        /** @var $role \Ibexa\Contracts\Core\Persistence\User\Role */
        foreach ($roles as $role) {
            /** @var $policy \Ibexa\Contracts\Core\Persistence\User\Policy */
            foreach ($role->policies as $policy) {
                $permissionResolverMock->hasAccess($policy->module, $policy->function);
            }
        }
    }

    /**
     * @return array<mixed>
     */
    public static function providerForTestHasAccessReturnsPermissionSetsWithRoleLimitation(): array
    {
        return [
            [
                [
                    32 => self::createRole(
                        [
                            [
                                'dummy-module', 'dummy-function', [
                                'Subtree' => [
                                    '/1/2/',
                                ],
                            ],
                            ],
                        ],
                        32
                    ),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 32,
                            'limitationIdentifier' => 'Subtree',
                            'values' => ['/1/2/'],
                        ]
                    ),
                ],
            ],
            [
                [
                    33 => self::createRole([['*', '*', '*']], 33),
                ],
                [
                    new RoleAssignment(
                        [
                            'roleId' => 33,
                            'limitationIdentifier' => 'Subtree',
                            'values' => ['/1/2/'],
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the hasAccess() method.
     */
    #[DataProvider('providerForTestHasAccessReturnsPermissionSetsWithRoleLimitation')]
    public function testHasAccessReturnsPermissionSetsWithRoleLimitation(array $roles, array $roleAssignments): void
    {
        /** @var $userHandlerMock \PHPUnit\Framework\MockObject\MockObject */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $limitationTypeMock = $this->createMock(Type::class);
        $limitationService = $this->getLimitationServiceMock();
        $roleDomainMapper = $this->getRoleDomainMapperMock();
        $permissionResolverMock = $this->getPermissionResolverMock(['getCurrentUserReference']);

        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->will(self::returnValue(new UserReference(14)));

        $userHandlerMock
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::isInt(), self::equalTo(true))
            ->will(self::returnValue($roleAssignments));

        $this->mockLoadRoleSequence($userHandlerMock, $roleAssignments, $roles);

        $permissionSets = [];
        $expectedBuildDomainPolicyObjectCalls = [];
        $expectedBuildValueCalls = [];
        /** @var $roleAssignments \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[] */
        foreach ($roleAssignments as $i => $roleAssignment) {
            $permissionSet = [];
            foreach ($roles[$roleAssignment->roleId]->policies as $k => $policy) {
                $policyName = "policy-{$i}-{$k}";
                $permissionSet['policies'][] = $policyName;
                $expectedBuildDomainPolicyObjectCalls[] = [$policy, $policyName];
            }

            $limitation = $this->createMock(Limitation::class);
            $limitation->method('getIdentifier')->willReturn("limitation-{$i}");

            $permissionSet['limitation'] = $limitation;
            $expectedBuildValueCalls[] = [$roleAssignment->values, $permissionSet['limitation']];
            $limitationService
                ->expects(self::any())
                ->method('getLimitationType')
                ->with($roleAssignment->limitationIdentifier)
                ->will(self::returnValue($limitationTypeMock));

            $permissionSets[] = $permissionSet;
        }

        if ($expectedBuildDomainPolicyObjectCalls !== []) {
            $buildDomainPolicyObjectMatcher = self::exactly(count($expectedBuildDomainPolicyObjectCalls));
            $roleDomainMapper
                ->expects($buildDomainPolicyObjectMatcher)
                ->method('buildDomainPolicyObject')
                ->willReturnCallback(static function ($policy) use ($buildDomainPolicyObjectMatcher, $expectedBuildDomainPolicyObjectCalls) {
                    [$expectedPolicy, $policyName] = $expectedBuildDomainPolicyObjectCalls[$buildDomainPolicyObjectMatcher->numberOfInvocations() - 1];
                    self::assertSame($expectedPolicy, $policy);

                    return $policyName;
                });
        }

        if ($expectedBuildValueCalls !== []) {
            $buildValueMatcher = self::exactly(count($expectedBuildValueCalls));
            $limitationTypeMock
                ->expects($buildValueMatcher)
                ->method('buildValue')
                ->willReturnCallback(static function ($values) use ($buildValueMatcher, $expectedBuildValueCalls) {
                    [$expectedValues, $limitation] = $expectedBuildValueCalls[$buildValueMatcher->numberOfInvocations() - 1];
                    self::assertSame($expectedValues, $values);

                    return $limitation;
                });
        }

        self::assertEquals(
            $permissionSets,
            $permissionResolverMock->hasAccess('dummy-module', 'dummy-function')
        );
    }

    /**
     * Sets up the expectation that $userHandlerMock->loadRole() is called once per
     * $roleAssignment, in order, each returning the matching entry from $roles.
     *
     * @param \PHPUnit\Framework\MockObject\MockObject $userHandlerMock
     * @param \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[] $roleAssignments
     * @param \Ibexa\Contracts\Core\Persistence\User\Role[] $roles
     */
    private function mockLoadRoleSequence($userHandlerMock, array $roleAssignments, array $roles): void
    {
        if ($roleAssignments === []) {
            $userHandlerMock->expects(self::never())->method('loadRole');

            return;
        }

        $roleAssignmentList = array_values($roleAssignments);
        $matcher = self::exactly(count($roleAssignmentList));
        $userHandlerMock
            ->expects($matcher)
            ->method('loadRole')
            ->willReturnCallback(static function ($roleId, $status = Role::STATUS_DEFINED) use ($matcher, $roleAssignmentList, $roles) {
                $roleAssignment = $roleAssignmentList[$matcher->numberOfInvocations() - 1];
                self::assertSame($roleAssignment->roleId, $roleId);

                return $roles[$roleAssignment->roleId];
            });
    }

    /**
     * Returns Role stub.
     *
     * @param array $policiesData
     * @param mixed $roleId
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    private static function createRole(array $policiesData, $roleId = null)
    {
        $policies = [];
        foreach ($policiesData as $policyData) {
            $policies[] = new Policy(
                [
                    'module' => $policyData[0],
                    'function' => $policyData[1],
                    'limitations' => $policyData[2],
                ]
            );
        }

        return new Role(
            [
                'id' => $roleId,
                'policies' => $policies,
            ]
        );
    }

    /**
     * @return array<mixed>
     */
    public static function providerForTestCanUserSimple(): array
    {
        return [
            [true, true],
            [false, false],
            [[], false],
        ];
    }

    /**
     * Test for the canUser() method.
     *
     * Tests execution paths with permission sets equaling to boolean value or empty array.
     */
    #[DataProvider('providerForTestCanUserSimple')]
    public function testCanUserSimple($permissionSets, $result): void
    {
        $permissionResolverMock = $this->getPermissionResolverMock(['hasAccess']);

        $permissionResolverMock
            ->expects(self::once())
            ->method('hasAccess')
            ->with(self::equalTo('test-module'), self::equalTo('test-function'))
            ->will(self::returnValue($permissionSets));

        /** @var $valueObject \Ibexa\Contracts\Core\Repository\Values\ValueObject */
        $valueObject = $this->getMockForAbstractClass(ValueObject::class);

        self::assertEquals(
            $result,
            $permissionResolverMock->canUser('test-module', 'test-function', $valueObject, [$valueObject])
        );
    }

    /**
     * Test for the canUser() method.
     *
     * Tests execution path with permission set defining no limitations.
     */
    public function testCanUserWithoutLimitations(): void
    {
        $permissionResolverMock = $this->getPermissionResolverMock(
            [
                'hasAccess',
                'getCurrentUserReference',
            ]
        );

        $policyMock = $this->getMockBuilder(Policy::class)
            ->setConstructorArgs([])
            ->disableOriginalConstructor()
            ->addMethods(['getLimitations'])
            ->getMock();

        $policyMock
            ->expects(self::once())
            ->method('getLimitations')
            ->will(self::returnValue('*'));
        $permissionSets = [
            [
                'limitation' => null,
                'policies' => [$policyMock],
            ],
        ];
        $permissionResolverMock
            ->expects(self::once())
            ->method('hasAccess')
            ->with(self::equalTo('test-module'), self::equalTo('test-function'))
            ->will(self::returnValue($permissionSets));

        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->will(self::returnValue(new UserReference(14)));

        /** @var $valueObject \Ibexa\Contracts\Core\Repository\Values\ValueObject */
        $valueObject = $this->getMockForAbstractClass(ValueObject::class);

        self::assertTrue(
            $permissionResolverMock->canUser(
                'test-module',
                'test-function',
                $valueObject,
                [$valueObject]
            )
        );
    }

    /**
     * @return array
     */
    private function getPermissionSetsMock()
    {
        $roleLimitationMock = $this->createMock(Limitation::class);
        $roleLimitationMock
            ->expects(self::any())
            ->method('getIdentifier')
            ->will(self::returnValue('test-role-limitation-identifier'));

        $policyLimitationMock = $this->createMock(Limitation::class);
        $policyLimitationMock
            ->expects(self::any())
            ->method('getIdentifier')
            ->will(self::returnValue('test-policy-limitation-identifier'));

        $policyMock = $this->getMockBuilder(Policy::class)
            ->setConstructorArgs([])
            ->addMethods(['getLimitations'])
            ->getMock();

        $policyMock
            ->expects(self::any())
            ->method('getLimitations')
            ->will(self::returnValue([$policyLimitationMock, $policyLimitationMock]));

        $permissionSet = [
            'limitation' => clone $roleLimitationMock,
            'policies' => [$policyMock, $policyMock],
        ];
        $permissionSets = [$permissionSet, $permissionSet];

        return $permissionSets;
    }

    /**
     * Provides evaluation results for two permission sets, each with a role limitation and two policies,
     * with two limitations per policy.
     *
     * @return array
     */
    public static function providerForTestCanUserComplex(): array
    {
        return [
            [
                [true, true],
                [
                    [
                        [true, true],
                        [true, true],
                    ],
                    [
                        [true, true],
                        [true, true],
                    ],
                ],
                true,
            ],
            [
                [false, false],
                [
                    [
                        [true, true],
                        [true, true],
                    ],
                    [
                        [true, true],
                        [true, true],
                    ],
                ],
                false,
            ],
            [
                [false, true],
                [
                    [
                        [true, true],
                        [true, true],
                    ],
                    [
                        [true, true],
                        [true, true],
                    ],
                ],
                true,
            ],
            [
                [false, true],
                [
                    [
                        [true, true],
                        [true, true],
                    ],
                    [
                        [true, false],
                        [true, true],
                    ],
                ],
                true,
            ],
            [
                [true, false],
                [
                    [
                        [true, false],
                        [false, true],
                    ],
                    [
                        [true, true],
                        [true, true],
                    ],
                ],
                false,
            ],
        ];
    }

    /**
     * Test for the canUser() method.
     *
     * Tests execution paths with permission sets containing limitations.
     */
    #[DataProvider('providerForTestCanUserComplex')]
    public function testCanUserComplex(array $roleLimitationEvaluations, array $policyLimitationEvaluations, $userCan): void
    {
        /** @var $valueObject \Ibexa\Contracts\Core\Repository\Values\ValueObject */
        $valueObject = self::createStub(ValueObject::class);
        $limitationServiceMock = $this->getLimitationServiceMock();
        $permissionResolverMock = $this->getPermissionResolverMock(
            [
                'hasAccess',
                'getCurrentUserReference',
            ]
        );

        $permissionSets = $this->getPermissionSetsMock();
        $permissionResolverMock
            ->expects(self::once())
            ->method('hasAccess')
            ->with(self::equalTo('test-module'), self::equalTo('test-function'))
            ->will(self::returnValue($permissionSets));

        $userRef = new UserReference(14);
        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->will(self::returnValue(new UserReference(14)));

        $expectedGetLimitationTypeCalls = [];
        for ($i = 0; $i < count($permissionSets); ++$i) {
            $limitation = $this->createMock(Type::class);
            $limitation
                ->expects(self::once())
                ->method('evaluate')
                ->with($permissionSets[$i]['limitation'], $userRef, $valueObject, [$valueObject])
                ->will(self::returnValue($roleLimitationEvaluations[$i]));
            $expectedGetLimitationTypeCalls[] = ['test-role-limitation-identifier', $limitation];

            if (!$roleLimitationEvaluations[$i]) {
                continue;
            }

            for ($j = 0; $j < count($permissionSets[$i]['policies']); ++$j) {
                /** @var $policy \Ibexa\Contracts\Core\Repository\Values\User\Policy */
                $policy = $permissionSets[$i]['policies'][$j];
                $limitations = $policy->getLimitations();
                for ($k = 0; $k < count($limitations); ++$k) {
                    $limitationsPass = true;
                    $limitation = $this->createMock(Type::class);
                    $limitation
                        ->expects(self::once())
                        ->method('evaluate')
                        ->with($limitations[$k], $userRef, $valueObject, [$valueObject])
                        ->will(self::returnValue($policyLimitationEvaluations[$i][$j][$k]));
                    $expectedGetLimitationTypeCalls[] = ['test-policy-limitation-identifier', $limitation];

                    if (!$policyLimitationEvaluations[$i][$j][$k]) {
                        $limitationsPass = false;
                        break;
                    }
                }

                /** @var $limitationsPass */
                if ($limitationsPass) {
                    break 2;
                }
            }
        }

        $getLimitationTypeMatcher = self::exactly(count($expectedGetLimitationTypeCalls));
        $limitationServiceMock
            ->expects($getLimitationTypeMatcher)
            ->method('getLimitationType')
            ->willReturnCallback(static function ($identifier) use ($getLimitationTypeMatcher, $expectedGetLimitationTypeCalls) {
                [$expectedIdentifier, $limitation] = $expectedGetLimitationTypeCalls[$getLimitationTypeMatcher->numberOfInvocations() - 1];
                self::assertSame($expectedIdentifier, $identifier);

                return $limitation;
            });

        self::assertEquals(
            $userCan,
            $permissionResolverMock->canUser(
                'test-module',
                'test-function',
                $valueObject,
                [$valueObject]
            )
        );
    }

    /**
     * Test for the setCurrentUserReference() and getCurrentUserReference() methods.
     */
    public function testSetAndGetCurrentUserReference(): void
    {
        $permissionResolverMock = $this->getPermissionResolverMock(null);
        $userReferenceMock = $this->getUserReferenceMock();

        $userReferenceMock
            ->expects(self::once())
            ->method('getUserId')
            ->will(self::returnValue(42));

        $permissionResolverMock->setCurrentUserReference($userReferenceMock);

        self::assertSame(
            $userReferenceMock,
            $permissionResolverMock->getCurrentUserReference()
        );
    }

    /**
     * Test for the getCurrentUserReference() method.
     */
    public function testGetCurrentUserReferenceReturnsAnonymousUser(): void
    {
        $permissionResolverMock = $this->getPermissionResolverMock(null);

        self::assertEquals(new UserReference(10), $permissionResolverMock->getCurrentUserReference());
    }

    protected $permissionResolverMock;

    /**
     * @return \Ibexa\Contracts\Core\Repository\PermissionResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPermissionResolverMock($methods = [])
    {
        if ($this->permissionResolverMock === null) {
            $configResolverMock = $this->createMock(ConfigResolverInterface::class);
            $configResolverMock
                ->method('getParameter')
                ->with('anonymous_user_id')
                ->willReturn(10);

            $builder = $this
                ->getMockBuilder(PermissionResolver::class);
            if ($methods === null) {
                $builder->onlyMethods([]);
            } elseif ($methods !== []) {
                $builder->onlyMethods($methods);
            }
            $this->permissionResolverMock = $builder
                ->setConstructorArgs(
                    [
                        $this->getRoleDomainMapperMock(),
                        $this->getLimitationServiceMock(),
                        $this->getPersistenceMock()->userHandler(),
                        $configResolverMock,
                        [
                            'dummy-module' => [
                                'dummy-function' => [
                                    'dummy-limitation' => true,
                                ],
                                'dummy-function2' => [
                                    'dummy-limitation' => true,
                                ],
                            ],
                            'dummy-module2' => [
                                'dummy-function' => [
                                    'dummy-limitation' => true,
                                ],
                                'dummy-function2' => [
                                    'dummy-limitation' => true,
                                ],
                            ],
                        ],
                    ]
                )
                ->getMock();
        }

        return $this->permissionResolverMock;
    }

    protected $userReferenceMock;

    protected function getUserReferenceMock()
    {
        if ($this->userReferenceMock === null) {
            $this->userReferenceMock = $this->createMock(UserReference::class);
        }

        return $this->userReferenceMock;
    }

    protected $repositoryMock;

    /**
     * @return \Ibexa\Contracts\Core\Repository\Repository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getRepositoryMock(): Repository
    {
        if ($this->repositoryMock === null) {
            $this->repositoryMock = $this
                ->getMockBuilder(CoreRepository::class)
                ->onlyMethods(['getPermissionResolver'])
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->repositoryMock;
    }
}

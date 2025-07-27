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

/**
 * Mock test case for PermissionResolver.
 *
 * @todo Move to "Tests/Permission/"
 */
class PermissionTest extends BaseServiceMockTest
{
    public function providerForTestHasAccessReturnsTrue()
    {
        return [
            [
                [
                    25 => $this->createRole(
                        [
                            ['dummy-module', 'dummy-function', 'dummy-limitation'],
                            ['dummy-module2', 'dummy-function2', 'dummy-limitation2'],
                        ],
                        25
                    ),
                    26 => $this->createRole(
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
                    27 => $this->createRole(
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
                    28 => $this->createRole(
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
     *
     * @dataProvider providerForTestHasAccessReturnsTrue
     */
    public function testHasAccessReturnsTrue(array $roles, array $roleAssignments)
    {
        /** @var $userHandlerMock \PHPUnit\Framework\MockObject\MockObject */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $mockedService = $this->getPermissionResolverMock(null);

        $userHandlerMock
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::equalTo(10), self::equalTo(true))
            ->will(self::returnValue($roleAssignments));

        foreach ($roleAssignments as $at => $roleAssignment) {
            $userHandlerMock
                ->expects(self::at($at + 1))
                ->method('loadRole')
                ->with($roleAssignment->roleId)
                ->will(self::returnValue($roles[$roleAssignment->roleId]));
        }

        $result = $mockedService->hasAccess('dummy-module', 'dummy-function');

        self::assertTrue($result);
    }

    public function providerForTestHasAccessReturnsFalse()
    {
        return [
            [[], []],
            [
                [
                    29 => $this->createRole(
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
                    30 => $this->createRole(
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
     *
     * @dataProvider providerForTestHasAccessReturnsFalse
     */
    public function testHasAccessReturnsFalse(array $roles, array $roleAssignments)
    {
        /** @var $userHandlerMock \PHPUnit\Framework\MockObject\MockObject */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $service = $this->getPermissionResolverMock(null);

        $userHandlerMock
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::equalTo(10), self::equalTo(true))
            ->will(self::returnValue($roleAssignments));

        foreach ($roleAssignments as $at => $roleAssignment) {
            $userHandlerMock
                ->expects(self::at($at + 1))
                ->method('loadRole')
                ->with($roleAssignment->roleId)
                ->will(self::returnValue($roles[$roleAssignment->roleId]));
        }

        $result = $service->hasAccess('dummy-module2', 'dummy-function2');

        self::assertFalse($result);
    }

    /**
     * Test for the sudo() & hasAccess() method.
     */
    public function testHasAccessReturnsFalseButSudoSoTrue()
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
    public function providerForTestHasAccessReturnsPermissionSets()
    {
        return [
            [
                [
                    31 => $this->createRole(
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
                    31 => $this->createRole(
                        [
                            ['dummy-module', 'dummy-function', 'test-limitation'],
                        ],
                        31
                    ),
                    32 => $this->createRole(
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
     *
     * @dataProvider providerForTestHasAccessReturnsPermissionSets
     */
    public function testHasAccessReturnsPermissionSets(array $roles, array $roleAssignments)
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
            ->with(self::isType('integer'), self::equalTo(true))
            ->will(self::returnValue($roleAssignments));

        foreach ($roleAssignments as $at => $roleAssignment) {
            $userHandlerMock
                ->expects(self::at($at + 1))
                ->method('loadRole')
                ->with($roleAssignment->roleId)
                ->will(self::returnValue($roles[$roleAssignment->roleId]));
        }

        $permissionSets = [];
        $count = 0;
        /* @var $roleAssignments \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[] */
        foreach ($roleAssignments as $i => $roleAssignment) {
            $permissionSet = ['limitation' => null];
            foreach ($roles[$roleAssignment->roleId]->policies as $k => $policy) {
                $policyName = 'policy-' . $i . '-' . $k;
                $return = self::returnValue($policyName);
                $permissionSet['policies'][] = $policyName;

                $roleDomainMapper
                    ->expects(self::at($count++))
                    ->method('buildDomainPolicyObject')
                    ->with($policy)
                    ->will($return);
            }

            if (!empty($permissionSet['policies'])) {
                $permissionSets[] = $permissionSet;
            }
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
    public function providerForTestHasAccessReturnsLimitationNotFoundException()
    {
        return [
            [
                [
                    31 => $this->createRole(
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
                    31 => $this->createRole(
                        [
                            ['dummy-module', 'dummy-function', 'test-limitation'],
                        ],
                        31
                    ),
                    32 => $this->createRole(
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
     *
     * @dataProvider providerForTestHasAccessReturnsLimitationNotFoundException
     */
    public function testHasAccessReturnsLimitationNotFoundException(array $roles, array $roleAssignments)
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
            ->with(self::isType('integer'), self::equalTo(true))
            ->will(self::returnValue($roleAssignments));

        foreach ($roleAssignments as $at => $roleAssignment) {
            $userHandlerMock
                ->expects(self::at($at + 1))
                ->method('loadRole')
                ->with($roleAssignment->roleId)
                ->will(self::returnValue($roles[$roleAssignment->roleId]));
        }

        $count = 0;
        /* @var $roleAssignments \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[] */
        foreach ($roleAssignments as $i => $roleAssignment) {
            $permissionSet = ['limitation' => null];
            foreach ($roles[$roleAssignment->roleId]->policies as $k => $policy) {
                $policyName = 'policy-' . $i . '-' . $k;
                if ($policy->limitations === 'notfound') {
                    $return = self::throwException(new LimitationNotFoundException('notfound'));
                } else {
                    $return = self::returnValue($policyName);
                    $permissionSet['policies'][] = $policyName;
                }

                $roleDomainMapper
                    ->expects(self::at($count++))
                    ->method('buildDomainPolicyObject')
                    ->with($policy)
                    ->will($return);

                if ($policy->limitations === 'notfound') {
                    break 2; // no more execution after exception
                }
            }
        }

        $permissionResolverMock->hasAccess('dummy-module', 'dummy-function');
    }

    /**
     * @return array
     */
    public function providerForTestHasAccessReturnsInvalidArgumentValueException()
    {
        return [
            [
                [
                    31 => $this->createRole(
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
                    31 => $this->createRole(
                        [
                            ['other-module', 'test-function', '*'],
                        ],
                        31
                    ),
                    32 => $this->createRole(
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
     *
     * @dataProvider providerForTestHasAccessReturnsInvalidArgumentValueException
     */
    public function testHasAccessReturnsInvalidArgumentValueException(array $roles, array $roleAssignments)
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

    public function providerForTestHasAccessReturnsPermissionSetsWithRoleLimitation()
    {
        return [
            [
                [
                    32 => $this->createRole(
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
                    33 => $this->createRole([['*', '*', '*']], 33),
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
     *
     * @dataProvider providerForTestHasAccessReturnsPermissionSetsWithRoleLimitation
     */
    public function testHasAccessReturnsPermissionSetsWithRoleLimitation(array $roles, array $roleAssignments)
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
            ->with(self::isType('integer'), self::equalTo(true))
            ->will(self::returnValue($roleAssignments));

        foreach ($roleAssignments as $at => $roleAssignment) {
            $userHandlerMock
                ->expects(self::at($at + 1))
                ->method('loadRole')
                ->with($roleAssignment->roleId)
                ->will(self::returnValue($roles[$roleAssignment->roleId]));
        }

        $permissionSets = [];
        /** @var $roleAssignments \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[] */
        foreach ($roleAssignments as $i => $roleAssignment) {
            $permissionSet = [];
            foreach ($roles[$roleAssignment->roleId]->policies as $k => $policy) {
                $policyName = "policy-{$i}-{$k}";
                $permissionSet['policies'][] = $policyName;
                $roleDomainMapper
                    ->expects(self::at($k))
                    ->method('buildDomainPolicyObject')
                    ->with($policy)
                    ->will(self::returnValue($policyName));
            }

            $limitation = $this->createMock(Limitation::class);
            $limitation->method('getIdentifier')->willReturn("limitation-{$i}");

            $permissionSet['limitation'] = $limitation;
            $limitationTypeMock
                ->expects(self::at($i))
                ->method('buildValue')
                ->with($roleAssignment->values)
                ->will(self::returnValue($permissionSet['limitation']));
            $limitationService
                ->expects(self::any())
                ->method('getLimitationType')
                ->with($roleAssignment->limitationIdentifier)
                ->will(self::returnValue($limitationTypeMock));

            $permissionSets[] = $permissionSet;
        }

        self::assertEquals(
            $permissionSets,
            $permissionResolverMock->hasAccess('dummy-module', 'dummy-function')
        );
    }

    /**
     * Returns Role stub.
     *
     * @param array $policiesData
     * @param mixed $roleId
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    private function createRole(array $policiesData, $roleId = null)
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

    public function providerForTestCanUserSimple()
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
     *
     * @dataProvider providerForTestCanUserSimple
     */
    public function testCanUserSimple($permissionSets, $result)
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
    public function testCanUserWithoutLimitations()
    {
        $permissionResolverMock = $this->getPermissionResolverMock(
            [
                'hasAccess',
                'getCurrentUserReference',
            ]
        );

        $policyMock = $this->getMockBuilder(Policy::class)
            ->setMethods(['getLimitations'])
            ->setConstructorArgs([])
            ->disableOriginalConstructor()
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
            ->setMethods(['getLimitations'])
            ->setConstructorArgs([])
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
    public function providerForTestCanUserComplex()
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
     *
     * @dataProvider providerForTestCanUserComplex
     */
    public function testCanUserComplex(array $roleLimitationEvaluations, array $policyLimitationEvaluations, $userCan)
    {
        /** @var $valueObject \Ibexa\Contracts\Core\Repository\Values\ValueObject */
        $valueObject = $this->createMock(ValueObject::class);
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

        $invocation = 0;
        for ($i = 0; $i < count($permissionSets); ++$i) {
            $limitation = $this->createMock(Type::class);
            $limitation
                ->expects(self::once())
                ->method('evaluate')
                ->with($permissionSets[$i]['limitation'], $userRef, $valueObject, [$valueObject])
                ->will(self::returnValue($roleLimitationEvaluations[$i]));
            $limitationServiceMock
                ->expects(self::at($invocation++))
                ->method('getLimitationType')
                ->with('test-role-limitation-identifier')
                ->will(self::returnValue($limitation));

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
                    $limitationServiceMock
                        ->expects(self::at($invocation++))
                        ->method('getLimitationType')
                        ->with('test-policy-limitation-identifier')
                        ->will(self::returnValue($limitation));

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
    public function testSetAndGetCurrentUserReference()
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
    public function testGetCurrentUserReferenceReturnsAnonymousUser()
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

            $this->permissionResolverMock = $this
                ->getMockBuilder(PermissionResolver::class)
                ->setMethods($methods)
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

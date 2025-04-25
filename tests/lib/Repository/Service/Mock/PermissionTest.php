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
use Ibexa\Contracts\Core\Repository\Values\User\Policy as APIPolicy;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\Base\Exceptions\NotFound\LimitationNotFoundException;
use Ibexa\Core\Repository\Permission\PermissionResolver;
use Ibexa\Core\Repository\Values\User\Policy as CorePolicy;
use Ibexa\Core\Repository\Values\User\UserReference;
use Ibexa\Tests\Core\PHPUnit\InvocationMocker;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Mock test case for PermissionResolver.
 */
final class PermissionTest extends BaseServiceMockTest
{
    protected UserReference & MockObject $userReferenceMock;

    /**
     * @phpstan-return iterable<array{
     *     0: array<int, \Ibexa\Contracts\Core\Persistence\User\Role>,
     *     1: array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment>
     * }>
     */
    public function providerForTestHasAccessReturnsTrue(): iterable
    {
        yield [
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
        ];

        yield [
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
        ];

        yield [
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
        ];
    }

    /**
     * @dataProvider providerForTestHasAccessReturnsTrue
     *
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\Role> $roles
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment> $roleAssignments
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testHasAccessReturnsTrue(array $roles, array $roleAssignments): void
    {
        /** @var \Ibexa\Contracts\Core\Persistence\User\Handler&\PHPUnit\Framework\MockObject\MockObject $userHandlerMock */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $mockedService = $this->getPermissionResolverMock();

        $userHandlerMock
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::equalTo(10), self::equalTo(true))
            ->willReturn($roleAssignments);

        $userHandlerMock
            ->expects(self::exactly(count($roleAssignments)))
            ->method('loadRole')
            ->willReturnCallback(static fn (int $roleId): Role => $roles[$roleId]);

        $result = $mockedService->hasAccess('dummy-module', 'dummy-function');

        self::assertTrue($result);
    }

    /**
     * @phpstan-return iterable<array{
     *     0: array<int, \Ibexa\Contracts\Core\Persistence\User\Role>,
     *     1: array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment>
     * }>
     */
    public function providerForTestHasAccessReturnsFalse(): iterable
    {
        yield [[], []];

        yield [
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
        ];

        yield [
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
        ];
    }

    /**
     * @dataProvider providerForTestHasAccessReturnsFalse
     *
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\Role> $roles
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment> $roleAssignments
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testHasAccessReturnsFalse(array $roles, array $roleAssignments): void
    {
        /** @var \Ibexa\Contracts\Core\Persistence\User\Handler&\PHPUnit\Framework\MockObject\MockObject $userHandlerMock */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $service = $this->getPermissionResolverMock();

        $userHandlerMock
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::equalTo(10), self::equalTo(true))
            ->willReturn($roleAssignments);

        $userHandlerMock
            ->expects(self::exactly(count($roleAssignments)))
            ->method('loadRole')
            ->willReturnCallback(static fn (int $roleId): Role => $roles[$roleId]);

        $result = $service->hasAccess('dummy-module2', 'dummy-function2');

        self::assertFalse($result);
    }

    /**
     * Test for the sudo() & hasAccess() method.
     *
     * @throws \Exception
     */
    public function testHasAccessReturnsFalseButSudoSoTrue(): void
    {
        /** @var \Ibexa\Contracts\Core\Persistence\User\Handler&\PHPUnit\Framework\MockObject\MockObject $userHandlerMock */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $service = $this->getPermissionResolverMock();
        $repositoryMock = $this->getRepositoryMock();
        $repositoryMock
            ->method('getPermissionResolver')
            ->willReturn($service);

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
     * @phpstan-return iterable<
     *     array{
     *      0: array<int, \Ibexa\Contracts\Core\Persistence\User\Role>,
     *      1: array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment>
     * }>
     */
    public function providerForTestHasAccessReturnsPermissionSets(): iterable
    {
        yield 'single role, no limitations' => [
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
        ];

        yield 'multiple roles, no limitations' => [
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
        ];

        yield 'role with subtree limitation' => [
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
        ];

        yield 'role with all permissions and subtree limitation' => [
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
        ];
    }

    /**
     * @dataProvider providerForTestHasAccessReturnsPermissionSets
     *
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\Role> $roles
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment> $roleAssignments
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testHasAccessReturnsPermissionSets(array $roles, array $roleAssignments): void
    {
        $permissionResolverMock = $this->mockPermissionResolverForPermissionSetsTests($roleAssignments, $roles);

        self::assertEquals(
            $this->buildExpectedPermissionSets($roles, $roleAssignments),
            $permissionResolverMock->hasAccess('dummy-module', 'dummy-function')
        );
    }

    /**
     * @phpstan-return iterable<string, array{
     *     0: array<int, \Ibexa\Contracts\Core\Persistence\User\Role>,
     *     1: array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment>
     * }>
     */
    public function providerForTestHasAccessReturnsLimitationNotFoundException(): iterable
    {
        yield 'single role with not found limitation' => [
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
        ];

        yield 'multiple roles with not found limitation' => [
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
        ];
    }

    /**
     * @dataProvider providerForTestHasAccessReturnsLimitationNotFoundException
     *
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\Role> $roles
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment> $roleAssignments
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testHasAccessReturnsLimitationNotFoundException(array $roles, array $roleAssignments): void
    {
        $permissionResolverMock = $this->mockPermissionResolverForPermissionSetsTests($roleAssignments, $roles);

        $this->expectException(LimitationNotFoundException::class);
        $permissionResolverMock->hasAccess('dummy-module', 'dummy-function');
    }

    /**
     * @phpstan-return iterable<string, array{
     *     0: array<int, \Ibexa\Contracts\Core\Persistence\User\Role>
     * }>
     */
    public function providerForTestHasAccessReturnsInvalidArgumentValueException(): iterable
    {
        yield 'single role with all limitations' => [
            [
                31 => self::createRole(
                    [
                        ['test-module', 'test-function', '*'],
                    ],
                    31
                ),
            ],
        ];

        yield 'multiple roles with all limitations' => [
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
        ];
    }

    /**
     * @dataProvider providerForTestHasAccessReturnsInvalidArgumentValueException
     *
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\Role> $roles
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testHasAccessReturnsInvalidArgumentValueException(array $roles): void
    {
        $permissionResolverMock = $this->getPermissionResolverMock(['getCurrentUserReference']);

        $exceptionThrown = false;
        foreach ($roles as $role) {
            foreach ($role->policies as $policy) {
                try {
                    $permissionResolverMock->hasAccess($policy->module, $policy->function);
                    self::fail(
                        sprintf(
                            'Expected %s exception for %s/%s policy',
                            InvalidArgumentValue::class,
                            $policy->module,
                            $policy->function
                        )
                    );
                } catch (InvalidArgumentValue) {
                    $exceptionThrown = true;
                }
            }
        }

        self::assertTrue($exceptionThrown, sprintf('Expected %s exception to be thrown', InvalidArgumentValue::class));
    }

    /**
     * Returns Role stub.
     *
     * @phpstan-param list<array{string, string, string|array<string, string[]>}> $policiesData
     */
    private static function createRole(array $policiesData, int $roleId): Role
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
     * @phpstan-return iterable<array{
     *     0: bool|array<mixed>,
     *     1: bool
     * }>
     */
    public function providerForTestCanUserSimple(): iterable
    {
        yield [true, true];
        yield [false, false];
        yield [[], false];
    }

    /**
     * Tests execution paths with permission sets equaling to boolean value or empty array.
     *
     * @dataProvider providerForTestCanUserSimple
     *
     * @param bool|array<mixed> $permissionSets
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testCanUserSimple(bool|array $permissionSets, bool $result): void
    {
        $permissionResolverMock = $this->getPermissionResolverMock(['hasAccess']);

        $permissionResolverMock
            ->expects(self::once())
            ->method('hasAccess')
            ->with(self::equalTo('test-module'), self::equalTo('test-function'))
            ->willReturn($permissionSets);

        $valueObject = $this->getMockForAbstractClass(ValueObject::class);

        self::assertEquals(
            $result,
            $permissionResolverMock->canUser('test-module', 'test-function', $valueObject, [$valueObject])
        );
    }

    /**
     * Tests an execution path with a permission set defining no limitations.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCanUserWithoutLimitations(): void
    {
        $permissionResolverMock = $this->getPermissionResolverMock(
            [
                'hasAccess',
                'getCurrentUserReference',
            ]
        );

        $policyMock = $this->createMock(APIPolicy::class);
        $policyMock
            ->expects(self::once())
            ->method('getLimitations')
            ->willReturn([]);

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
            ->willReturn($permissionSets);

        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->willReturn(new UserReference(14));

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
     * @return list<array{
     *     limitation: \Ibexa\Contracts\Core\Repository\Values\User\Limitation,
     *     policies: list<\Ibexa\Contracts\Core\Repository\Values\User\Policy>
     * }>
     */
    private function getPermissionSetsMock(): array
    {
        $roleLimitationMock = $this->createMock(Limitation::class);
        $roleLimitationMock
            ->method('getIdentifier')
            ->willReturn('test-role-limitation-identifier');

        $policyLimitationMock = $this->createMock(Limitation::class);
        $policyLimitationMock
            ->method('getIdentifier')
            ->willReturn('test-policy-limitation-identifier');

        $policyMock = $this->createMock(APIPolicy::class);
        $policyMock
            ->method('getLimitations')
            ->willReturn([$policyLimitationMock, $policyLimitationMock]);

        $permissionSet = [
            'limitation' => clone $roleLimitationMock,
            'policies' => [$policyMock, $policyMock],
        ];

        return [$permissionSet, $permissionSet];
    }

    /**
     * @phpstan-return iterable<array{
     *     0: array<bool>,
     *     1: array<array<array<bool>>>,
     *     2: bool
     * }>
     */
    public function providerForTestCanUserComplex(): iterable
    {
        yield [
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
        ];

        yield [
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
        ];

        yield [
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
        ];

        yield [
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
        ];

        yield [
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
        ];
    }

    /**
     * Tests execution paths with permission sets containing limitations.
     *
     * @dataProvider providerForTestCanUserComplex
     *
     * @param array<bool> $roleLimitationEvaluations
     * @param array<array<array<bool>>> $policyLimitationEvaluations
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testCanUserComplex(
        array $roleLimitationEvaluations,
        array $policyLimitationEvaluations,
        bool $userCan
    ): void {
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
            ->willReturn($permissionSets);

        $userRef = new UserReference(14);
        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->willReturn(new UserReference(14));

        $getLimitationTypeInvocationMocker = new InvocationMocker('getLimitationType');
        $permissionSetCount = count($permissionSets);
        for ($i = 0; $i < $permissionSetCount; ++$i) {
            $limitation = $this->createMock(Type::class);
            $limitation
                ->expects(self::once())
                ->method('evaluate')
                ->with($permissionSets[$i]['limitation'], $userRef, $valueObject, [$valueObject])
                ->willReturn($roleLimitationEvaluations[$i]);

            $getLimitationTypeInvocationMocker->expect(['test-role-limitation-identifier'], $limitation);

            if (!$roleLimitationEvaluations[$i]) {
                continue;
            }

            $policyCount = count($permissionSets[$i]['policies']);
            for ($j = 0; $j < $policyCount; ++$j) {
                $limitationsPass = $this->mockEvaluateCallsUntilLimitationPass(
                    $permissionSets[$i]['policies'][$j],
                    $userRef,
                    $valueObject,
                    $policyLimitationEvaluations[$i][$j],
                    $getLimitationTypeInvocationMocker
                );

                if ($limitationsPass) {
                    break 2;
                }
            }
        }

        $limitationServiceMock
            ->expects(self::exactly($getLimitationTypeInvocationMocker->getExpectedInvocationCount()))
            ->method('getLimitationType')
            ->willReturnCallback(
                $getLimitationTypeInvocationMocker
            );

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
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testSetAndGetCurrentUserReference(): void
    {
        $permissionResolverMock = $this->getPermissionResolverMock();
        $userReferenceMock = $this->getUserReferenceMock();

        $userReferenceMock
            ->expects(self::once())
            ->method('getUserId')
            ->willReturn(42);

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
        $permissionResolverMock = $this->getPermissionResolverMock();

        self::assertEquals(new UserReference(10), $permissionResolverMock->getCurrentUserReference());
    }

    protected PermissionResolver & MockObject $permissionResolverMock;

    /**
     * @param string[] $methods
     */
    protected function getPermissionResolverMock(array $methods = []): PermissionResolver & MockObject
    {
        if (!isset($this->permissionResolverMock)) {
            $configResolverMock = $this->createMock(ConfigResolverInterface::class);
            $configResolverMock
                ->method('getParameter')
                ->with('anonymous_user_id')
                ->willReturn(10);

            $this->permissionResolverMock = $this
                ->getMockBuilder(PermissionResolver::class)
                ->onlyMethods($methods)
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

    protected function getUserReferenceMock(): UserReference & MockObject
    {
        if (!isset($this->userReferenceMock)) {
            $this->userReferenceMock = $this->createMock(UserReference::class);
        }

        return $this->userReferenceMock;
    }

    private function buildCoreAPIPolicyFromPersistenceValue(Policy $persistencePolicy): APIPolicy
    {
        $limitationMocks = [];
        if (is_array($persistencePolicy->limitations)) {
            foreach (array_keys($persistencePolicy->limitations) as $limitationIdentifier) {
                $limitationMock = $this->createMock(Limitation::class);
                $limitationMock->method('getIdentifier')->willReturn($limitationIdentifier);
                $limitationMocks[] = $limitationMock;
            }
        } elseif ($persistencePolicy->limitations === 'notfound') {
            throw new LimitationNotFoundException('notfound');
        }

        return new CorePolicy(
            [
                'id' => $persistencePolicy->id,
                'roleId' => $persistencePolicy->roleId,
                'module' => $persistencePolicy->module,
                'function' => $persistencePolicy->function,
                'limitations' => $limitationMocks,
            ]
        );
    }

    /**
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\Role> $roles
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment> $roleAssignments
     *
     * @phpstan-return list<array{
     *     limitation: null|\Ibexa\Contracts\Core\Repository\Values\User\Limitation,
     *     policies: list<\Ibexa\Contracts\Core\Repository\Values\User\Policy>
     * }>
     */
    private function buildExpectedPermissionSets(array $roles, array $roleAssignments): array
    {
        $roleAssignmentLimitationMap = [];
        $permissionSets = [];
        foreach ($roleAssignments as $roleAssignment) {
            $roleAssignmentLimitation = $this->buildExpectedRoleAssignmentLimitation($roleAssignment);
            if ($roleAssignmentLimitation !== null) {
                $roleAssignmentLimitationMap[$roleAssignment->limitationIdentifier] = $roleAssignmentLimitation;
            }

            $permissionSet = ['limitation' => $roleAssignmentLimitation];
            foreach ($roles[$roleAssignment->roleId]->policies as $persistencePolicy) {
                $permissionSet['policies'][] = $this->buildCoreAPIPolicyFromPersistenceValue($persistencePolicy);
            }

            if (!empty($permissionSet['policies'])) {
                $permissionSets[] = $permissionSet;
            }
        }

        $limitationService = $this->getLimitationServiceMock();
        $limitationService
            ->expects(self::exactly(count($roleAssignmentLimitationMap)))
            ->method('getLimitationType')
            ->willReturnCallback(
                function (string $identifier) use ($roleAssignmentLimitationMap): Type {
                    $limitationStub = $roleAssignmentLimitationMap[$identifier];
                    $limitationTypeMock = $this->createMock(Type::class);
                    $limitationTypeMock
                        ->method('buildValue')
                        ->willReturn($limitationStub);

                    return $limitationTypeMock;
                }
            );

        return $permissionSets;
    }

    private function buildExpectedRoleAssignmentLimitation(RoleAssignment $roleAssignment): ?Limitation
    {
        if (empty($roleAssignment->values) || empty($roleAssignment->limitationIdentifier)) {
            return null;
        }

        return new class($roleAssignment->limitationIdentifier, $roleAssignment->values) extends Limitation {
            private string $identifier;

            /**
             * @param mixed[] $limitationValues
             */
            public function __construct(string $identifier, array $limitationValues)
            {
                $this->identifier = $identifier;
                $this->limitationValues = $limitationValues;
                parent::__construct();
            }

            public function getIdentifier(): string
            {
                return $this->identifier;
            }
        };
    }

    /**
     * @param array<bool> $policyLimitationEvaluations
     */
    private function mockEvaluateCallsUntilLimitationPass(
        APIPolicy $policy,
        UserReference $userRef,
        ValueObject $valueObject,
        array $policyLimitationEvaluations,
        InvocationMocker $getLimitationTypeInvocationMocker
    ): bool {
        $limitations = iterator_to_array($policy->getLimitations());
        $limitationCount = count($limitations);
        $limitationsPass = false;
        for ($k = 0; $k < $limitationCount; ++$k) {
            $limitationsPass = true;
            $limitation1 = $this->createMock(Type::class);
            $limitation1
                ->expects(self::once())
                ->method('evaluate')
                ->with($limitations[$k], $userRef, $valueObject, [$valueObject])
                ->willReturn($policyLimitationEvaluations[$k]);

            $getLimitationTypeInvocationMocker->expect(['test-policy-limitation-identifier'], $limitation1);

            if (!$policyLimitationEvaluations[$k]) {
                $limitationsPass = false;
                break;
            }
        }

        return $limitationsPass;
    }

    /**
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\Role> $roles
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment> $roleAssignments
     */
    private function mockPermissionResolverForPermissionSetsTests(
        array $roleAssignments,
        array $roles
    ): MockObject & PermissionResolver {
        /** @var \Ibexa\Contracts\Core\Persistence\User\Handler&\PHPUnit\Framework\MockObject\MockObject $userHandlerMock */
        $userHandlerMock = $this->getPersistenceMock()->userHandler();
        $roleDomainMapper = $this->getRoleDomainMapperMock();
        $permissionResolverMock = $this->getPermissionResolverMock(['getCurrentUserReference']);

        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->willReturn(new UserReference(14));

        $userHandlerMock
            ->expects(self::once())
            ->method('loadRoleAssignmentsByGroupId')
            ->with(self::isType('integer'), self::equalTo(true))
            ->willReturn($roleAssignments);

        $userHandlerMock
            ->expects(self::exactly(count($roleAssignments)))
            ->method('loadRole')
            ->willReturnCallback(static fn (int $roleId): Role => $roles[$roleId]);

        $roleDomainMapper
            ->method('buildDomainPolicyObject')
            ->willReturnCallback(
                fn (Policy $persistencePolicy): APIPolicy => $this->buildCoreAPIPolicyFromPersistenceValue(
                    $persistencePolicy
                )
            );

        return $permissionResolverMock;
    }
}

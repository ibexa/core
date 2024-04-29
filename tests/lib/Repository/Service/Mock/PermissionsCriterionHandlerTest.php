<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Ibexa\Contracts\Core\Limitation\Type;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitation;
use Ibexa\Core\Repository\PermissionsCriterionHandler;
use Ibexa\Core\Repository\Values\User\Policy;
use Ibexa\Core\Repository\Values\User\User;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;

/**
 * Mock test case for PermissionCriterionHandler.
 *
 * @deprecated
 */
class PermissionsCriterionHandlerTest extends BaseServiceMockTest
{
    public function providerForTestAddPermissionsCriterionWithBooleanPermission()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * Test for the addPermissionsCriterion() method.
     *
     * @dataProvider providerForTestAddPermissionsCriterionWithBooleanPermission
     */
    public function testAddPermissionsCriterionWithBooleanPermission($permissionsCriterion)
    {
        $handler = $this->getPermissionsCriterionHandlerMock(['getPermissionsCriterion']);
        $criterionMock = $this->createMock(Criterion::class);

        $handler
            ->expects(self::once())
            ->method('getPermissionsCriterion')
            ->will(self::returnValue($permissionsCriterion));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterionMock */
        $result = $handler->addPermissionsCriterion($criterionMock);

        self::assertSame($permissionsCriterion, $result);
    }

    public function providerForTestAddPermissionsCriterion()
    {
        $criterionMock = $this->createMock(Criterion::class);

        return [
            [
                $criterionMock,
                new Criterion\LogicalAnd([]),
                new Criterion\LogicalAnd([$criterionMock]),
            ],
            [
                $criterionMock,
                $criterionMock,
                new Criterion\LogicalAnd([$criterionMock, $criterionMock]),
            ],
        ];
    }

    /**
     * Test for the addPermissionsCriterion() method.
     *
     * @dataProvider providerForTestAddPermissionsCriterion
     */
    public function testAddPermissionsCriterion($permissionsCriterionMock, $givenCriterion, $expectedCriterion)
    {
        $handler = $this->getPermissionsCriterionHandlerMock(['getPermissionsCriterion']);
        $handler
            ->expects(self::once())
            ->method('getPermissionsCriterion')
            ->will(self::returnValue($permissionsCriterionMock));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterionMock */
        $result = $handler->addPermissionsCriterion($givenCriterion);

        self::assertTrue($result);
        self::assertEquals($expectedCriterion, $givenCriterion);
    }

    public function providerForTestGetPermissionsCriterion()
    {
        $criterionMock = $this->createMock(Criterion::class);
        $limitationMock = $this
            ->getMockBuilder(APILimitation::class)
            ->getMockForAbstractClass();
        $limitationMock
            ->expects(self::any())
            ->method('getIdentifier')
            ->will(self::returnValue('limitationIdentifier'));

        $policy1 = new Policy(['limitations' => [$limitationMock]]);
        $policy2 = new Policy(['limitations' => [$limitationMock, $limitationMock]]);

        return [
            [
                $criterionMock,
                1,
                [
                    [
                        'limitation' => null,
                        'policies' => [$policy1],
                    ],
                ],
                $criterionMock,
            ],
            [
                $criterionMock,
                2,
                [
                    [
                        'limitation' => null,
                        'policies' => [$policy1, $policy1],
                    ],
                ],
                new Criterion\LogicalOr([$criterionMock, $criterionMock]),
            ],
            [
                $criterionMock,
                0,
                [
                    [
                        'limitation' => null,
                        'policies' => [new Policy(['limitations' => []]), $policy1],
                    ],
                ],
                false,
            ],
            [
                $criterionMock,
                2,
                [
                    [
                        'limitation' => null,
                        'policies' => [$policy2],
                    ],
                ],
                new Criterion\LogicalAnd([$criterionMock, $criterionMock]),
            ],
            [
                $criterionMock,
                3,
                [
                    [
                        'limitation' => null,
                        'policies' => [$policy1, $policy2],
                    ],
                ],
                new Criterion\LogicalOr(
                    [
                        $criterionMock,
                        new Criterion\LogicalAnd([$criterionMock, $criterionMock]),
                    ]
                ),
            ],
            [
                $criterionMock,
                2,
                [
                    [
                        'limitation' => null,
                        'policies' => [$policy1],
                    ],
                    [
                        'limitation' => null,
                        'policies' => [$policy1],
                    ],
                ],
                new Criterion\LogicalOr([$criterionMock, $criterionMock]),
            ],
            [
                $criterionMock,
                3,
                [
                    [
                        'limitation' => null,
                        'policies' => [$policy1],
                    ],
                    [
                        'limitation' => null,
                        'policies' => [$policy1, $policy1],
                    ],
                ],
                new Criterion\LogicalOr([$criterionMock, $criterionMock, $criterionMock]),
            ],
            [
                $criterionMock,
                3,
                [
                    [
                        'limitation' => null,
                        'policies' => [$policy2],
                    ],
                    [
                        'limitation' => null,
                        'policies' => [$policy1],
                    ],
                ],
                new Criterion\LogicalOr(
                    [
                        new Criterion\LogicalAnd([$criterionMock, $criterionMock]),
                        $criterionMock,
                    ]
                ),
            ],
            [
                $criterionMock,
                2,
                [
                    [
                        'limitation' => $limitationMock,
                        'policies' => [$policy1],
                    ],
                ],
                new Criterion\LogicalAnd([$criterionMock, $criterionMock]),
            ],
            [
                $criterionMock,
                4,
                [
                    [
                        'limitation' => $limitationMock,
                        'policies' => [$policy1],
                    ],
                    [
                        'limitation' => $limitationMock,
                        'policies' => [$policy1],
                    ],
                ],
                new Criterion\LogicalOr(
                    [
                        new Criterion\LogicalAnd([$criterionMock, $criterionMock]),
                        new Criterion\LogicalAnd([$criterionMock, $criterionMock]),
                    ]
                ),
            ],
            [
                $criterionMock,
                1,
                [
                    [
                        'limitation' => $limitationMock,
                        'policies' => [new Policy(['limitations' => []])],
                    ],
                ],
                $criterionMock,
            ],
            [
                $criterionMock,
                2,
                [
                    [
                        'limitation' => $limitationMock,
                        'policies' => [new Policy(['limitations' => []])],
                    ],
                    [
                        'limitation' => $limitationMock,
                        'policies' => [new Policy(['limitations' => []])],
                    ],
                ],
                new Criterion\LogicalOr([$criterionMock, $criterionMock]),
            ],
        ];
    }

    protected function mockServices($criterionMock, $limitationCount, $permissionSets)
    {
        $userMock = $this->createMock(User::class);
        $limitationTypeMock = $this->createMock(Type::class);
        $limitationServiceMock = $this->getLimitationServiceMock();
        $permissionResolverMock = $this->getPermissionResolverMock(
            [
                'hasAccess',
                'getCurrentUserReference',
            ]
        );

        $limitationTypeMock
            ->expects(self::any())
            ->method('getCriterion')
            ->with(
                self::isInstanceOf(APILimitation::class),
                self::equalTo($userMock)
            )
            ->will(self::returnValue($criterionMock));

        $limitationServiceMock
            ->expects(self::exactly($limitationCount))
            ->method('getLimitationType')
            ->with(self::equalTo('limitationIdentifier'))
            ->will(self::returnValue($limitationTypeMock));

        $permissionResolverMock
            ->expects(self::once())
            ->method('hasAccess')
            ->with(self::equalTo('content'), self::equalTo('read'))
            ->will(self::returnValue($permissionSets));

        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->will(self::returnValue($userMock));
    }

    /**
     * Test for the getPermissionsCriterion() method.
     *
     * @dataProvider providerForTestGetPermissionsCriterion
     */
    public function testGetPermissionsCriterion(
        $criterionMock,
        $limitationCount,
        $permissionSets,
        $expectedCriterion
    ) {
        $this->mockServices($criterionMock, $limitationCount, $permissionSets);
        $handler = $this->getPermissionsCriterionHandlerMock(null);

        $permissionsCriterion = $handler->getPermissionsCriterion();

        self::assertEquals($expectedCriterion, $permissionsCriterion);
    }

    public function providerForTestGetPermissionsCriterionBooleanPermissionSets()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * Test for the getPermissionsCriterion() method.
     *
     * @dataProvider providerForTestGetPermissionsCriterionBooleanPermissionSets
     */
    public function testGetPermissionsCriterionBooleanPermissionSets($permissionSets)
    {
        $permissionResolverMock = $this->getPermissionResolverMock(['hasAccess']);
        $permissionResolverMock
            ->expects(self::once())
            ->method('hasAccess')
            ->with(self::equalTo('testModule'), self::equalTo('testFunction'))
            ->will(self::returnValue($permissionSets));
        $handler = $this->getPermissionsCriterionHandlerMock(null);

        $permissionsCriterion = $handler->getPermissionsCriterion('testModule', 'testFunction');

        self::assertEquals($permissionSets, $permissionsCriterion);
    }

    /**
     * Returns the PermissionsCriterionHandler to test with $methods mocked.
     *
     * @param string[]|null $methods
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Repository\PermissionsCriterionHandler
     */
    protected function getPermissionsCriterionHandlerMock($methods = [])
    {
        return $this
            ->getMockBuilder(PermissionsCriterionHandler::class)
            ->setMethods($methods)
            ->setConstructorArgs(
                [
                    $this->getPermissionResolverMock(),
                    $this->getLimitationServiceMock(),
                ]
            )
            ->getMock();
    }

    protected $permissionResolverMock;

    protected function getPermissionResolverMock($methods = [])
    {
        if ($this->permissionResolverMock === null) {
            $this->permissionResolverMock = $this
                ->getMockBuilder(PermissionResolver::class)
                ->setMethods($methods)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
        }

        return $this->permissionResolverMock;
    }
}

class_alias(PermissionsCriterionHandlerTest::class, 'eZ\Publish\Core\Repository\Tests\Service\Mock\PermissionsCriterionHandlerTest');

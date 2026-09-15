<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Content\VersionInfo as SPIVersionInfo;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\StatusLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Limitation\StatusLimitationType;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Test Case for LimitationType.
 */
class StatusLimitationTypeTest extends Base
{
    /**
     * @return \Ibexa\Core\Limitation\StatusLimitationType
     */
    public function testConstruct()
    {
        return new StatusLimitationType();
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValue()
    {
        return [
            [new StatusLimitation()],
            [new StatusLimitation([])],
            [
                new StatusLimitation(
                    [
                        'limitationValues' => [
                            VersionInfo::STATUS_DRAFT,
                            VersionInfo::STATUS_PUBLISHED,
                            VersionInfo::STATUS_ARCHIVED,
                        ],
                    ]
                ),
            ],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\StatusLimitation $limitation
     * @param \Ibexa\Core\Limitation\StatusLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    #[DataProvider('providerForTestAcceptValue')]
    public function testAcceptValue(StatusLimitation $limitation, StatusLimitationType $limitationType)
    {
        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValueException()
    {
        return [
            [new ObjectStateLimitation()],
            [new StatusLimitation(['limitationValues' => [true]])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Core\Limitation\StatusLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    #[DataProvider('providerForTestAcceptValueException')]
    public function testAcceptValueException(Limitation $limitation, StatusLimitationType $limitationType)
    {
        $this->expectException(InvalidArgumentException::class);

        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public static function providerForTestValidateError()
    {
        return [
            [new StatusLimitation(), 0],
            [new StatusLimitation([]), 0],
            [
                new StatusLimitation(
                    [
                        'limitationValues' => [SPIVersionInfo::STATUS_PUBLISHED],
                    ]
                ),
                0,
            ],
            [new StatusLimitation(['limitationValues' => [100]]), 1],
            [
                new StatusLimitation(
                    [
                        'limitationValues' => [
                            SPIVersionInfo::STATUS_PUBLISHED,
                            PHP_INT_MAX,
                        ],
                    ]
                ),
                1,
            ],
            [
                new StatusLimitation(
                    [
                        'limitationValues' => [
                            SPIVersionInfo::STATUS_PENDING,
                            SPIVersionInfo::STATUS_REJECTED,
                        ],
                    ]
                ),
                2,
            ],
            [
                new StatusLimitation(
                    [
                        'limitationValues' => [
                            SPIVersionInfo::STATUS_DRAFT,
                            SPIVersionInfo::STATUS_PUBLISHED,
                            SPIVersionInfo::STATUS_ARCHIVED,
                        ],
                    ]
                ),
                0,
            ],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\StatusLimitation $limitation
     * @param int $errorCount
     * @param \Ibexa\Core\Limitation\StatusLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    #[DataProvider('providerForTestValidateError')]
    public function testValidateError(StatusLimitation $limitation, $errorCount, StatusLimitationType $limitationType)
    {
        $validationErrors = $limitationType->validate($limitation);
        self::assertCount($errorCount, $validationErrors);
    }

    /**
     * @param \Ibexa\Core\Limitation\StatusLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testBuildValue(StatusLimitationType $limitationType)
    {
        $expected = ['test', 'test' => 9];
        $value = $limitationType->buildValue($expected);

        self::assertInstanceOf(StatusLimitation::class, $value);
        self::assertIsArray($value->limitationValues);
        self::assertEquals($expected, $value->limitationValues);
    }

    protected function getVersionInfoMock($shouldBeCalled = true)
    {
        $versionInfoMock = $this->getMockBuilder(APIVersionInfo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMockForAbstractClass();

        if ($shouldBeCalled) {
            $versionInfoMock
                ->expects(self::once())
                ->method('__get')
                ->with('status')
                ->will(self::returnValue(24));
        } else {
            $versionInfoMock
                ->expects(self::never())
                ->method('__get')
                ->with('status');
        }

        return $versionInfoMock;
    }

    protected function getContentMock($shouldBeCalled = true)
    {
        $contentMock = $this->getMockBuilder(APIContent::class)
            ->setConstructorArgs([])
            ->getMock();

        $contentMock
            ->expects(self::once())
            ->method('getVersionInfo')
            ->will(self::returnValue($this->getVersionInfoMock($shouldBeCalled)));

        return $contentMock;
    }

    /**
     * @return array
     */
    public static function providerForTestEvaluate()
    {
        return [
            // VersionInfo, no access
            [
                new StatusLimitation(),
                'versionInfo',
                false,
                false,
            ],
            // VersionInfo, no access
            [
                new StatusLimitation(['limitationValues' => [42]]),
                'versionInfo',
                true,
                false,
            ],
            // VersionInfo, with access
            [
                new StatusLimitation(['limitationValues' => [24]]),
                'versionInfo',
                true,
                true,
            ],
            // Content, no access
            [
                new StatusLimitation(),
                'content',
                false,
                false,
            ],
            // Content, no access
            [
                new StatusLimitation(['limitationValues' => [42]]),
                'content',
                true,
                false,
            ],
            // Content, with access
            [
                new StatusLimitation(['limitationValues' => [24]]),
                'content',
                true,
                true,
            ],
        ];
    }

    #[Depends('testConstruct')]
    #[DataProvider('providerForTestEvaluate')]
    public function testEvaluate(
        StatusLimitation $limitation,
        string $objectType,
        bool $shouldBeCalled,
        $expected,
        StatusLimitationType $limitationType
    ) {
        $object = $objectType === 'versionInfo' ? $this->getVersionInfoMock($shouldBeCalled) : $this->getContentMock($shouldBeCalled);

        $userMock = $this->getUserMock();
        $userMock->expects(self::never())
            ->method(self::anything());

        $userMock = new User();
        $value = $limitationType->evaluate(
            $limitation,
            $userMock,
            $object
        );

        self::assertIsBool($value);
        self::assertEquals($expected, $value);
    }

    /**
     * @return array
     */
    public static function providerForTestEvaluateInvalidArgument()
    {
        $versionInfoStub = new class() extends APIVersionInfo {
            public function getContentInfo(): \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
            {
                return new \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo();
            }

            public function getCreator(): User
            {
                return new User();
            }

            public function getInitialLanguage(): \Ibexa\Contracts\Core\Repository\Values\Content\Language
            {
                return new \Ibexa\Contracts\Core\Repository\Values\Content\Language();
            }

            public function getLanguages(): iterable
            {
                return [];
            }

            public function getNames(): array
            {
                return [];
            }

            public function getName(?string $languageCode = null): ?string
            {
                return null;
            }
        };

        return [
            // invalid limitation
            [
                new ObjectStateLimitation(),
                $versionInfoStub,
            ],
            // invalid object
            [
                new StatusLimitation(),
                new ObjectStateLimitation(),
            ],
        ];
    }

    #[Depends('testConstruct')]
    #[DataProvider('providerForTestEvaluateInvalidArgument')]
    public function testEvaluateInvalidArgument(
        Limitation $limitation,
        ValueObject $object,
        StatusLimitationType $limitationType
    ) {
        $this->expectException(InvalidArgumentException::class);

        $userMock = $this->getUserMock();
        $userMock->expects(self::never())->method(self::anything());

        $userMock = new User();
        $limitationType->evaluate(
            $limitation,
            $userMock,
            $object
        );
    }

    /**
     * @param \Ibexa\Core\Limitation\StatusLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testGetCriterion(StatusLimitationType $limitationType)
    {
        $this->expectException(NotImplementedException::class);

        $limitationType->getCriterion(new StatusLimitation(), $this->getUserMock());
    }

    /**
     * @param \Ibexa\Core\Limitation\StatusLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testValueSchema(StatusLimitationType $limitationType)
    {
        self::markTestSkipped('Method valueSchema() is not implemented');
    }
}

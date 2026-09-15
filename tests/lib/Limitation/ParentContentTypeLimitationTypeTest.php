<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo as SPIContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Handler as SPIContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPIContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ParentContentTypeLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\ParentContentTypeLimitationType;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\Location;

/**
 * Test Case for LimitationType.
 */
class ParentContentTypeLimitationTypeTest extends Base
{
    public const int EXAMPLE_CONTENT_INFO_ID = 24;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Location\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $locationHandlerMock;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Type\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $contentTypeHandlerMock;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $contentHandlerMock;

    /**
     * Setup Location Handler mock.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->locationHandlerMock = $this->createMock(SPILocation\Handler::class);
        $this->contentTypeHandlerMock = $this->createMock(SPIContentTypeHandler::class);
        $this->contentHandlerMock = $this->createMock(SPIContentHandler::class);
    }

    /**
     * Tear down Location Handler mock.
     */
    protected function tearDown(): void
    {
        unset($this->locationHandlerMock);
        unset($this->contentTypeHandlerMock);
        unset($this->contentHandlerMock);
        parent::tearDown();
    }

    /**
     * @return \Ibexa\Core\Limitation\ParentContentTypeLimitationType
     */
    public function testConstruct()
    {
        return new ParentContentTypeLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValue()
    {
        return [
            [new ParentContentTypeLimitation()],
            [new ParentContentTypeLimitation([])],
            [new ParentContentTypeLimitation(['limitationValues' => ['', 'true', '2', 's3fd4af32r']])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ParentContentTypeLimitation $limitation
     * @param \Ibexa\Core\Limitation\ParentContentTypeLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestAcceptValue')]
    public function testAcceptValue(ParentContentTypeLimitation $limitation, ParentContentTypeLimitationType $limitationType)
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
            [new ParentContentTypeLimitation(['limitationValues' => [true]])],
            [new ParentContentTypeLimitation(['limitationValues' => [new \DateTime()]])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Core\Limitation\ParentContentTypeLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestAcceptValueException')]
    public function testAcceptValueException(Limitation $limitation, ParentContentTypeLimitationType $limitationType)
    {
        $this->expectException(InvalidArgumentException::class);

        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public static function providerForTestValidatePass()
    {
        return [
            [new ParentContentTypeLimitation()],
            [new ParentContentTypeLimitation([])],
            [new ParentContentTypeLimitation(['limitationValues' => ['1']])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ParentContentTypeLimitation $limitation
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestValidatePass')]
    public function testValidatePass(ParentContentTypeLimitation $limitation)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('contentTypeHandler')
                ->will(self::returnValue($this->contentTypeHandlerMock));

            $limitationValues = $limitation->limitationValues;
            $matcher = self::exactly(count($limitationValues));
            $this->contentTypeHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function ($actualValue) use ($matcher, $limitationValues) {
                    self::assertSame($limitationValues[$matcher->numberOfInvocations() - 1], $actualValue);

                    return 42;
                });
        }

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $validationErrors = $limitationType->validate($limitation);
        self::assertEmpty($validationErrors);
    }

    /**
     * @return array
     */
    public static function providerForTestValidateError()
    {
        return [
            [new ParentContentTypeLimitation(), 0],
            [new ParentContentTypeLimitation(['limitationValues' => ['/1/777/']]), 1],
            [new ParentContentTypeLimitation(['limitationValues' => ['/1/888/', '/1/999/']]), 2],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\ParentContentTypeLimitation $limitation
     * @param int $errorCount
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestValidateError')]
    public function testValidateError(ParentContentTypeLimitation $limitation, $errorCount)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('contentTypeHandler')
                ->will(self::returnValue($this->contentTypeHandlerMock));

            $limitationValues = $limitation->limitationValues;
            $matcher = self::exactly(count($limitationValues));
            $this->contentTypeHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function ($actualValue) use ($matcher, $limitationValues): void {
                    $value = $limitationValues[$matcher->numberOfInvocations() - 1];
                    self::assertSame($value, $actualValue);

                    throw new NotFoundException('location', $value);
                });
        } else {
            $this->getPersistenceMock()
                ->expects(self::never())
                ->method(self::anything());
        }

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $validationErrors = $limitationType->validate($limitation);
        self::assertCount($errorCount, $validationErrors);
    }

    /**
     * @param \Ibexa\Core\Limitation\ParentContentTypeLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testBuildValue(ParentContentTypeLimitationType $limitationType)
    {
        $expected = ['test', 'test' => '1'];
        $value = $limitationType->buildValue($expected);

        self::assertInstanceOf(ParentContentTypeLimitation::class, $value);
        self::assertIsArray($value->limitationValues);
        self::assertEquals($expected, $value->limitationValues);
    }

    protected function getTestEvaluateContentMock()
    {
        $contentMock = $this->createMock(APIContent::class);

        $contentMock
            ->expects(self::once())
            ->method('getVersionInfo')
            ->will(self::returnValue($this->getTestEvaluateVersionInfoMock()));

        return $contentMock;
    }

    protected function getTestEvaluateVersionInfoMock()
    {
        $versionInfoMock = $this->createMock(APIVersionInfo::class);

        $versionInfoMock
            ->expects(self::once())
            ->method('getContentInfo')
            ->will(self::returnValue(new ContentInfo(['published' => true])));

        return $versionInfoMock;
    }

    /**
     * @return array
     */
    public static function providerForTestEvaluate()
    {
        return [
            // ContentInfo, with API targets, no access
            [
                'limitation' => new ParentContentTypeLimitation(),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new Location(['contentInfo' => new ContentInfo(['contentTypeId' => 24])])],
                'persistence' => [],
                'expected' => false,
            ],
            // ContentInfo, with SPI targets, no access
            [
                'limitation' => new ParentContentTypeLimitation(),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new SPILocation(['contentId' => 42])],
                'persistence' => [
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '24'])],
                ],
                'expected' => false,
            ],
            // ContentInfo, with API targets, no access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new Location(['contentInfo' => new ContentInfo(['contentTypeId' => 24])])],
                'persistence' => [],
                'expected' => false,
            ],
            // ContentInfo, with SPI targets, no access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new SPILocation(['contentId' => 42])],
                'persistence' => [
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '24'])],
                ],
                'expected' => false,
            ],
            // ContentInfo, with API targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new Location(['contentInfo' => new ContentInfo(['contentTypeId' => 42])])],
                'persistence' => [],
                'expected' => true,
            ],
            // ContentInfo, with SPI targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new SPILocation(['contentId' => 24])],
                'persistence' => [
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '42'])],
                ],
                'expected' => true,
            ],
            // ContentInfo, no targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [43]]),
                'object' => new ContentInfo(['published' => true, 'id' => 40]),
                'targets' => [],
                'persistence' => [
                    'locations' => [new SPILocation(['id' => 40, 'contentId' => '24', 'parentId' => 43, 'depth' => 1])],
                    'parentLocations' => [43 => new SPILocation(['id' => 43, 'contentId' => 24])],
                    'parentContents' => [24 => new SPIContentInfo(['id' => 24, 'contentTypeId' => 43])],
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '42'])],
                ],
                'expected' => true,
            ],
            // ContentInfo, no targets, no access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [40]]),
                'object' => new ContentInfo(['published' => true, 'id' => 40]),
                'targets' => [],
                'persistence' => [
                    'locations' => [new SPILocation(['id' => 40, 'contentId' => '24', 'parentId' => 43, 'depth' => 1])],
                    'parentLocations' => [43 => new SPILocation(['id' => 43, 'contentId' => 24])],
                    'parentContents' => [24 => new SPIContentInfo(['id' => 24, 'contentTypeId' => 39])],
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '42'])],
                ],
                'expected' => false,
            ],
            // ContentInfo, no targets, un-published, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => new ContentInfo([
                    'id' => self::EXAMPLE_CONTENT_INFO_ID,
                    'published' => false,
                ]),
                'targets' => [],
                'persistence' => [
                    'locations' => [new SPILocation(['contentId' => '24'])],
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '42'])],
                ],
                'expected' => true,
            ],
            // ContentInfo, no targets, un-published, no access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => new ContentInfo([
                    'id' => self::EXAMPLE_CONTENT_INFO_ID,
                    'published' => false,
                ]),
                'targets' => [],
                'persistence' => [
                    'locations' => [new SPILocation(['contentId' => '24'])],
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '4200'])],
                ],
                'expected' => false,
            ],
            // Content, with API targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => 'contentMock',
                'targets' => [new Location(['contentInfo' => new ContentInfo(['contentTypeId' => 42])])],
                'persistence' => [],
                'expected' => true,
            ],
            // Content, with SPI targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => 'contentMock',
                'targets' => [new SPILocation(['contentId' => '24'])],
                'persistence' => [
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '42'])],
                ],
                'expected' => true,
            ],
            // VersionInfo, with API targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => 'versionInfoMock',
                'targets' => [new Location(['contentInfo' => new ContentInfo(['contentTypeId' => 42])])],
                'persistence' => [],
                'expected' => true,
            ],
            // VersionInfo, with SPI targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => 'versionInfoMock',
                'targets' => [new SPILocation(['contentId' => '24'])],
                'persistence' => [
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '42'])],
                ],
                'expected' => true,
            ],
            // VersionInfo, with LocationCreateStruct targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => 'versionInfoMock',
                'targets' => [new LocationCreateStruct(['parentLocationId' => 24])],
                'persistence' => [
                    'locations' => [new SPILocation(['contentId' => 100])],
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '42'])],
                ],
                'expected' => true,
            ],
            // Content, with LocationCreateStruct targets, no access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => 'contentMock',
                'targets' => [new LocationCreateStruct(['parentLocationId' => 24])],
                'persistence' => [
                    'locations' => [new SPILocation(['contentId' => 100])],
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '24'])],
                ],
                'expected' => false,
            ],
            // ContentCreateStruct, no targets, no access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => new ContentCreateStruct(),
                'targets' => [],
                'persistence' => [],
                'expected' => false,
            ],
            // ContentCreateStruct, with LocationCreateStruct targets, no access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [12, 23]]),
                'object' => new ContentCreateStruct(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 24])],
                'persistence' => [
                    'locations' => [new SPILocation(['contentId' => 100])],
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => 34])],
                ],
                'expected' => false,
            ],
            // ContentCreateStruct, with LocationCreateStruct targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [12, 23]]),
                'object' => new ContentCreateStruct(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 43])],
                'persistence' => [
                    'locations' => [new SPILocation(['contentId' => 100])],
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => 12])],
                ],
                'expected' => true,
            ],
        ];
    }

    protected function assertContentHandlerExpectations($callNo, $persistenceCalled, $contentId, $contentInfo)
    {
        $this->getPersistenceMock()
            ->expects(self::once())
            ->method('contentHandler')
            ->will(self::returnValue($this->contentHandlerMock));

        $this->contentHandlerMock
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with($contentId)
            ->will(self::returnValue($contentInfo));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestEvaluate')]
    public function testEvaluate(
        ParentContentTypeLimitation $limitation,
        $object,
        $targets,
        array $persistence,
        $expected
    ) {
        if ($object === 'contentMock') {
            $object = $this->getTestEvaluateContentMock();
        } elseif ($object === 'versionInfoMock') {
            $object = $this->getTestEvaluateVersionInfoMock();
        }

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $userMock = $this->getUserMock();
        $userMock
            ->expects(self::never())
            ->method(self::anything());

        $persistenceMock = $this->getPersistenceMock();
        // ContentTypeHandler is never used in evaluate()
        $persistenceMock
            ->expects(self::never())
            ->method('contentTypeHandler');

        if (empty($persistence)) {
            // Covers API targets, where no additional loading is required
            $persistenceMock
                ->expects(self::never())
                ->method(self::anything());
        } elseif (!empty($targets)) {
            foreach ($targets as $index => $target) {
                if ($target instanceof LocationCreateStruct) {
                    $this->getPersistenceMock()
                        ->expects(self::once())
                        ->method('locationHandler')
                        ->will(self::returnValue($this->locationHandlerMock));

                    $this->locationHandlerMock
                        ->expects(self::once())
                        ->method('load')
                        ->with($target->parentLocationId)
                        ->will(self::returnValue($location = $persistence['locations'][$index]));

                    $contentId = $location->contentId;
                } else {
                    $contentId = $target->contentId;
                }

                $this->assertContentHandlerExpectations(
                    $index,
                    $target instanceof LocationCreateStruct,
                    $contentId,
                    $persistence['contentInfos'][$index]
                );
            }
        } else {
            $this->getPersistenceMock()
                ->method('locationHandler')
                ->will(self::returnValue($this->locationHandlerMock));

            $this->getPersistenceMock()
                ->method('contentHandler')
                ->will(self::returnValue($this->contentHandlerMock));

            $this->locationHandlerMock
                ->method(
                    $object instanceof ContentInfo && $object->published ? 'loadLocationsByContent' : 'loadParentLocationsForDraftContent'
                )
                ->with($object->id)
                ->will(self::returnValue($persistence['locations']));

            foreach ($persistence['locations'] as $location) {
                if (!empty($persistence['parentLocations'][$location->parentId])) {
                    $this->locationHandlerMock
                            ->method('load')
                            ->with($location->parentId)
                            ->will(self::returnValue($persistence['parentLocations'][$location->parentId]));
                }

                if (!empty($persistence['parentLocations'][$location->parentId])) {
                    $this->contentHandlerMock
                            ->method('loadContentInfo')
                            ->with($location->contentId)
                            ->willReturn($persistence['parentContents'][$location->contentId]);
                }
            }

            foreach ($persistence['locations'] as $index => $location) {
                $this->assertContentHandlerExpectations(
                    $index,
                    true,
                    $location->contentId,
                    $persistence['contentInfos'][$index]
                );
            }
        }

        $value = $limitationType->evaluate(
            $limitation,
            $userMock,
            $object,
            $targets
        );

        self::assertIsBool($value);
        self::assertEquals($expected, $value);
    }

    /**
     * @return array
     */
    public static function providerForTestEvaluateInvalidArgument()
    {
        return [
            // invalid limitation
            [
                'limitation' => new ObjectStateLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new Location()],
            ],
            // invalid object
            [
                'limitation' => new ParentContentTypeLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => [],
            ],
            // invalid target when using ContentCreateStruct
            [
                'limitation' => new ParentContentTypeLimitation(),
                'object' => new ContentCreateStruct(),
                'targets' => [new Location()],
            ],
            // invalid target when not using ContentCreateStruct
            [
                'limitation' => new ParentContentTypeLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new ObjectStateLimitation()],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestEvaluateInvalidArgument')]
    public function testEvaluateInvalidArgument(Limitation $limitation, ValueObject $object, $targets)
    {
        $this->expectException(InvalidArgumentException::class);

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $userMock = $this->getUserMock();
        $userMock
            ->expects(self::never())
            ->method(self::anything());

        $persistenceMock = $this->getPersistenceMock();
        $persistenceMock
            ->expects(self::never())
            ->method(self::anything());

        $limitationType->evaluate(
            $limitation,
            $userMock,
            $object,
            $targets
        );
    }

    /**
     * @param \Ibexa\Core\Limitation\ParentContentTypeLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testGetCriterionInvalidValue(ParentContentTypeLimitationType $limitationType)
    {
        $this->expectException(NotImplementedException::class);

        $limitationType->getCriterion(
            new ParentContentTypeLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @param \Ibexa\Core\Limitation\ParentContentTypeLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testValueSchema(ParentContentTypeLimitationType $limitationType)
    {
        self::markTestIncomplete('Method is not implemented yet: ' . __METHOD__);
    }
}

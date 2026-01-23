<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo as SPIContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Handler;
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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test Case for LimitationType.
 */
class ParentContentTypeLimitationTypeTest extends Base
{
    public const int EXAMPLE_CONTENT_INFO_ID = 24;

    /** @var SPILocation\Handler|MockObject */
    private $locationHandlerMock;

    /** @var SPIContentTypeHandler|MockObject */
    private $contentTypeHandlerMock;

    /** @var Handler|MockObject */
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
     * @return ParentContentTypeLimitationType
     */
    public function testConstruct()
    {
        return new ParentContentTypeLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public function providerForTestAcceptValue()
    {
        return [
            [new ParentContentTypeLimitation()],
            [new ParentContentTypeLimitation([])],
            [new ParentContentTypeLimitation(['limitationValues' => ['', 'true', '2', 's3fd4af32r']])],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValue
     *
     * @depends testConstruct
     *
     * @param ParentContentTypeLimitation $limitation
     * @param ParentContentTypeLimitationType $limitationType
     */
    public function testAcceptValue(
        ParentContentTypeLimitation $limitation,
        ParentContentTypeLimitationType $limitationType
    ) {
        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public function providerForTestAcceptValueException()
    {
        return [
            [new ObjectStateLimitation()],
            [new ParentContentTypeLimitation(['limitationValues' => [true]])],
            [new ParentContentTypeLimitation(['limitationValues' => [new \DateTime()]])],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValueException
     *
     * @depends testConstruct
     *
     * @param Limitation $limitation
     * @param ParentContentTypeLimitationType $limitationType
     */
    public function testAcceptValueException(
        Limitation $limitation,
        ParentContentTypeLimitationType $limitationType
    ) {
        $this->expectException(InvalidArgumentException::class);

        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public function providerForTestValidatePass()
    {
        return [
            [new ParentContentTypeLimitation()],
            [new ParentContentTypeLimitation([])],
            [new ParentContentTypeLimitation(['limitationValues' => ['1']])],
        ];
    }

    /**
     * @dataProvider providerForTestValidatePass
     *
     * @param ParentContentTypeLimitation $limitation
     */
    public function testValidatePass(ParentContentTypeLimitation $limitation)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('contentTypeHandler')
                ->will(self::returnValue($this->contentTypeHandlerMock));

            $expectedValues = $limitation->limitationValues;
            $this->contentTypeHandlerMock
                ->expects(self::exactly(count($expectedValues)))
                ->method('load')
                ->willReturnCallback(static function ($value) use (&$expectedValues) {
                    $expectedValue = array_shift($expectedValues);
                    self::assertSame($expectedValue, $value);

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
    public function providerForTestValidateError()
    {
        return [
            [new ParentContentTypeLimitation(), 0],
            [new ParentContentTypeLimitation(['limitationValues' => ['/1/777/']]), 1],
            [new ParentContentTypeLimitation(['limitationValues' => ['/1/888/', '/1/999/']]), 2],
        ];
    }

    /**
     * @dataProvider providerForTestValidateError
     *
     * @param ParentContentTypeLimitation $limitation
     * @param int $errorCount
     */
    public function testValidateError(
        ParentContentTypeLimitation $limitation,
        $errorCount
    ) {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('contentTypeHandler')
                ->will(self::returnValue($this->contentTypeHandlerMock));

            $expectedValues = $limitation->limitationValues;
            $this->contentTypeHandlerMock
                ->expects(self::exactly(count($expectedValues)))
                ->method('load')
                ->willReturnCallback(static function ($value) use (&$expectedValues): void {
                    $expectedValue = array_shift($expectedValues);
                    self::assertSame($expectedValue, $value);
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
     * @depends testConstruct
     *
     * @param ParentContentTypeLimitationType $limitationType
     */
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
    public function providerForTestEvaluate()
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
                'object' => $this->getTestEvaluateContentMock(),
                'targets' => [new Location(['contentInfo' => new ContentInfo(['contentTypeId' => 42])])],
                'persistence' => [],
                'expected' => true,
            ],
            // Content, with SPI targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => $this->getTestEvaluateContentMock(),
                'targets' => [new SPILocation(['contentId' => '24'])],
                'persistence' => [
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '42'])],
                ],
                'expected' => true,
            ],
            // VersionInfo, with API targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => $this->getTestEvaluateVersionInfoMock(),
                'targets' => [new Location(['contentInfo' => new ContentInfo(['contentTypeId' => 42])])],
                'persistence' => [],
                'expected' => true,
            ],
            // VersionInfo, with SPI targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => $this->getTestEvaluateVersionInfoMock(),
                'targets' => [new SPILocation(['contentId' => '24'])],
                'persistence' => [
                    'contentInfos' => [new SPIContentInfo(['contentTypeId' => '42'])],
                ],
                'expected' => true,
            ],
            // VersionInfo, with LocationCreateStruct targets, with access
            [
                'limitation' => new ParentContentTypeLimitation(['limitationValues' => [42]]),
                'object' => $this->getTestEvaluateVersionInfoMock(),
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
                'object' => $this->getTestEvaluateContentMock(),
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

    protected function setupContentHandlerExpectations(array $expectations): void
    {
        if (empty($expectations)) {
            return;
        }

        $this->getPersistenceMock()
            ->method('contentHandler')
            ->will(self::returnValue($this->contentHandlerMock));

        $expectedCalls = $expectations;
        $this->contentHandlerMock
            ->expects(self::exactly(count($expectedCalls)))
            ->method('loadContentInfo')
            ->willReturnCallback(static function ($contentId) use (&$expectedCalls) {
                $expected = array_shift($expectedCalls);
                self::assertSame($expected['contentId'], $contentId);

                return $expected['contentInfo'];
            });
    }

    /**
     * @dataProvider providerForTestEvaluate
     */
    public function testEvaluate(
        ParentContentTypeLimitation $limitation,
        ValueObject $object,
        $targets,
        array $persistence,
        $expected
    ) {
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
            $expectedLocationLoads = [];
            $contentHandlerExpectations = [];
            $hasLocationCreateStruct = false;

            foreach ($targets as $index => $target) {
                if ($target instanceof LocationCreateStruct) {
                    $hasLocationCreateStruct = true;
                    $expectedLocationLoads[] = [
                        'arg' => $target->parentLocationId,
                        'return' => $persistence['locations'][$index],
                    ];
                    $contentId = $persistence['locations'][$index]->contentId;
                } else {
                    $contentId = $target->contentId;
                }

                $contentHandlerExpectations[] = [
                    'contentId' => $contentId,
                    'contentInfo' => $persistence['contentInfos'][$index],
                ];
            }

            if ($hasLocationCreateStruct) {
                $this->getPersistenceMock()
                    ->expects(self::once())
                    ->method('locationHandler')
                    ->will(self::returnValue($this->locationHandlerMock));

                $this->locationHandlerMock
                    ->expects(self::exactly(count($expectedLocationLoads)))
                    ->method('load')
                    ->willReturnCallback(static function ($arg) use (&$expectedLocationLoads) {
                        $expected = array_shift($expectedLocationLoads);
                        self::assertSame($expected['arg'], $arg);

                        return $expected['return'];
                    });
            }

            $this->setupContentHandlerExpectations($contentHandlerExpectations);
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

            $loadContentInfoExpectations = [];

            foreach ($persistence['locations'] as $index => $location) {
                $loadContentInfoExpectations[$location->contentId] = $persistence['contentInfos'][$index];
            }

            foreach ($persistence['locations'] as $location) {
                if (!empty($persistence['parentLocations'][$location->parentId])) {
                    $this->locationHandlerMock
                            ->method('load')
                            ->with($location->parentId)
                            ->will(self::returnValue($persistence['parentLocations'][$location->parentId]));

                    $loadContentInfoExpectations[$location->contentId] = $persistence['parentContents'][$location->contentId];
                }
            }

            if (!empty($loadContentInfoExpectations)) {
                $this->contentHandlerMock
                    ->method('loadContentInfo')
                    ->willReturnCallback(static function ($contentId) use ($loadContentInfoExpectations) {
                        self::assertArrayHasKey($contentId, $loadContentInfoExpectations);

                        return $loadContentInfoExpectations[$contentId];
                    });
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
    public function providerForTestEvaluateInvalidArgument()
    {
        return [
            // invalid limitation
            [
                'limitation' => new ObjectStateLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new Location()],
                'persistence' => [],
            ],
            // invalid object
            [
                'limitation' => new ParentContentTypeLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => [],
                'persistence' => [],
            ],
            // invalid target when using ContentCreateStruct
            [
                'limitation' => new ParentContentTypeLimitation(),
                'object' => new ContentCreateStruct(),
                'targets' => [new Location()],
                'persistence' => [],
            ],
            // invalid target when not using ContentCreateStruct
            [
                'limitation' => new ParentContentTypeLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new ObjectStateLimitation()],
                'persistence' => [],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestEvaluateInvalidArgument
     */
    public function testEvaluateInvalidArgument(
        Limitation $limitation,
        ValueObject $object,
        $targets
    ) {
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
     * @depends testConstruct
     *
     * @param ParentContentTypeLimitationType $limitationType
     */
    public function testGetCriterionInvalidValue(ParentContentTypeLimitationType $limitationType)
    {
        $this->expectException(NotImplementedException::class);

        $limitationType->getCriterion(
            new ParentContentTypeLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @depends testConstruct
     *
     * @param ParentContentTypeLimitationType $limitationType
     */
    public function testValueSchema(ParentContentTypeLimitationType $limitationType)
    {
        self::markTestIncomplete('Method is not implemented yet: ' . __METHOD__);
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Content\Location\Handler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as SPILocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ParentDepthLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Limitation\ParentDepthLimitationType;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test Case for LimitationType.
 */
class ParentDepthLimitationTypeTest extends Base
{
    public const int EXAMPLE_CONTENT_INFO_ID = 12;

    /** @var Handler|MockObject */
    private $locationHandlerMock;

    /**
     * Setup Location Handler mock.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->locationHandlerMock = $this->createMock(SPILocationHandler::class);
    }

    /**
     * Tear down Location Handler mock.
     */
    protected function tearDown(): void
    {
        unset($this->locationHandlerMock);
        parent::tearDown();
    }

    /**
     * @return ParentDepthLimitationType
     */
    public function testConstruct()
    {
        return new ParentDepthLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public function providerForTestAcceptValue()
    {
        return [
            [new ParentDepthLimitation()],
            [new ParentDepthLimitation([])],
            [new ParentDepthLimitation(['limitationValues' => [0, 1, 2, PHP_INT_MAX]])],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValue
     *
     * @depends testConstruct
     *
     * @param ParentDepthLimitation $limitation
     * @param ParentDepthLimitationType $limitationType
     */
    public function testAcceptValue(
        ParentDepthLimitation $limitation,
        ParentDepthLimitationType $limitationType
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
            [new ParentDepthLimitation(['limitationValues' => [true]])],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValueException
     *
     * @depends testConstruct
     *
     * @param Limitation $limitation
     * @param ParentDepthLimitationType $limitationType
     */
    public function testAcceptValueException(
        Limitation $limitation,
        ParentDepthLimitationType $limitationType
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
            [new ParentDepthLimitation()],
            [new ParentDepthLimitation([])],
            [new ParentDepthLimitation(['limitationValues' => [2]])],
        ];
    }

    /**
     * @dataProvider providerForTestValidatePass
     *
     * @depends testConstruct
     *
     * @param ParentDepthLimitation $limitation
     */
    public function testValidatePass(
        ParentDepthLimitation $limitation,
        ParentDepthLimitationType $limitationType
    ) {
        $validationErrors = $limitationType->validate($limitation);
        self::assertEmpty($validationErrors);
    }

    /**
     * @depends testConstruct
     *
     * @param ParentDepthLimitationType $limitationType
     */
    public function testBuildValue(ParentDepthLimitationType $limitationType)
    {
        $expected = [2, 7];
        $value = $limitationType->buildValue($expected);

        self::assertInstanceOf(
            ParentDepthLimitation::class,
            $value
        );
        self::assertIsArray($value->limitationValues);
        self::assertEquals($expected, $value->limitationValues);
    }

    /**
     * @return array
     */
    public function providerForTestEvaluate()
    {
        // Mocks for testing Content & VersionInfo objects, should only be used once because of expect rules.
        $contentMock = $this->createMock(APIContent::class);
        $versionInfoMock = $this->createMock(APIVersionInfo::class);

        $contentMock
            ->expects(self::once())
            ->method('getVersionInfo')
            ->will(self::returnValue($versionInfoMock));

        $versionInfoMock
            ->expects(self::once())
            ->method('getContentInfo')
            ->will(self::returnValue(new ContentInfo(['published' => true])));

        $versionInfoMock2 = $this->createMock(APIVersionInfo::class);

        $versionInfoMock2
            ->expects(self::once())
            ->method('getContentInfo')
            ->will(self::returnValue(new ContentInfo(['published' => true])));

        return [
            // ContentInfo, with targets, no access
            [
                'limitation' => new ParentDepthLimitation(),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new Location()],
                'persistence' => [],
                'expected' => false,
            ],
            // ContentInfo, with targets, no access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new Location(['depth' => 55])],
                'persistence' => [],
                'expected' => false,
            ],
            // ContentInfo, with targets, with access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new Location(['depth' => 2])],
                'persistence' => [],
                'expected' => true,
            ],
            // ContentInfo, no targets, with access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo([
                    'id' => self::EXAMPLE_CONTENT_INFO_ID,
                    'published' => true,
                ]),
                'targets' => null,
                'persistence' => [new Location(['depth' => 2])],
                'expected' => true,
            ],
            // ContentInfo, no targets, no access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentInfo([
                    'id' => self::EXAMPLE_CONTENT_INFO_ID,
                    'published' => true,
                ]),
                'targets' => null,
                'persistence' => [new Location(['depth' => 55])],
                'expected' => false,
            ],
            // ContentInfo, no targets, un-published, with access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo([
                    'id' => self::EXAMPLE_CONTENT_INFO_ID,
                    'published' => false,
                ]),
                'targets' => null,
                'persistence' => [new Location(['depth' => 2])],
                'expected' => true,
            ],
            // ContentInfo, no targets, un-published, no access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentInfo([
                    'id' => self::EXAMPLE_CONTENT_INFO_ID,
                    'published' => false,
                ]),
                'targets' => null,
                'persistence' => [new Location(['depth' => 55])],
                'expected' => false,
            ],
            // Content, with targets, with access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2]]),
                'object' => $contentMock,
                'targets' => [new Location(['depth' => 2])],
                'persistence' => [],
                'expected' => true,
            ],
            // VersionInfo, with targets, with access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2]]),
                'object' => $versionInfoMock2,
                'targets' => [new Location(['depth' => 2])],
                'persistence' => [],
                'expected' => true,
            ],
            // ContentCreateStruct, no targets, no access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2]]),
                'object' => new ContentCreateStruct(),
                'targets' => [],
                'persistence' => [],
                'expected' => false,
            ],
            // ContentCreateStruct, with targets, no access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 55])],
                'persistence' => [
                    55 => new Location(['depth' => 42]),
                ],
                'expected' => false,
            ],
            // ContentCreateStruct, with targets, with access
            [
                'limitation' => new ParentDepthLimitation(['limitationValues' => [2, 42]]),
                'object' => new ContentCreateStruct(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 43])],
                'persistence' => [
                    43 => new Location(['depth' => 42]),
                ],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestEvaluate
     */
    public function testEvaluate(
        ParentDepthLimitation $limitation,
        ValueObject $object,
        $targets,
        array $persistenceLocations,
        $expected
    ) {
        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $userMock = $this->getUserMock();
        $userMock
            ->expects(self::never())
            ->method(self::anything());

        $persistenceMock = $this->getPersistenceMock();
        if (empty($persistenceLocations)) {
            $persistenceMock
                ->expects(self::never())
                ->method(self::anything());
        } elseif ($object instanceof ContentCreateStruct) {
            $this->getPersistenceMock()
                ->expects(self::once())
                ->method('locationHandler')
                ->will(self::returnValue($this->locationHandlerMock));

            foreach ($targets as $target) {
                $this->locationHandlerMock
                    ->expects(self::once())
                    ->method('load')
                    ->with($target->parentLocationId)
                    ->will(self::returnValue($persistenceLocations[$target->parentLocationId]));
            }
        } else {
            $this->getPersistenceMock()
                ->expects(self::once())
                ->method('locationHandler')
                ->will(self::returnValue($this->locationHandlerMock));

            $this->locationHandlerMock
                ->expects(self::once())
                ->method(
                    $object instanceof ContentInfo && $object->published ?
                        'loadLocationsByContent' :
                        'loadParentLocationsForDraftContent'
                )
                ->with($object->id)
                ->will(self::returnValue($persistenceLocations));
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
                'limitation' => new ParentDepthLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => [new Location()],
                'persistence' => [],
            ],
            // invalid target
            [
                'limitation' => new ParentDepthLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new ObjectStateLimitation()],
                'persistence' => [],
            ],
            // invalid target when using ContentCreateStruct
            [
                'limitation' => new ParentDepthLimitation(),
                'object' => new ContentCreateStruct(),
                'targets' => [new Location()],
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
        $targets,
        array $persistenceLocations
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

        $v = $limitationType->evaluate(
            $limitation,
            $userMock,
            $object,
            $targets
        );
        var_dump($v); // intentional, debug in case no exception above
    }
}

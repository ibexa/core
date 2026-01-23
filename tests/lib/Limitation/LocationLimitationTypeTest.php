<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Content\Location\Handler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as SPIHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LocationId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\LocationLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\LocationLimitationType;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test Case for LimitationType.
 */
class LocationLimitationTypeTest extends Base
{
    /** @var Handler|MockObject */
    private $locationHandlerMock;

    /**
     * Setup Location Handler mock.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->locationHandlerMock = $this->createMock(SPIHandler::class);
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
     * @return LocationLimitationType
     */
    public function testConstruct()
    {
        return new LocationLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public function providerForTestAcceptValue()
    {
        return [
            [new LocationLimitation()],
            [new LocationLimitation([])],
            [new LocationLimitation(['limitationValues' => [0, PHP_INT_MAX, '2', 's3fdaf32r']])],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValue
     *
     * @depends testConstruct
     *
     * @param LocationLimitation $limitation
     * @param LocationLimitationType $limitationType
     */
    public function testAcceptValue(
        LocationLimitation $limitation,
        LocationLimitationType $limitationType
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
            [new LocationLimitation(['limitationValues' => [true]])],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValueException
     *
     * @depends testConstruct
     *
     * @param Limitation $limitation
     * @param LocationLimitationType $limitationType
     */
    public function testAcceptValueException(
        Limitation $limitation,
        LocationLimitationType $limitationType
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
            [new LocationLimitation()],
            [new LocationLimitation([])],
            [new LocationLimitation(['limitationValues' => [2]])],
        ];
    }

    /**
     * @dataProvider providerForTestValidatePass
     *
     * @param LocationLimitation $limitation
     */
    public function testValidatePass(LocationLimitation $limitation)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('locationHandler')
                ->will(self::returnValue($this->locationHandlerMock));

            $expectedValues = $limitation->limitationValues;
            $this->locationHandlerMock
                ->expects(self::exactly(count($expectedValues)))
                ->method('load')
                ->willReturnCallback(static function ($value) use (&$expectedValues): void {
                    $expectedValue = array_shift($expectedValues);
                    self::assertSame($expectedValue, $value);
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
            [new LocationLimitation(), 0],
            [new LocationLimitation(['limitationValues' => [0]]), 1],
            [new LocationLimitation(['limitationValues' => [0, PHP_INT_MAX]]), 2],
        ];
    }

    /**
     * @dataProvider providerForTestValidateError
     *
     * @param LocationLimitation $limitation
     * @param int $errorCount
     */
    public function testValidateError(
        LocationLimitation $limitation,
        $errorCount
    ) {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('locationHandler')
                ->will(self::returnValue($this->locationHandlerMock));

            $expectedValues = $limitation->limitationValues;
            $this->locationHandlerMock
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
     * @param LocationLimitationType $limitationType
     */
    public function testBuildValue(LocationLimitationType $limitationType)
    {
        $expected = ['test', 'test' => 9];
        $value = $limitationType->buildValue($expected);

        self::assertInstanceOf(LocationLimitation::class, $value);
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
                'limitation' => new LocationLimitation(),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new Location(['id' => 55])],
                'persistence' => [],
                'expected' => false,
            ],
            // ContentInfo, with targets, no access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new Location(['id' => 55])],
                'persistence' => [],
                'expected' => false,
            ],
            // ContentInfo, with targets, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo(['id' => 23, 'published' => true]),
                'targets' => [new Location(['id' => 2])],
                'persistence' => [],
                'expected' => true,
            ],
            // ContentInfo, no targets, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo(['id' => 23, 'published' => true]),
                'targets' => null,
                'persistence' => [new Location(['id' => 2])],
                'expected' => true,
            ],
            // ContentInfo, no targets, no access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentInfo(['id' => 23, 'published' => true]),
                'targets' => null,
                'persistence' => [new Location(['id' => 55])],
                'expected' => false,
            ],
            // ContentInfo, no targets, un-published, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo([
                    'id' => 23,
                    'published' => false,
                    'status' => ContentInfo::STATUS_DRAFT,
                ]),
                'targets' => null,
                'persistence' => [new Location(['id' => 2])],
                'expected' => true,
            ],
            // ContentInfo, no targets, un-published, no access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentInfo([
                    'id' => 23,
                    'published' => false,
                    'status' => ContentInfo::STATUS_DRAFT,
                ]),
                'targets' => null,
                'persistence' => [new Location(['id' => 55])],
                'expected' => false,
            ],
            // Content, with targets, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => $contentMock,
                'targets' => [new Location(['id' => 2])],
                'persistence' => [],
                'expected' => true,
            ],
            // VersionInfo, with targets, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => $versionInfoMock2,
                'targets' => [new Location(['id' => 2])],
                'persistence' => [],
                'expected' => true,
            ],
            // ContentCreateStruct, no targets, no access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => new ContentCreateStruct(),
                'targets' => [],
                'persistence' => [],
                'expected' => false,
            ],
            // ContentCreateStruct, with targets, no access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 55])],
                'persistence' => [],
                'expected' => false,
            ],
            // ContentCreateStruct, with targets, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 43])],
                'persistence' => [],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestEvaluate
     */
    public function testEvaluate(
        LocationLimitation $limitation,
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
        if (empty($persistenceLocations) && $targets !== null) {
            $persistenceMock
                ->expects(self::never())
                ->method(self::anything());
        } else {
            $this->getPersistenceMock()
                ->expects(self::once())
                ->method('locationHandler')
                ->will(self::returnValue($this->locationHandlerMock));

            $this->locationHandlerMock
                ->expects(self::once())
                ->method($object instanceof ContentInfo && $object->published ? 'loadLocationsByContent' : 'loadParentLocationsForDraftContent')
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
                'limitation' => new LocationLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => [new Location()],
                'persistence' => [],
            ],
            // invalid target
            [
                'limitation' => new LocationLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new ObjectStateLimitation()],
                'persistence' => [],
            ],
            // invalid target when using ContentCreateStruct
            [
                'limitation' => new LocationLimitation(),
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

    /**
     * @depends testConstruct
     *
     * @param LocationLimitationType $limitationType
     */
    public function testGetCriterionInvalidValue(LocationLimitationType $limitationType)
    {
        $this->expectException(\RuntimeException::class);

        $limitationType->getCriterion(
            new LocationLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @depends testConstruct
     *
     * @param LocationLimitationType $limitationType
     */
    public function testGetCriterionSingleValue(LocationLimitationType $limitationType)
    {
        $criterion = $limitationType->getCriterion(
            new LocationLimitation(['limitationValues' => [9]]),
            $this->getUserMock()
        );

        self::assertInstanceOf(LocationId::class, $criterion);
        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::EQ, $criterion->operator);
        self::assertEquals([9], $criterion->value);
    }

    /**
     * @depends testConstruct
     *
     * @param LocationLimitationType $limitationType
     */
    public function testGetCriterionMultipleValues(LocationLimitationType $limitationType)
    {
        $criterion = $limitationType->getCriterion(
            new LocationLimitation(['limitationValues' => [9, 55]]),
            $this->getUserMock()
        );

        self::assertInstanceOf(LocationId::class, $criterion);
        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::IN, $criterion->operator);
        self::assertEquals([9, 55], $criterion->value);
    }

    /**
     * @depends testConstruct
     *
     * @param LocationLimitationType $limitationType
     */
    public function testValueSchema(LocationLimitationType $limitationType)
    {
        self::assertEquals(
            LocationLimitationType::VALUE_SCHEMA_LOCATION_ID,
            $limitationType->valueSchema()
        );
    }
}

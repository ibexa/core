<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as SPIHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LocationId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\LocationLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\LocationLimitationType;
use Ibexa\Core\Repository\Values\Content\Content as CoreContent;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo as CoreVersionInfo;

/**
 * Test Case for LimitationType.
 */
class LocationLimitationTypeTest extends Base
{
    /** @var \Ibexa\Contracts\Core\Persistence\Content\Location\Handler|\PHPUnit\Framework\MockObject\MockObject */
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
     * @return \Ibexa\Core\Limitation\LocationLimitationType
     */
    public function testConstruct()
    {
        return new LocationLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValue()
    {
        return [
            [new LocationLimitation()],
            [new LocationLimitation([])],
            [new LocationLimitation(['limitationValues' => [0, PHP_INT_MAX, '2', 's3fdaf32r']])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\LocationLimitation $limitation
     * @param \Ibexa\Core\Limitation\LocationLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestAcceptValue')]
    public function testAcceptValue(LocationLimitation $limitation, LocationLimitationType $limitationType)
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
            [new LocationLimitation(['limitationValues' => [true]])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Core\Limitation\LocationLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestAcceptValueException')]
    public function testAcceptValueException(Limitation $limitation, LocationLimitationType $limitationType)
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
            [new LocationLimitation()],
            [new LocationLimitation([])],
            [new LocationLimitation(['limitationValues' => [2]])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\LocationLimitation $limitation
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestValidatePass')]
    public function testValidatePass(LocationLimitation $limitation)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('locationHandler')
                ->will(self::returnValue($this->locationHandlerMock));

            $limitationValues = $limitation->limitationValues;
            $matcher = self::exactly(count($limitationValues));
            $this->locationHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function ($actualValue, ?array $translations = null, bool $useAlwaysAvailable = true) use ($matcher, $limitationValues): void {
                    self::assertSame($limitationValues[$matcher->numberOfInvocations() - 1], $actualValue);
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
            [new LocationLimitation(), 0],
            [new LocationLimitation(['limitationValues' => [0]]), 1],
            [new LocationLimitation(['limitationValues' => [0, PHP_INT_MAX]]), 2],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\LocationLimitation $limitation
     * @param int $errorCount
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestValidateError')]
    public function testValidateError(LocationLimitation $limitation, $errorCount)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('locationHandler')
                ->will(self::returnValue($this->locationHandlerMock));

            $limitationValues = $limitation->limitationValues;
            $matcher = self::exactly(count($limitationValues));
            $this->locationHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function ($actualValue, ?array $translations = null, bool $useAlwaysAvailable = true) use ($matcher, $limitationValues): void {
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
     * @param \Ibexa\Core\Limitation\LocationLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
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
    public static function providerForTestEvaluate()
    {
        $contentMock = new CoreContent([
            'versionInfo' => new CoreVersionInfo([
                'contentInfo' => new ContentInfo(['published' => true]),
            ]),
        ]);

        $versionInfoMock2 = new CoreVersionInfo([
            'contentInfo' => new ContentInfo(['published' => true]),
        ]);

        return [
            // ContentInfo, with targets, no access
            [
                'limitation' => new LocationLimitation(),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new Location(['id' => 55])],
                'persistenceLocations' => [],
                'expected' => false,
            ],
            // ContentInfo, with targets, no access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new Location(['id' => 55])],
                'persistenceLocations' => [],
                'expected' => false,
            ],
            // ContentInfo, with targets, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo(['id' => 23, 'published' => true]),
                'targets' => [new Location(['id' => 2])],
                'persistenceLocations' => [],
                'expected' => true,
            ],
            // ContentInfo, no targets, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo(['id' => 23, 'published' => true]),
                'targets' => null,
                'persistenceLocations' => [new Location(['id' => 2])],
                'expected' => true,
            ],
            // ContentInfo, no targets, no access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentInfo(['id' => 23, 'published' => true]),
                'targets' => null,
                'persistenceLocations' => [new Location(['id' => 55])],
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
                'persistenceLocations' => [new Location(['id' => 2])],
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
                'persistenceLocations' => [new Location(['id' => 55])],
                'expected' => false,
            ],
            // Content, with targets, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => $contentMock,
                'targets' => [new Location(['id' => 2])],
                'persistenceLocations' => [],
                'expected' => true,
            ],
            // VersionInfo, with targets, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => $versionInfoMock2,
                'targets' => [new Location(['id' => 2])],
                'persistenceLocations' => [],
                'expected' => true,
            ],
            // ContentCreateStruct, no targets, no access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2]]),
                'object' => new ContentCreateStruct(),
                'targets' => [],
                'persistenceLocations' => [],
                'expected' => false,
            ],
            // ContentCreateStruct, with targets, no access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 55])],
                'persistenceLocations' => [],
                'expected' => false,
            ],
            // ContentCreateStruct, with targets, with access
            [
                'limitation' => new LocationLimitation(['limitationValues' => [2, 43]]),
                'object' => new ContentCreateStruct(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 43])],
                'persistenceLocations' => [],
                'expected' => true,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestEvaluate')]
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
    public static function providerForTestEvaluateInvalidArgument()
    {
        return [
            // invalid limitation
            [
                'limitation' => new ObjectStateLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new Location()],
                'persistenceLocations' => [],
            ],
            // invalid object
            [
                'limitation' => new LocationLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => [new Location()],
                'persistenceLocations' => [],
            ],
            // invalid target
            [
                'limitation' => new LocationLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new ObjectStateLimitation()],
                'persistenceLocations' => [],
            ],
            // invalid target when using ContentCreateStruct
            [
                'limitation' => new LocationLimitation(),
                'object' => new ContentCreateStruct(),
                'targets' => [new Location()],
                'persistenceLocations' => [],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestEvaluateInvalidArgument')]
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
     * @param \Ibexa\Core\Limitation\LocationLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testGetCriterionInvalidValue(LocationLimitationType $limitationType)
    {
        $this->expectException(\RuntimeException::class);

        $limitationType->getCriterion(
            new LocationLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @param \Ibexa\Core\Limitation\LocationLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
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
     * @param \Ibexa\Core\Limitation\LocationLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
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
     * @param \Ibexa\Core\Limitation\LocationLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testValueSchema(LocationLimitationType $limitationType)
    {
        self::assertEquals(
            LocationLimitationType::VALUE_SCHEMA_LOCATION_ID,
            $limitationType->valueSchema()
        );
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Type as LimitationType;
use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as SPILocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Subtree;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\SubtreeLimitationType;
use Ibexa\Core\Repository\Values\Content\Content as CoreContent;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\Query\Criterion\PermissionSubtree;
use Ibexa\Core\Repository\Values\Content\VersionInfo as CoreVersionInfo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Test Case for LimitationType.
 */
class SubtreeLimitationTypeTest extends Base
{
    public const int EXAMPLE_CONTENT_INFO_ID = 12312;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Location\Handler|\PHPUnit\Framework\MockObject\MockObject */
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
     * @return \Ibexa\Core\Limitation\SubtreeLimitationType
     */
    public function testConstruct(): SubtreeLimitationType
    {
        return new SubtreeLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValue(): array
    {
        return [
            [new SubtreeLimitation()],
            [new SubtreeLimitation([])],
            [new SubtreeLimitation(['limitationValues' => ['', 'true', '2', 's3fdaf32r']])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation $limitation
     * @param \Ibexa\Core\Limitation\SubtreeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    #[DataProvider('providerForTestAcceptValue')]
    public function testAcceptValue(SubtreeLimitation $limitation, SubtreeLimitationType $limitationType): void
    {
        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValueException(): array
    {
        return [
            [new ObjectStateLimitation()],
            [new SubtreeLimitation(['limitationValues' => [true]])],
            [new SubtreeLimitation(['limitationValues' => [1]])],
            [new SubtreeLimitation(['limitationValues' => [0]])],
            [new SubtreeLimitation(['limitationValues' => '/1/2/'])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Core\Limitation\SubtreeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    #[DataProvider('providerForTestAcceptValueException')]
    public function testAcceptValueException(Limitation $limitation, SubtreeLimitationType $limitationType): void
    {
        $this->expectException(InvalidArgumentException::class);

        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public static function providerForTestValidatePass(): array
    {
        return [
            [new SubtreeLimitation()],
            [new SubtreeLimitation([])],
            [new SubtreeLimitation(['limitationValues' => ['/1/2/']])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation $limitation
     */
    #[DataProvider('providerForTestValidatePass')]
    public function testValidatePass(SubtreeLimitation $limitation): void
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('locationHandler')
                ->will(self::returnValue($this->locationHandlerMock));

            $loadArguments = [];
            $loadReturnValues = [];
            foreach ($limitation->limitationValues as $key => $value) {
                $pathArray = explode('/', trim($value, '/'));
                $loadArguments[$key] = end($pathArray);
                $loadReturnValues[$key] = new SPILocation(['pathString' => $value]);
            }

            $matcher = self::exactly(count($limitation->limitationValues));
            $this->locationHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function (...$parameters) use ($matcher, $loadArguments, $loadReturnValues): SPILocation {
                    self::assertSame($loadArguments[$matcher->numberOfInvocations() - 1], $parameters[0]);

                    return $loadReturnValues[$matcher->numberOfInvocations() - 1];
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
    public static function providerForTestValidateError(): array
    {
        return [
            [new SubtreeLimitation(), 0],
            [new SubtreeLimitation(['limitationValues' => ['/1/777/']]), 1],
            [new SubtreeLimitation(['limitationValues' => ['/1/888/', '/1/999/']]), 2],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation $limitation
     * @param int $errorCount
     */
    #[DataProvider('providerForTestValidateError')]
    public function testValidateError(SubtreeLimitation $limitation, $errorCount): void
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('locationHandler')
                ->will(self::returnValue($this->locationHandlerMock));

            $loadArguments = [];
            $loadExceptions = [];
            foreach ($limitation->limitationValues as $key => $value) {
                $pathArray = explode('/', trim($value, '/'));
                $loadArguments[$key] = end($pathArray);
                $loadExceptions[$key] = new NotFoundException('location', $value);
            }

            $matcher = self::exactly(count($limitation->limitationValues));
            $this->locationHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function (...$parameters) use ($matcher, $loadArguments, $loadExceptions): void {
                    self::assertSame($loadArguments[$matcher->numberOfInvocations() - 1], $parameters[0]);

                    throw $loadExceptions[$matcher->numberOfInvocations() - 1];
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

    public function testValidateErrorWrongPath(): void
    {
        $limitation = new SubtreeLimitation(['limitationValues' => ['/1/2/42/']]);

        $this->getPersistenceMock()
            ->expects(self::any())
            ->method('locationHandler')
            ->will(self::returnValue($this->locationHandlerMock));

        $loadArguments = [];
        $loadReturnValues = [];
        foreach ($limitation->limitationValues as $key => $value) {
            $pathArray = explode('/', trim($value, '/'));
            $loadArguments[$key] = end($pathArray);
            $loadReturnValues[$key] = new SPILocation(['pathString' => '/1/5/42']);
        }

        $matcher = self::exactly(count($limitation->limitationValues));
        $this->locationHandlerMock
            ->expects($matcher)
            ->method('load')
            ->willReturnCallback(static function (...$parameters) use ($matcher, $loadArguments, $loadReturnValues): SPILocation {
                self::assertSame($loadArguments[$matcher->numberOfInvocations() - 1], $parameters[0]);

                return $loadReturnValues[$matcher->numberOfInvocations() - 1];
            });

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $validationErrors = $limitationType->validate($limitation);
        self::assertCount(1, $validationErrors);
    }

    /**
     * @param \Ibexa\Core\Limitation\SubtreeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testBuildValue(SubtreeLimitationType $limitationType): void
    {
        $expected = ['test', 'test' => '/1/999/'];
        $value = $limitationType->buildValue($expected);

        self::assertInstanceOf(SubtreeLimitation::class, $value);
        self::assertIsArray($value->limitationValues);
        self::assertEquals($expected, $value->limitationValues);
    }

    /**
     * @return array
     */
    public static function providerForTestEvaluate(): array
    {
        // Real Content & VersionInfo objects, avoiding mocks since providers must be static.
        $content = new CoreContent([
            'versionInfo' => new CoreVersionInfo([
                'contentInfo' => new ContentInfo(['published' => true, 'status' => ContentInfo::STATUS_PUBLISHED]),
            ]),
        ]);

        $versionInfo = new CoreVersionInfo([
            'contentInfo' => new ContentInfo(['published' => true, 'status' => ContentInfo::STATUS_PUBLISHED]),
        ]);

        return [
            // ContentInfo, with targets, no access
            [
                'limitation' => new SubtreeLimitation(),
                'object' => new ContentInfo(['published' => true, 'status' => ContentInfo::STATUS_PUBLISHED]),
                'targets' => [new Location()],
                'persistenceLocations' => [],
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // ContentInfo, with targets, no access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/']]),
                'object' => new ContentInfo(['published' => true, 'status' => ContentInfo::STATUS_PUBLISHED]),
                'targets' => [new Location(['pathString' => '/1/55/'])],
                'persistenceLocations' => [],
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // ContentInfo, with targets, with access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/']]),
                'object' => new ContentInfo(['published' => true, 'status' => ContentInfo::STATUS_PUBLISHED]),
                'targets' => [new Location(['pathString' => '/1/2/'])],
                'persistenceLocations' => [],
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // ContentInfo, no targets, with access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/']]),
                'object' => new ContentInfo([
                    'id' => self::EXAMPLE_CONTENT_INFO_ID,
                    'published' => true,
                    'status' => ContentInfo::STATUS_PUBLISHED,
                ]),
                'targets' => null,
                'persistenceLocations' => [new Location(['pathString' => '/1/2/'])],
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // ContentInfo, no targets, no access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/', '/1/43/']]),
                'object' => new ContentInfo([
                    'id' => self::EXAMPLE_CONTENT_INFO_ID,
                    'published' => true,
                    'status' => ContentInfo::STATUS_PUBLISHED,
                ]),
                'targets' => null,
                'persistenceLocations' => [new Location(['pathString' => '/1/55/'])],
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // ContentInfo, no targets, un-published, with access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/']]),
                'object' => new ContentInfo([
                    'id' => self::EXAMPLE_CONTENT_INFO_ID,
                    'published' => false,
                    'status' => ContentInfo::STATUS_DRAFT,
                ]),
                'targets' => null,
                'persistenceLocations' => [new Location(['pathString' => '/1/2/'])],
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // ContentInfo, no targets, un-published, no access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/', '/1/43/']]),
                'object' => new ContentInfo([
                    'id' => self::EXAMPLE_CONTENT_INFO_ID,
                    'published' => false,
                    'status' => ContentInfo::STATUS_DRAFT,
                ]),
                'targets' => null,
                'persistenceLocations' => [new Location(['pathString' => '/1/55/'])],
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // Content, with targets, with access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/']]),
                'object' => $content,
                'targets' => [new Location(['pathString' => '/1/2/'])],
                'persistenceLocations' => [],
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // VersionInfo, with targets, with access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/']]),
                'object' => $versionInfo,
                'targets' => [new Location(['pathString' => '/1/2/'])],
                'persistenceLocations' => [],
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // ContentCreateStruct, no targets, no access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/']]),
                'object' => new ContentCreateStruct(),
                'targets' => [],
                'persistenceLocations' => [],
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // ContentCreateStruct, with targets, no access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/', '/1/43/']]),
                'object' => new ContentCreateStruct(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 55])],
                'persistenceLocations' => [new Location(['pathString' => '/1/55/'])],
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // ContentCreateStruct, with targets, with access
            [
                'limitation' => new SubtreeLimitation(['limitationValues' => ['/1/2/', '/1/43/']]),
                'object' => new ContentCreateStruct(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 43])],
                'persistenceLocations' => [new Location(['pathString' => '/1/43/'])],
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // invalid object
            [
                'limitation' => new SubtreeLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 43])],
                'persistenceLocations' => [],
                'expected' => LimitationType::ACCESS_ABSTAIN,
            ],
            // invalid target
            [
                'limitation' => new SubtreeLimitation(),
                'object' => new ContentInfo(['published' => true, 'status' => ContentInfo::STATUS_PUBLISHED]),
                'targets' => [new ObjectStateLimitation()],
                'persistenceLocations' => [],
                'expected' => LimitationType::ACCESS_ABSTAIN,
            ],
        ];
    }

    #[DataProvider('providerForTestEvaluate')]
    public function testEvaluate(
        SubtreeLimitation $limitation,
        ValueObject $object,
        $targets,
        array $persistenceLocations,
        $expected
    ): void {
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
        } elseif ($object instanceof ContentCreateStruct) {
            $this->getPersistenceMock()
                ->expects(self::exactly(count($targets)))
                ->method('locationHandler')
                ->willReturn($this->locationHandlerMock);

            $loadArguments = [];
            $loadReturnValues = [];
            foreach ((array)$targets as $key => $target) {
                $loadArguments[$key] = $target->parentLocationId;
                $loadReturnValues[$key] = $persistenceLocations[$key];
            }

            $matcher = self::exactly(count($targets));
            $this->locationHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function (...$parameters) use ($matcher, $loadArguments, $loadReturnValues): Location {
                    self::assertSame($loadArguments[$matcher->numberOfInvocations() - 1], $parameters[0]);

                    return $loadReturnValues[$matcher->numberOfInvocations() - 1];
                });
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

        self::assertEquals($expected, $value);
    }

    /**
     * @return array
     */
    public static function providerForTestEvaluateInvalidArgument(): array
    {
        return [
            // invalid limitation
            [
                'limitation' => new ObjectStateLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new Location()],
                'persistenceLocations' => [],
            ],
            // invalid target when using ContentCreateStruct
            [
                'limitation' => new SubtreeLimitation(),
                'object' => new ContentCreateStruct(),
                'targets' => [new Location()],
                'persistenceLocations' => [],
            ],
        ];
    }

    #[DataProvider('providerForTestEvaluateInvalidArgument')]
    public function testEvaluateInvalidArgument(
        Limitation $limitation,
        ValueObject $object,
        $targets,
        array $persistenceLocations
    ): void {
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
     * @param \Ibexa\Core\Limitation\SubtreeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testGetCriterionInvalidValue(SubtreeLimitationType $limitationType): void
    {
        $this->expectException(\RuntimeException::class);

        $limitationType->getCriterion(
            new SubtreeLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @param \Ibexa\Core\Limitation\SubtreeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testGetCriterionSingleValue(SubtreeLimitationType $limitationType): void
    {
        $criterion = $limitationType->getCriterion(
            new SubtreeLimitation(['limitationValues' => ['/1/9/']]),
            $this->getUserMock()
        );

        // Assert that $criterion is instance of API type (for Solr/ES), and internal type(optimization for SQL engines)
        self::assertInstanceOf(Subtree::class, $criterion);
        self::assertInstanceOf(PermissionSubtree::class, $criterion);
        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::EQ, $criterion->operator);
        self::assertEquals(['/1/9/'], $criterion->value);
    }

    /**
     * @param \Ibexa\Core\Limitation\SubtreeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testGetCriterionMultipleValues(SubtreeLimitationType $limitationType): void
    {
        $criterion = $limitationType->getCriterion(
            new SubtreeLimitation(['limitationValues' => ['/1/9/', '/1/55/']]),
            $this->getUserMock()
        );

        // Assert that $criterion is instance of API type (for Solr/ES), and internal type(optimization for SQL engines)
        self::assertInstanceOf(Subtree::class, $criterion);
        self::assertInstanceOf(PermissionSubtree::class, $criterion);
        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::IN, $criterion->operator);
        self::assertEquals(['/1/9/', '/1/55/'], $criterion->value);
    }

    /**
     * @param \Ibexa\Core\Limitation\SubtreeLimitationType $limitationType
     */
    #[Depends('testConstruct')]
    public function testValueSchema(SubtreeLimitationType $limitationType): void
    {
        self::assertEquals(
            SubtreeLimitationType::VALUE_SCHEMA_LOCATION_PATH,
            $limitationType->valueSchema()
        );
    }
}

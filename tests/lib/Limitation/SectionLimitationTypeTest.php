<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Limitation\Type as LimitationType;
use Ibexa\Contracts\Core\Persistence\Content\Section as SPISection;
use Ibexa\Contracts\Core\Persistence\Content\Section\Handler as SPISectionHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\SectionId;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\SectionLimitationType;
use Ibexa\Core\Repository\Values\Content\Content as CoreContent;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo as CoreVersionInfo;

/**
 * Test Case for LimitationType.
 */
class SectionLimitationTypeTest extends Base
{
    /** @var \Ibexa\Contracts\Core\Persistence\Content\Section\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $sectionHandlerMock;

    /**
     * Setup Location Handler mock.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->sectionHandlerMock = $this->createMock(SPISectionHandler::class);
    }

    /**
     * Tear down Location Handler mock.
     */
    protected function tearDown(): void
    {
        unset($this->sectionHandlerMock);
        parent::tearDown();
    }

    /**
     * @return \Ibexa\Core\Limitation\SectionLimitationType
     */
    public function testConstruct()
    {
        return new SectionLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public static function providerForTestAcceptValue()
    {
        return [
            [new SectionLimitation()],
            [new SectionLimitation([])],
            [new SectionLimitation(['limitationValues' => ['', 'true', '2', 's3fdaf32r']])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation $limitation
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestAcceptValue')]
    public function testAcceptValue(SectionLimitation $limitation, SectionLimitationType $limitationType)
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
            [new SectionLimitation(['limitationValues' => [true]])],
            [new SectionLimitation(['limitationValues' => [new \stdClass()]])],
            [new SectionLimitation(['limitationValues' => [null]])],
            [new SectionLimitation(['limitationValues' => '/1/2/'])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestAcceptValueException')]
    public function testAcceptValueException(Limitation $limitation, SectionLimitationType $limitationType)
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
            [new SectionLimitation()],
            [new SectionLimitation([])],
            [new SectionLimitation(['limitationValues' => ['1']])],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation $limitation
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestValidatePass')]
    public function testValidatePass(SectionLimitation $limitation)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('sectionHandler')
                ->will(self::returnValue($this->sectionHandlerMock));

            $limitationValues = $limitation->limitationValues;
            $matcher = self::exactly(count($limitationValues));
            $this->sectionHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function ($actualValue) use ($matcher, $limitationValues): SPISection {
                    $value = $limitationValues[$matcher->numberOfInvocations() - 1];
                    self::assertSame($value, $actualValue);

                    return new SPISection(['id' => $value]);
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
            [new SectionLimitation(), 0],
            [new SectionLimitation(['limitationValues' => ['777']]), 1],
            [new SectionLimitation(['limitationValues' => ['888', '999']]), 2],
        ];
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation $limitation
     * @param int $errorCount
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestValidateError')]
    public function testValidateError(SectionLimitation $limitation, $errorCount)
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->expects(self::any())
                ->method('sectionHandler')
                ->will(self::returnValue($this->sectionHandlerMock));

            $limitationValues = $limitation->limitationValues;
            $matcher = self::exactly(count($limitationValues));
            $this->sectionHandlerMock
                ->expects($matcher)
                ->method('load')
                ->willReturnCallback(static function ($actualValue) use ($matcher, $limitationValues): void {
                    $value = $limitationValues[$matcher->numberOfInvocations() - 1];
                    self::assertSame($value, $actualValue);

                    throw new NotFoundException('Section', $value);
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
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testBuildValue(SectionLimitationType $limitationType)
    {
        $expected = ['test', 'test' => '33'];
        $value = $limitationType->buildValue($expected);

        self::assertInstanceOf(SectionLimitation::class, $value);
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
                'contentInfo' => new ContentInfo(['sectionId' => 2]),
            ]),
        ]);

        $versionInfoMock2 = new CoreVersionInfo([
            'contentInfo' => new ContentInfo(['sectionId' => 2]),
        ]);

        return [
            // ContentInfo, with targets, no access
            [
                'limitation' => new SectionLimitation(),
                'object' => new ContentInfo(['sectionId' => 55]),
                'targets' => [new Location()],
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // ContentInfo, with targets, no access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2']]),
                'object' => new ContentInfo(['sectionId' => 55]),
                'targets' => [new Location(['pathString' => '/1/55'])],
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // ContentInfo, with targets, with access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2']]),
                'object' => new ContentInfo(['sectionId' => 2]),
                'targets' => [new Location(['pathString' => '/1/2/'])],
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // ContentInfo, no targets, with access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2']]),
                'object' => new ContentInfo(['sectionId' => 2]),
                'targets' => null,
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // ContentInfo, no targets, no access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2', '43']]),
                'object' => new ContentInfo(['sectionId' => 55]),
                'targets' => null,
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // ContentInfo, no targets, un-published, with access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2']]),
                'object' => new ContentInfo(['published' => false, 'sectionId' => 2]),
                'targets' => null,
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // ContentInfo, no targets, un-published, no access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2', '43']]),
                'object' => new ContentInfo(['published' => false, 'sectionId' => 55]),
                'targets' => null,
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // Content, with targets, with access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2']]),
                'object' => $contentMock,
                'targets' => [new Location(['pathString' => '/1/2/'])],
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // VersionInfo, with targets, with access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2']]),
                'object' => $versionInfoMock2,
                'targets' => [new Location(['pathString' => '/1/2/'])],
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // ContentCreateStruct, no targets, no access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2']]),
                'object' => new ContentCreateStruct(),
                'targets' => [],
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // ContentCreateStruct, with targets, no access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2', '43']]),
                'object' => new ContentCreateStruct(['sectionId' => 55]),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 55])],
                'expected' => LimitationType::ACCESS_DENIED,
            ],
            // ContentCreateStruct, with targets, with access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2', '43']]),
                'object' => new ContentCreateStruct(['sectionId' => 43]),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 55])],
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // invalid object
            [
                'limitation' => new SectionLimitation(),
                'object' => new ObjectStateLimitation(),
                'targets' => [new LocationCreateStruct(['parentLocationId' => 43])],
                'expected' => LimitationType::ACCESS_ABSTAIN,
            ],
            // invalid target
            [
                'limitation' => new SectionLimitation(),
                'object' => new ContentInfo(['published' => true]),
                'targets' => [new ObjectStateLimitation()],
                'expected' => LimitationType::ACCESS_ABSTAIN,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestEvaluate')]
    public function testEvaluate(
        SectionLimitation $limitation,
        ValueObject $object,
        $targets,
        $expected
    ) {
        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $userMock = $this->getUserMock();
        $userMock
            ->expects(self::never())
            ->method(self::anything());

        $this->getPersistenceMock()
            ->expects(self::never())
            ->method(self::anything());

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
    public static function providerForTestEvaluateInvalidArgument()
    {
        return [
            // invalid limitation
            [
                'limitation' => new ObjectStateLimitation(),
                'object' => new ContentInfo(),
                'targets' => [new Location()],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestEvaluateInvalidArgument')]
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

        $this->getPersistenceMock()
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
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testGetCriterionInvalidValue(SectionLimitationType $limitationType)
    {
        $this->expectException(\RuntimeException::class);

        $limitationType->getCriterion(
            new SectionLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testGetCriterionSingleValue(SectionLimitationType $limitationType)
    {
        $criterion = $limitationType->getCriterion(
            new SectionLimitation(['limitationValues' => ['9']]),
            $this->getUserMock()
        );

        self::assertInstanceOf(SectionId::class, $criterion);
        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::EQ, $criterion->operator);
        self::assertEquals(['9'], $criterion->value);
    }

    /**
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testGetCriterionMultipleValues(SectionLimitationType $limitationType)
    {
        $criterion = $limitationType->getCriterion(
            new SectionLimitation(['limitationValues' => ['9', '55']]),
            $this->getUserMock()
        );

        self::assertInstanceOf(SectionId::class, $criterion);
        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::IN, $criterion->operator);
        self::assertEquals(['9', '55'], $criterion->value);
    }

    /**
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    #[\PHPUnit\Framework\Attributes\Depends('testConstruct')]
    public function testValueSchema(SectionLimitationType $limitationType)
    {
        $this->expectException(NotImplementedException::class);

        $limitationType->valueSchema();
    }
}

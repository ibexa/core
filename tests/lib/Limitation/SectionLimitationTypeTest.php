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
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Limitation\SectionLimitationType;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use stdClass;

/**
 * Test Case for LimitationType.
 */
class SectionLimitationTypeTest extends Base
{
    private SPISectionHandler & MockObject $sectionHandlerMock;

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
    public function testConstruct(): SectionLimitationType
    {
        return new SectionLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public function providerForTestAcceptValue(): array
    {
        return [
            [new SectionLimitation()],
            [new SectionLimitation([])],
            [new SectionLimitation(['limitationValues' => ['', 'true', '2', 's3fdaf32r']])],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValue
     *
     * @depends testConstruct
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation $limitation
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testAcceptValue(SectionLimitation $limitation, SectionLimitationType $limitationType): void
    {
        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public function providerForTestAcceptValueException(): array
    {
        return [
            [new ObjectStateLimitation()],
            [new SectionLimitation(['limitationValues' => [true]])],
            [new SectionLimitation(['limitationValues' => [new stdClass()]])],
            [new SectionLimitation(['limitationValues' => [null]])],
            [new SectionLimitation(['limitationValues' => '/1/2/'])],
        ];
    }

    /**
     * @dataProvider providerForTestAcceptValueException
     *
     * @depends testConstruct
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitation
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    public function testAcceptValueException(Limitation $limitation, SectionLimitationType $limitationType): void
    {
        $this->expectException(InvalidArgumentException::class);

        $limitationType->acceptValue($limitation);
    }

    /**
     * @return array
     */
    public function providerForTestValidatePass(): array
    {
        return [
            [new SectionLimitation()],
            [new SectionLimitation([])],
            [new SectionLimitation(['limitationValues' => ['1']])],
        ];
    }

    /**
     * @dataProvider providerForTestValidatePass
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation $limitation
     */
    public function testValidatePass(SectionLimitation $limitation): void
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->method('sectionHandler')
                ->willReturn($this->sectionHandlerMock);

            foreach ($limitation->limitationValues as $key => $value) {
                $this->sectionHandlerMock
                    ->expects(self::at($key))
                    ->method('load')
                    ->with($value)
                    ->willReturn(
                        new SPISection(['id' => $value])
                    );
            }
        }

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $validationErrors = $limitationType->validate($limitation);
        self::assertEmpty($validationErrors);
    }

    /**
     * @return array
     */
    public function providerForTestValidateError(): array
    {
        return [
            [new SectionLimitation(), 0],
            [new SectionLimitation(['limitationValues' => ['777']]), 1],
            [new SectionLimitation(['limitationValues' => ['888', '999']]), 2],
        ];
    }

    /**
     * @dataProvider providerForTestValidateError
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation $limitation
     * @param int $errorCount
     */
    public function testValidateError(SectionLimitation $limitation, int $errorCount): void
    {
        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                ->method('sectionHandler')
                ->willReturn($this->sectionHandlerMock);

            foreach ($limitation->limitationValues as $key => $value) {
                $this->sectionHandlerMock
                    ->expects(self::at($key))
                    ->method('load')
                    ->with($value)
                    ->willThrowException(new NotFoundException('Section', $value));
            }
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
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    public function testBuildValue(SectionLimitationType $limitationType): void
    {
        $expected = ['test', 'test' => '33'];
        $value = $limitationType->buildValue($expected);

        self::assertIsArray($value->limitationValues);
        self::assertEquals($expected, $value->limitationValues);
    }

    /**
     * @phpstan-return list<array{
     *      limitation: \Ibexa\Contracts\Core\Repository\Values\User\Limitation,
     *      object: \Ibexa\Contracts\Core\Repository\Values\ValueObject,
     *      targets: null|array<\Ibexa\Contracts\Core\Repository\Values\ValueObject>,
     *      expected: \Ibexa\Contracts\Core\Limitation\Type::ACCESS_*
     * }>
     */
    public function providerForTestEvaluate(): array
    {
        // Mocks for testing Content & VersionInfo objects should only be used once because of expect rules.
        $contentMock = $this->createMock(APIContent::class);
        $versionInfoMock = $this->createMock(APIVersionInfo::class);

        $contentMock
            ->expects(self::once())
            ->method('getVersionInfo')
            ->willReturn($versionInfoMock);

        $versionInfoMock
            ->expects(self::once())
            ->method('getContentInfo')
            ->willReturn(new ContentInfo(['sectionId' => 2]));

        $versionInfoMock2 = $this->createMock(APIVersionInfo::class);

        $versionInfoMock2
            ->expects(self::once())
            ->method('getContentInfo')
            ->willReturn(new ContentInfo(['sectionId' => 2]));

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
            // ContentInfo, no targets, unpublished, with access
            [
                'limitation' => new SectionLimitation(['limitationValues' => ['2']]),
                'object' => new ContentInfo(['published' => false, 'sectionId' => 2]),
                'targets' => null,
                'expected' => LimitationType::ACCESS_GRANTED,
            ],
            // ContentInfo, no targets, unpublished, no access
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

    /**
     * @dataProvider providerForTestEvaluate
     */
    public function testEvaluate(
        SectionLimitation $limitation,
        ValueObject $object,
        ?array $targets,
        bool|null $expected
    ): void {
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
     * @phpstan-return list<array{
     *     limitation: \Ibexa\Contracts\Core\Repository\Values\User\Limitation,
     *     object: \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo,
     *     targets: null|array<\Ibexa\Contracts\Core\Repository\Values\ValueObject>
     * }>
     */
    public function providerForTestEvaluateInvalidArgument(): array
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

    /**
     * @dataProvider providerForTestEvaluateInvalidArgument
     *
     * @param array<\Ibexa\Contracts\Core\Repository\Values\ValueObject>|null $targets
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testEvaluateInvalidArgument(
        Limitation $limitation,
        ValueObject $object,
        ?array $targets
    ): void {
        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $userMock = $this->getUserMock();
        $userMock
            ->expects(self::never())
            ->method(self::anything());

        $this->getPersistenceMock()
            ->expects(self::never())
            ->method(self::anything());

        $this->expectException(InvalidArgumentException::class);
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
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    public function testGetCriterionInvalidValue(SectionLimitationType $limitationType): void
    {
        $this->expectException(RuntimeException::class);

        $limitationType->getCriterion(
            new SectionLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @depends testConstruct
     *
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    public function testGetCriterionSingleValue(SectionLimitationType $limitationType): void
    {
        $criterion = $limitationType->getCriterion(
            new SectionLimitation(['limitationValues' => ['9']]),
            $this->getUserMock()
        );

        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::EQ, $criterion->operator);
        self::assertEquals(['9'], $criterion->value);
    }

    /**
     * @depends testConstruct
     *
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    public function testGetCriterionMultipleValues(SectionLimitationType $limitationType): void
    {
        $criterion = $limitationType->getCriterion(
            new SectionLimitation(['limitationValues' => ['9', '55']]),
            $this->getUserMock()
        );

        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::IN, $criterion->operator);
        self::assertEquals(['9', '55'], $criterion->value);
    }

    /**
     * @depends testConstruct
     *
     * @param \Ibexa\Core\Limitation\SectionLimitationType $limitationType
     */
    public function testValueSchema(SectionLimitationType $limitationType): void
    {
        $this->expectException(NotImplementedException::class);

        $limitationType->valueSchema();
    }
}

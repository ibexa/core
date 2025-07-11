<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Content\ObjectState;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Handler as SPIHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ObjectStateId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\Limitation\ObjectStateLimitationType;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;

/**
 * Test Case for LimitationType.
 */
class ObjectStateLimitationTypeTest extends Base
{
    public const int EXAMPLE_MAIN_LOCATION_ID = 879;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\ObjectState\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $objectStateHandlerMock;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group[] */
    private $allObjectStateGroups;

    /** @var array */
    private $loadObjectStatesMap;

    /**
     * Setup Handler mock.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectStateHandlerMock = $this->createMock(SPIHandler::class);

        $this->allObjectStateGroups = [
            new Group(['id' => 1]),
            new Group(['id' => 2]),
        ];

        $this->loadObjectStatesMap = [
            [
                1,
                [
                    new ObjectState(['id' => 1, 'priority' => 1]),
                    new ObjectState(['id' => 2, 'priority' => 0]),
                ],
            ],
            [
                2,
                [
                    new ObjectState(['id' => 3, 'priority' => 1]),
                    new ObjectState(['id' => 4, 'priority' => 0]),
                ],
            ],
        ];
    }

    /**
     * Tear down Handler mock.
     */
    protected function tearDown(): void
    {
        unset($this->objectStateHandlerMock);

        parent::tearDown();
    }

    /**
     * @return \Ibexa\Core\Limitation\ObjectStateLimitationType
     */
    public function testConstruct()
    {
        return new ObjectStateLimitationType($this->getPersistenceMock());
    }

    /**
     * @return array
     */
    public function providerForTestEvaluate(): array
    {
        return [
            'ContentInfo, published, no Limitations, no access' => [
                'limitation' => new ObjectStateLimitation(),
                'object' => new ContentInfo(['id' => 1, 'published' => true]),
                'expected' => false,
            ],
            'ContentInfo, published, with Limitation=2, no access' => [
                'limitation' => new ObjectStateLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo([
                    'id' => 1,
                    'published' => true,
                    'mainLocationId' => self::EXAMPLE_MAIN_LOCATION_ID,
                ]),
                'expected' => false,
            ],
            'ContentInfo, published, with Limitations=(2, 3), no access' => [
                'limitation' => new ObjectStateLimitation(['limitationValues' => [2, 3]]),
                'object' => new ContentInfo([
                    'id' => 1,
                    'published' => true,
                    'mainLocationId' => self::EXAMPLE_MAIN_LOCATION_ID,
                ]),
                'expected' => false,
            ],
            'ContentInfo, published, with Limitations=(2, 4), no access' => [
                'limitation' => new ObjectStateLimitation(['limitationValues' => [2, 4]]),
                'object' => new ContentInfo([
                    'id' => 1,
                    'published' => true,
                    'mainLocationId' => self::EXAMPLE_MAIN_LOCATION_ID,
                ]),
                'expected' => false,
            ],
            'ContentInfo, published, with Limitation=1, with access' => [
                'limitation' => new ObjectStateLimitation(['limitationValues' => [1]]),
                'object' => new ContentInfo([
                    'id' => 1,
                    'published' => true,
                    'mainLocationId' => self::EXAMPLE_MAIN_LOCATION_ID,
                ]),
                'expected' => true,
            ],
            'ContentInfo, published, with Limitations=(1, 3), with access' => [
                'limitation' => new ObjectStateLimitation(['limitationValues' => [1, 3]]),
                'object' => new ContentInfo([
                    'id' => 1,
                    'published' => true,
                    'mainLocationId' => self::EXAMPLE_MAIN_LOCATION_ID,
                ]),
                'expected' => true,
            ],
            'ContentInfo, not published, with Limitation=2, no access' => [
                'limitation' => new ObjectStateLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo([
                    'id' => 1,
                    'published' => false,
                    'mainLocationId' => self::EXAMPLE_MAIN_LOCATION_ID,
                ]),
                'expected' => false,
            ],
            'ContentInfo, not published, with Limitation=(1, 3), with access' => [
                'limitation' => new ObjectStateLimitation(['limitationValues' => [1, 3]]),
                'object' => new ContentInfo([
                    'id' => 1,
                    'published' => false,
                    'mainLocationId' => self::EXAMPLE_MAIN_LOCATION_ID,
                ]),
                'expected' => true,
            ],
            'RootLocation, no object states assigned' => [
                'limitation' => new ObjectStateLimitation(['limitationValues' => [1, 3]]),
                'object' => new ContentInfo(['id' => 0, 'mainLocationId' => 1, 'published' => true]),
                'expected' => true,
            ],
            'Non-RootLocation, published, with Limitation=2' => [
                'limitation' => new ObjectStateLimitation(['limitationValues' => [2]]),
                'object' => new ContentInfo(['id' => 1, 'mainLocationId' => 2, 'published' => true]),
                'expected' => false,
            ],
            'ContentCreateStruct, with access' => [
                'limitation' => new ObjectStateLimitation(['limitationValues' => [1, 3]]),
                'object' => new ContentCreateStruct(),
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestEvaluate
     */
    public function testEvaluate(
        ObjectStateLimitation $limitation,
        ValueObject $object,
        $expected
    ) {
        $getContentStateMap = [
            [
                1,
                1,
                new ObjectState(['id' => 1]),
            ],
            [
                1,
                2,
                new ObjectState(['id' => 3]),
            ],
        ];

        if (!empty($limitation->limitationValues)) {
            $this->getPersistenceMock()
                 ->method('objectStateHandler')
                 ->willReturn($this->objectStateHandlerMock);

            $this->objectStateHandlerMock
                ->method('loadAllGroups')
                ->willReturn($this->allObjectStateGroups);

            $this->objectStateHandlerMock
                ->method('loadObjectStates')
                ->willReturnMap($this->loadObjectStatesMap);

            $this->objectStateHandlerMock
                ->method('getContentState')
                ->willReturnMap($getContentStateMap);
        } else {
            $this->getPersistenceMock()
                 ->expects(self::never())
                 ->method(self::anything());
        }

        $userMock = $this->getUserMock();
        $userMock
            ->expects(self::never())
            ->method(self::anything());

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $value = $limitationType->evaluate(
            $limitation,
            $userMock,
            $object
        );

        self::assertSame($expected, $value);
    }

    /**
     * @depends testConstruct
     *
     * @param \Ibexa\Core\Limitation\ObjectStateLimitationType $limitationType
     */
    public function testGetCriterionInvalidValue(ObjectStateLimitationType $limitationType)
    {
        $this->expectException(\RuntimeException::class);

        $limitationType->getCriterion(
            new ObjectStateLimitation([]),
            $this->getUserMock()
        );
    }

    /**
     * @depends testConstruct
     *
     * @param \Ibexa\Core\Limitation\ObjectStateLimitationType $limitationType
     */
    public function testGetCriterionSingleValue(ObjectStateLimitationType $limitationType)
    {
        $criterion = $limitationType->getCriterion(
            new ObjectStateLimitation(['limitationValues' => [2]]),
            $this->getUserMock()
        );

        self::assertInstanceOf(ObjectStateId::class, $criterion);
        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::EQ, $criterion->operator);
        self::assertEquals([2], $criterion->value);
    }

    public function testGetCriterionMultipleValuesFromSingleGroup()
    {
        $this->getPersistenceMock()
             ->method('objectStateHandler')
             ->willReturn($this->objectStateHandlerMock);

        $this->objectStateHandlerMock
            ->method('loadAllGroups')
            ->willReturn($this->allObjectStateGroups);

        $this->objectStateHandlerMock
            ->method('loadObjectStates')
            ->willReturnMap($this->loadObjectStatesMap);

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $criterion = $limitationType->getCriterion(
            new ObjectStateLimitation(['limitationValues' => [1, 2]]),
            $this->getUserMock()
        );

        self::assertInstanceOf(ObjectStateId::class, $criterion);
        self::assertIsArray($criterion->value);
        self::assertIsString($criterion->operator);
        self::assertEquals(Operator::IN, $criterion->operator);
        self::assertEquals([1, 2], $criterion->value);
    }

    public function testGetCriterionMultipleValuesFromMultipleGroups()
    {
        $this->getPersistenceMock()
             ->method('objectStateHandler')
             ->willReturn($this->objectStateHandlerMock);

        $this->objectStateHandlerMock
            ->method('loadAllGroups')
            ->willReturn($this->allObjectStateGroups);

        $this->objectStateHandlerMock
            ->method('loadObjectStates')
            ->willReturnMap($this->loadObjectStatesMap);

        // Need to create inline instead of depending on testConstruct() to get correct mock instance
        $limitationType = $this->testConstruct();

        $criterion = $limitationType->getCriterion(
            new ObjectStateLimitation(['limitationValues' => [1, 2, 3]]),
            $this->getUserMock()
        );

        self::assertInstanceOf(LogicalAnd::class, $criterion);
        self::assertIsArray($criterion->criteria);

        self::assertInstanceOf(ObjectStateId::class, $criterion->criteria[0]);
        self::assertIsArray($criterion->criteria[0]->value);
        self::assertIsString($criterion->criteria[0]->operator);
        self::assertEquals(Operator::IN, $criterion->criteria[0]->operator);
        self::assertEquals([1, 2], $criterion->criteria[0]->value);

        self::assertInstanceOf(ObjectStateId::class, $criterion->criteria[1]);
        self::assertIsArray($criterion->criteria[1]->value);
        self::assertIsString($criterion->criteria[1]->operator);
        self::assertEquals(Operator::IN, $criterion->criteria[1]->operator);
        self::assertEquals([3], $criterion->criteria[1]->value);
    }
}

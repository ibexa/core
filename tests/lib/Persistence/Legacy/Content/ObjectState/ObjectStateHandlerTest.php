<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\ObjectState;

use Ibexa\Contracts\Core\Persistence\Content\ObjectState;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\InputStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Handler;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Mapper;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageAwareTestCase;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase as APIBaseTest;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Handler::class)]
class ObjectStateHandlerTest extends LanguageAwareTestCase
{
    /**
     * Object state handler.
     *
     * @var Handler
     */
    protected $objectStateHandler;

    /**
     * Object state gateway mock.
     *
     * @var Gateway
     */
    protected $gatewayMock;

    /**
     * Object state mapper mock.
     *
     * @var Mapper
     */
    protected $mapperMock;

    public function testCreateGroup(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $mapperMock->expects($this->once())
            ->method('createObjectStateGroupFromInputStruct')
            ->with(self::equalTo($this->getInputStructFixture()))
            ->will($this->returnValue($this->getObjectStateGroupFixture()));

        $gatewayMock->expects($this->once())
            ->method('insertObjectStateGroup')
            ->with(self::equalTo($this->getObjectStateGroupFixture()))
            ->will($this->returnValue($this->getObjectStateGroupFixture()));

        $result = $handler->createGroup($this->getInputStructFixture());

        self::assertInstanceOf(
            Group::class,
            $result
        );
    }

    public function testLoadGroup(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateGroupData')
            ->with(self::equalTo(2))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateGroupFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue($this->getObjectStateGroupFixture()));

        $result = $handler->loadGroup(2);

        self::assertInstanceOf(
            Group::class,
            $result
        );
    }

    public function testLoadGroupThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateGroupData')
            ->with(self::equalTo(APIBaseTest::DB_INT_MAX))
            ->will($this->returnValue([]));

        $handler->loadGroup(APIBaseTest::DB_INT_MAX);
    }

    public function testLoadGroupByIdentifier(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateGroupDataByIdentifier')
            ->with(self::equalTo('ibexa_lock'))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateGroupFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue($this->getObjectStateGroupFixture()));

        $result = $handler->loadGroupByIdentifier('ibexa_lock');

        self::assertInstanceOf(
            Group::class,
            $result
        );
    }

    public function testLoadGroupByIdentifierThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateGroupDataByIdentifier')
            ->with(self::equalTo('unknown'))
            ->will($this->returnValue([]));

        $handler->loadGroupByIdentifier('unknown');
    }

    public function testLoadAllGroups(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateGroupListData')
            ->with(self::equalTo(0), self::equalTo(-1))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateGroupListFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue([$this->getObjectStateGroupFixture()]));

        $result = $handler->loadAllGroups();

        foreach ($result as $resultItem) {
            self::assertInstanceOf(
                Group::class,
                $resultItem
            );
        }
    }

    public function testLoadObjectStates(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateListData')
            ->with(self::equalTo(2))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateListFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue([$this->getObjectStateFixture(), $this->getObjectStateFixture()]));

        $result = $handler->loadObjectStates(2);

        foreach ($result as $resultItem) {
            self::assertInstanceOf(
                ObjectState::class,
                $resultItem
            );
        }
    }

    public function testUpdateGroup(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $mapperMock->expects($this->once())
            ->method('createObjectStateGroupFromInputStruct')
            ->with(self::equalTo($this->getInputStructFixture()))
            ->will($this->returnValue($this->getObjectStateGroupFixture()));

        $gatewayMock->expects($this->once())
            ->method('updateObjectStateGroup')
            ->with(self::equalTo(new Group(['id' => 2])));

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateGroupData')
            ->with(self::equalTo(2))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateGroupFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue($this->getObjectStateGroupFixture()));

        $result = $handler->updateGroup(2, $this->getInputStructFixture());

        self::assertInstanceOf(
            Group::class,
            $result
        );
    }

    public function testDeleteGroup(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateListData')
            ->with(self::equalTo(2))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateListFromData')
            ->with(self::equalTo([[]]))
            ->will(
                $this->returnValue(
                    [
                        new ObjectState(['id' => 1]),
                        new ObjectState(['id' => 2]),
                    ]
                )
            );

        $calls = [];

        $gatewayMock->expects($this->exactly(2))
            ->method('deleteObjectStateLinks')
            ->willReturnCallback(static function (int $stateId) use (&$calls): void {
                $calls[] = ['deleteObjectStateLinks', $stateId];
            });

        $gatewayMock->expects($this->exactly(2))
            ->method('deleteObjectState')
            ->willReturnCallback(static function (int $stateId) use (&$calls): void {
                $calls[] = ['deleteObjectState', $stateId];
            });

        $gatewayMock->expects($this->once())
            ->method('deleteObjectStateGroup')
            ->with(self::equalTo(2));

        $handler->deleteGroup(2);

        self::assertSame(
            [
                ['deleteObjectStateLinks', 1],
                ['deleteObjectState', 1],
                ['deleteObjectStateLinks', 2],
                ['deleteObjectState', 2],
            ],
            $calls
        );
    }

    public function testCreate(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $mapperMock->expects($this->once())
            ->method('createObjectStateFromInputStruct')
            ->with(self::equalTo($this->getInputStructFixture()))
            ->will($this->returnValue($this->getObjectStateFixture()));

        $gatewayMock->expects($this->once())
            ->method('insertObjectState')
            ->with(self::equalTo($this->getObjectStateFixture()), self::equalTo(2))
            ->will($this->returnValue($this->getObjectStateFixture()));

        $result = $handler->create(2, $this->getInputStructFixture());

        self::assertInstanceOf(
            ObjectState::class,
            $result
        );
    }

    public function testLoad(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(1))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue($this->getObjectStateFixture()));

        $result = $handler->load(1);

        self::assertInstanceOf(
            ObjectState::class,
            $result
        );
    }

    public function testLoadThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(APIBaseTest::DB_INT_MAX))
            ->will($this->returnValue([]));

        $handler->load(APIBaseTest::DB_INT_MAX);
    }

    public function testLoadByIdentifier(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateDataByIdentifier')
            ->with(self::equalTo('not_locked'), self::equalTo(2))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue($this->getObjectStateFixture()));

        $result = $handler->loadByIdentifier('not_locked', 2);

        self::assertInstanceOf(
            ObjectState::class,
            $result
        );
    }

    public function testLoadByIdentifierThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateDataByIdentifier')
            ->with(self::equalTo('unknown'), self::equalTo(2))
            ->will($this->returnValue([]));

        $handler->loadByIdentifier('unknown', 2);
    }

    public function testUpdate(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $mapperMock->expects($this->once())
            ->method('createObjectStateFromInputStruct')
            ->with(self::equalTo($this->getInputStructFixture()))
            ->will($this->returnValue($this->getObjectStateFixture()));

        $gatewayMock->expects($this->once())
            ->method('updateObjectState')
            ->with(self::equalTo(new ObjectState(['id' => 1])));

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(1))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue($this->getObjectStateFixture()));

        $result = $handler->update(1, $this->getInputStructFixture());

        self::assertInstanceOf(
            ObjectState::class,
            $result
        );
    }

    public function testSetPriority(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(2))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue(new ObjectState(['id' => 2, 'groupId' => 2])));

        $gatewayMock->expects($this->any())
            ->method('loadObjectStateListData')
            ->with(self::equalTo(2))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->any())
            ->method('createObjectStateListFromData')
            ->with(self::equalTo([[]]))
            ->will(
                $this->returnValue(
                    [
                        new ObjectState(['id' => 1, 'groupId' => 2]),
                        new ObjectState(['id' => 2, 'groupId' => 2]),
                        new ObjectState(['id' => 3, 'groupId' => 2]),
                    ]
                )
            );

        $updateObjectStatePriorityMatcher = $this->exactly(3);
        $gatewayMock->expects($updateObjectStatePriorityMatcher)
            ->method('updateObjectStatePriority')
            ->willReturnCallback(static function (
                $stateId,
                $priority
            ) use ($updateObjectStatePriorityMatcher): void {
                [$expectedStateId, $expectedPriority] = match ($updateObjectStatePriorityMatcher->numberOfInvocations()) {
                    1 => [2, 0],
                    2 => [1, 1],
                    3 => [3, 2],
                };
                self::assertSame($expectedStateId, $stateId);
                self::assertSame($expectedPriority, $priority);
            });

        $handler->setPriority(2, 0);
    }

    public function testDelete(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(1))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue(new ObjectState(['id' => 1, 'groupId' => 2])));

        $gatewayMock->expects($this->once())
            ->method('deleteObjectState')
            ->with(self::equalTo(1));

        $gatewayMock->expects($this->any())
            ->method('loadObjectStateListData')
            ->with(self::equalTo(2))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->any())
            ->method('createObjectStateListFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue([new ObjectState(['id' => 2, 'groupId' => 2])]));

        $gatewayMock->expects($this->once())
            ->method('updateObjectStatePriority')
            ->with(self::equalTo(2), self::equalTo(0));

        $gatewayMock->expects($this->once())
            ->method('updateObjectStateLinks')
            ->with(self::equalTo(1), self::equalTo(2));

        $handler->delete(1);
    }

    public function testDeleteThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(APIBaseTest::DB_INT_MAX))
            ->will($this->returnValue([]));

        $handler->delete(APIBaseTest::DB_INT_MAX);
    }

    public function testSetContentState(): void
    {
        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('setContentState')
            ->with(self::equalTo(42), self::equalTo(2), self::equalTo(2));

        $result = $handler->setContentState(42, 2, 2);

        self::assertTrue($result);
    }

    public function testGetContentState(): void
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('loadObjectStateDataForContent')
            ->with(self::equalTo(42), self::equalTo(2))
            ->will($this->returnValue([[]]));

        $mapperMock->expects($this->once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will($this->returnValue($this->getObjectStateFixture()));

        $result = $handler->getContentState(42, 2);

        self::assertInstanceOf(
            ObjectState::class,
            $result
        );
    }

    public function testGetContentCount(): void
    {
        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('getContentCount')
            ->with(self::equalTo(1))
            ->will($this->returnValue(185));

        $result = $handler->getContentCount(1);

        self::assertEquals(185, $result);
    }

    /**
     * Returns an object state.
     *
     * @return ObjectState
     */
    protected function getObjectStateFixture()
    {
        return new ObjectState();
    }

    /**
     * Returns an object state group.
     *
     * @return Group
     */
    protected function getObjectStateGroupFixture()
    {
        return new Group();
    }

    /**
     * Returns the InputStruct.
     *
     * @return InputStruct
     */
    protected function getInputStructFixture()
    {
        return new InputStruct();
    }

    /**
     * Returns the object state handler to test.
     *
     * @return Handler
     */
    protected function getObjectStateHandler()
    {
        if (!isset($this->objectStateHandler)) {
            $this->objectStateHandler = new Handler(
                $this->getGatewayMock(),
                $this->getMapperMock()
            );
        }

        return $this->objectStateHandler;
    }

    /**
     * Returns an object state mapper mock.
     *
     * @return Mapper
     */
    protected function getMapperMock()
    {
        if (!isset($this->mapperMock)) {
            $this->mapperMock = $this->getMockBuilder(Mapper::class)
                ->setConstructorArgs([$this->getLanguageHandler()])
                ->getMock();
        }

        return $this->mapperMock;
    }

    /**
     * Returns a mock for the object state gateway.
     *
     * @return Gateway
     */
    protected function getGatewayMock()
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->getMockForAbstractClass(Gateway::class);
        }

        return $this->gatewayMock;
    }
}

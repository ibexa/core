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

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\ObjectState\Handler
 */
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

    public function testCreateGroup()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $mapperMock->expects(self::once())
            ->method('createObjectStateGroupFromInputStruct')
            ->with(self::equalTo($this->getInputStructFixture()))
            ->will(self::returnValue($this->getObjectStateGroupFixture()));

        $gatewayMock->expects(self::once())
            ->method('insertObjectStateGroup')
            ->with(self::equalTo($this->getObjectStateGroupFixture()))
            ->will(self::returnValue($this->getObjectStateGroupFixture()));

        $result = $handler->createGroup($this->getInputStructFixture());

        self::assertInstanceOf(
            Group::class,
            $result
        );
    }

    public function testLoadGroup()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateGroupData')
            ->with(self::equalTo(2))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateGroupFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue($this->getObjectStateGroupFixture()));

        $result = $handler->loadGroup(2);

        self::assertInstanceOf(
            Group::class,
            $result
        );
    }

    public function testLoadGroupThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateGroupData')
            ->with(self::equalTo(APIBaseTest::DB_INT_MAX))
            ->will(self::returnValue([]));

        $handler->loadGroup(APIBaseTest::DB_INT_MAX);
    }

    public function testLoadGroupByIdentifier()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateGroupDataByIdentifier')
            ->with(self::equalTo('ibexa_lock'))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateGroupFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue($this->getObjectStateGroupFixture()));

        $result = $handler->loadGroupByIdentifier('ibexa_lock');

        self::assertInstanceOf(
            Group::class,
            $result
        );
    }

    public function testLoadGroupByIdentifierThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateGroupDataByIdentifier')
            ->with(self::equalTo('unknown'))
            ->will(self::returnValue([]));

        $handler->loadGroupByIdentifier('unknown');
    }

    public function testLoadAllGroups()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateGroupListData')
            ->with(self::equalTo(0), self::equalTo(-1))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateGroupListFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue([$this->getObjectStateGroupFixture()]));

        $result = $handler->loadAllGroups();

        foreach ($result as $resultItem) {
            self::assertInstanceOf(
                Group::class,
                $resultItem
            );
        }
    }

    public function testLoadObjectStates()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateListData')
            ->with(self::equalTo(2))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateListFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue([$this->getObjectStateFixture(), $this->getObjectStateFixture()]));

        $result = $handler->loadObjectStates(2);

        foreach ($result as $resultItem) {
            self::assertInstanceOf(
                ObjectState::class,
                $resultItem
            );
        }
    }

    public function testUpdateGroup()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $mapperMock->expects(self::once())
            ->method('createObjectStateGroupFromInputStruct')
            ->with(self::equalTo($this->getInputStructFixture()))
            ->will(self::returnValue($this->getObjectStateGroupFixture()));

        $gatewayMock->expects(self::once())
            ->method('updateObjectStateGroup')
            ->with(self::equalTo(new Group(['id' => 2])));

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateGroupData')
            ->with(self::equalTo(2))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateGroupFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue($this->getObjectStateGroupFixture()));

        $result = $handler->updateGroup(2, $this->getInputStructFixture());

        self::assertInstanceOf(
            Group::class,
            $result
        );
    }

    public function testDeleteGroup()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateListData')
            ->with(self::equalTo(2))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateListFromData')
            ->with(self::equalTo([[]]))
            ->will(
                self::returnValue(
                    [
                        new ObjectState(['id' => 1]),
                        new ObjectState(['id' => 2]),
                    ]
                )
            );

        $gatewayMock->expects(self::exactly(2))
            ->method('deleteObjectStateLinks');

        $gatewayMock->expects(self::exactly(2))
            ->method('deleteObjectState');

        $gatewayMock->expects(self::at(1))
            ->method('deleteObjectStateLinks')
            ->with(self::equalTo(1));

        $gatewayMock->expects(self::at(2))
            ->method('deleteObjectState')
            ->with(self::equalTo(1));

        $gatewayMock->expects(self::at(3))
            ->method('deleteObjectStateLinks')
            ->with(self::equalTo(2));

        $gatewayMock->expects(self::at(4))
            ->method('deleteObjectState')
            ->with(self::equalTo(2));

        $gatewayMock->expects(self::once())
            ->method('deleteObjectStateGroup')
            ->with(self::equalTo(2));

        $handler->deleteGroup(2);
    }

    public function testCreate()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $mapperMock->expects(self::once())
            ->method('createObjectStateFromInputStruct')
            ->with(self::equalTo($this->getInputStructFixture()))
            ->will(self::returnValue($this->getObjectStateFixture()));

        $gatewayMock->expects(self::once())
            ->method('insertObjectState')
            ->with(self::equalTo($this->getObjectStateFixture()), self::equalTo(2))
            ->will(self::returnValue($this->getObjectStateFixture()));

        $result = $handler->create(2, $this->getInputStructFixture());

        self::assertInstanceOf(
            ObjectState::class,
            $result
        );
    }

    public function testLoad()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(1))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue($this->getObjectStateFixture()));

        $result = $handler->load(1);

        self::assertInstanceOf(
            ObjectState::class,
            $result
        );
    }

    public function testLoadThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(APIBaseTest::DB_INT_MAX))
            ->will(self::returnValue([]));

        $handler->load(APIBaseTest::DB_INT_MAX);
    }

    public function testLoadByIdentifier()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateDataByIdentifier')
            ->with(self::equalTo('not_locked'), self::equalTo(2))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue($this->getObjectStateFixture()));

        $result = $handler->loadByIdentifier('not_locked', 2);

        self::assertInstanceOf(
            ObjectState::class,
            $result
        );
    }

    public function testLoadByIdentifierThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateDataByIdentifier')
            ->with(self::equalTo('unknown'), self::equalTo(2))
            ->will(self::returnValue([]));

        $handler->loadByIdentifier('unknown', 2);
    }

    public function testUpdate()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $mapperMock->expects(self::once())
            ->method('createObjectStateFromInputStruct')
            ->with(self::equalTo($this->getInputStructFixture()))
            ->will(self::returnValue($this->getObjectStateFixture()));

        $gatewayMock->expects(self::once())
            ->method('updateObjectState')
            ->with(self::equalTo(new ObjectState(['id' => 1])));

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(1))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue($this->getObjectStateFixture()));

        $result = $handler->update(1, $this->getInputStructFixture());

        self::assertInstanceOf(
            ObjectState::class,
            $result
        );
    }

    public function testSetPriority()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(2))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue(new ObjectState(['id' => 2, 'groupId' => 2])));

        $gatewayMock->expects(self::any())
            ->method('loadObjectStateListData')
            ->with(self::equalTo(2))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::any())
            ->method('createObjectStateListFromData')
            ->with(self::equalTo([[]]))
            ->will(
                self::returnValue(
                    [
                        new ObjectState(['id' => 1, 'groupId' => 2]),
                        new ObjectState(['id' => 2, 'groupId' => 2]),
                        new ObjectState(['id' => 3, 'groupId' => 2]),
                    ]
                )
            );

        $gatewayMock->expects(self::exactly(3))
            ->method('updateObjectStatePriority');

        $gatewayMock->expects(self::at(2))
            ->method('updateObjectStatePriority')
            ->with(self::equalTo(2), self::equalTo(0));

        $gatewayMock->expects(self::at(3))
            ->method('updateObjectStatePriority')
            ->with(self::equalTo(1), self::equalTo(1));

        $gatewayMock->expects(self::at(4))
            ->method('updateObjectStatePriority')
            ->with(self::equalTo(3), self::equalTo(2));

        $handler->setPriority(2, 0);
    }

    public function testDelete()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(1))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue(new ObjectState(['id' => 1, 'groupId' => 2])));

        $gatewayMock->expects(self::once())
            ->method('deleteObjectState')
            ->with(self::equalTo(1));

        $gatewayMock->expects(self::any())
            ->method('loadObjectStateListData')
            ->with(self::equalTo(2))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::any())
            ->method('createObjectStateListFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue([new ObjectState(['id' => 2, 'groupId' => 2])]));

        $gatewayMock->expects(self::once())
            ->method('updateObjectStatePriority')
            ->with(self::equalTo(2), self::equalTo(0));

        $gatewayMock->expects(self::once())
            ->method('updateObjectStateLinks')
            ->with(self::equalTo(1), self::equalTo(2));

        $handler->delete(1);
    }

    public function testDeleteThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateData')
            ->with(self::equalTo(APIBaseTest::DB_INT_MAX))
            ->will(self::returnValue([]));

        $handler->delete(APIBaseTest::DB_INT_MAX);
    }

    public function testSetContentState()
    {
        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('setContentState')
            ->with(self::equalTo(42), self::equalTo(2), self::equalTo(2));

        $result = $handler->setContentState(42, 2, 2);

        self::assertTrue($result);
    }

    public function testGetContentState()
    {
        $handler = $this->getObjectStateHandler();
        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('loadObjectStateDataForContent')
            ->with(self::equalTo(42), self::equalTo(2))
            ->will(self::returnValue([[]]));

        $mapperMock->expects(self::once())
            ->method('createObjectStateFromData')
            ->with(self::equalTo([[]]))
            ->will(self::returnValue($this->getObjectStateFixture()));

        $result = $handler->getContentState(42, 2);

        self::assertInstanceOf(
            ObjectState::class,
            $result
        );
    }

    public function testGetContentCount()
    {
        $handler = $this->getObjectStateHandler();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('getContentCount')
            ->with(self::equalTo(1))
            ->will(self::returnValue(185));

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
                ->setMethods([])
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

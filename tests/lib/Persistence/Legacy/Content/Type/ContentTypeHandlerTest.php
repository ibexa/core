<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\Type\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\CreateStruct as GroupCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\UpdateStruct as GroupUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\UpdateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Handler;
use Ibexa\Core\Persistence\Legacy\Content\Type\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\Type\StorageDispatcherInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler as UpdateHandler;
use Ibexa\Core\Persistence\Legacy\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Type\Handler
 */
class ContentTypeHandlerTest extends TestCase
{
    /**
     * Gateway mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Type\Gateway
     */
    protected $gatewayMock;

    /**
     * Mapper mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Type\Mapper
     */
    protected $mapperMock;

    /**
     * Update\Handler mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler
     */
    protected $updateHandlerMock;

    /** @var \Ibexa\Core\Persistence\Legacy\Content\Type\StorageDispatcherInterface&\PHPUnit\Framework\MockObject\MockObject */
    protected $storageDispatcherMock;

    public function testCreateGroup()
    {
        $createStruct = new GroupCreateStruct();

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('createGroupFromCreateStruct')
            ->with(
                self::isInstanceOf(
                    GroupCreateStruct::class
                )
            )
            ->will(
                self::returnValue(new Group())
            );

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('insertGroup')
            ->with(
                self::isInstanceOf(
                    Group::class
                )
            )
            ->will(self::returnValue(23));

        $handler = $this->getHandler();
        $group = $handler->createGroup(
            new GroupCreateStruct()
        );

        self::assertInstanceOf(
            Group::class,
            $group
        );
        self::assertEquals(
            23,
            $group->id
        );
    }

    public function testUpdateGroup()
    {
        $updateStruct = new GroupUpdateStruct();
        $updateStruct->id = 23;

        $mapperMock = $this->getMapperMock();

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('updateGroup')
            ->with(
                self::isInstanceOf(
                    GroupUpdateStruct::class
                )
            );

        $handlerMock = $this->getMockBuilder(Handler::class)
            ->setMethods(['loadGroup'])
            ->setConstructorArgs([
                $gatewayMock,
                $mapperMock,
                $this->getUpdateHandlerMock(),
                $this->getStorageDispatcherMock(),
            ])
            ->getMock();

        $handlerMock->expects(self::once())
            ->method('loadGroup')
            ->with(
                self::equalTo(23)
            )->will(
                self::returnValue(new Group())
            );

        $res = $handlerMock->updateGroup(
            $updateStruct
        );

        self::assertInstanceOf(
            Group::class,
            $res
        );
    }

    public function testDeleteGroupSuccess()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('countTypesInGroup')
            ->with(self::equalTo(23))
            ->will(self::returnValue(0));
        $gatewayMock->expects(self::once())
            ->method('deleteGroup')
            ->with(self::equalTo(23));

        $handler = $this->getHandler();
        $handler->deleteGroup(23);
    }

    public function testDeleteGroupFailure()
    {
        $this->expectException(Exception\GroupNotEmpty::class);
        $this->expectExceptionMessage('Group with ID "23" is not empty.');

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('countTypesInGroup')
            ->with(self::equalTo(23))
            ->will(self::returnValue(42));
        $gatewayMock->expects(self::never())
            ->method('deleteGroup');

        $handler = $this->getHandler();
        $handler->deleteGroup(23);
    }

    public function testLoadGroup()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadGroupData')
            ->with(self::equalTo([23]))
            ->will(self::returnValue([]));

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractGroupsFromRows')
            ->with(self::equalTo([]))
            ->will(self::returnValue([new Group()]));

        $handler = $this->getHandler();
        $res = $handler->loadGroup(23);

        self::assertEquals(
            new Group(),
            $res
        );
    }

    public function testLoadGroupByIdentifier()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadGroupDataByIdentifier')
            ->with(self::equalTo('content'))
            ->will(self::returnValue([]));

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractGroupsFromRows')
            ->with(self::equalTo([]))
            ->will(self::returnValue([new Group()]));

        $handler = $this->getHandler();
        $res = $handler->loadGroupByIdentifier('content');

        self::assertEquals(
            new Group(),
            $res
        );
    }

    public function testLoadAllGroups()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadAllGroupsData')
            ->will(self::returnValue([]));

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractGroupsFromRows')
            ->with(self::equalTo([]))
            ->will(self::returnValue([new Group()]));

        $handler = $this->getHandler();
        $res = $handler->loadAllGroups();

        self::assertEquals(
            [new Group()],
            $res
        );
    }

    public function testLoadContentTypes()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadTypesDataForGroup')
            ->with(self::equalTo(23), self::equalTo(0))
            ->will(self::returnValue([]));

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractTypesFromRows')
            ->with(self::equalTo([]))
            ->will(self::returnValue([new Type()]));

        $handler = $this->getHandler();
        $res = $handler->loadContentTypes(23, 0);

        self::assertEquals(
            [new Type()],
            $res
        );
    }

    public function testLoadContentTypeList(): void
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadTypesListData')
            ->with(self::equalTo([23, 24]))
            ->willReturn([]);

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractTypesFromRows')
            ->with(self::equalTo([]))
            ->willReturn([23 => new Type()]);

        $handler = $this->getHandler();
        $types = $handler->loadContentTypeList([23, 24]);

        self::assertEquals(
            [23 => new Type()],
            $types,
            'Types not loaded correctly'
        );
    }

    public function testLoadContentTypesByFieldDefinitionIdentifier(): void
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadTypesDataByFieldDefinitionIdentifier')
            ->with('ibexa_string')
            ->willReturn([]);

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractTypesFromRows')
            ->with([])
            ->willReturn([23 => new Type()]);

        $handler = $this->getHandler();
        $types = $handler->loadContentTypesByFieldDefinitionIdentifier('ibexa_string');

        self::assertEquals(
            [23 => new Type()],
            $types,
            'Types not loaded correctly'
        );
    }

    public function testLoad()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadTypeData')
            ->with(
                self::equalTo(23),
                self::equalTo(1)
            )
            ->will(self::returnValue([]));

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractTypesFromRows')
            ->with(self::equalTo([]))
            ->will(
                self::returnValue(
                    [new Type()]
                )
            );

        $handler = $this->getHandler();
        $type = $handler->load(23, 1);

        self::assertEquals(
            new Type(),
            $type,
            'Type not loaded correctly'
        );
    }

    public function testLoadNotFound()
    {
        $this->expectException(Exception\TypeNotFound::class);

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadTypeData')
            ->with(
                self::equalTo(23),
                self::equalTo(1)
            )
            ->will(self::returnValue([]));

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractTypesFromRows')
            ->with(self::equalTo([]))
            ->will(
                self::returnValue(
                    []
                )
            );

        $handler = $this->getHandler();
        $type = $handler->load(23, 1);
    }

    public function testLoadDefaultVersion()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadTypeData')
            ->with(
                self::equalTo(23),
                self::equalTo(0)
            )
            ->will(self::returnValue([]));

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractTypesFromRows')
            ->will(
                self::returnValue(
                    [new Type()]
                )
            );

        $handler = $this->getHandler();
        $type = $handler->load(23);

        self::assertEquals(
            new Type(),
            $type,
            'Type not loaded correctly'
        );
    }

    public function testLoadByIdentifier()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadTypeDataByIdentifier')
            ->with(
                self::equalTo('blogentry'),
                self::equalTo(0)
            )
            ->will(self::returnValue([]));

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractTypesFromRows')
            ->will(
                self::returnValue(
                    [new Type()]
                )
            );

        $handler = $this->getHandler();
        $type = $handler->loadByIdentifier('blogentry');

        self::assertEquals(
            new Type(),
            $type,
            'Type not loaded correctly'
        );
    }

    public function testLoadByRemoteId()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadTypeDataByRemoteId')
            ->with(
                self::equalTo('someLongHash'),
                self::equalTo(0)
            )
            ->will(self::returnValue([]));

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('extractTypesFromRows')
            ->will(
                self::returnValue(
                    [new Type()]
                )
            );

        $handler = $this->getHandler();
        $type = $handler->loadByRemoteId('someLongHash');

        self::assertEquals(
            new Type(),
            $type,
            'Type not loaded correctly'
        );
    }

    public function testCreate()
    {
        $createStructFix = $this->getContentTypeCreateStructFixture();
        $createStructClone = clone $createStructFix;

        $mapperMock = $this->getMapperMock(
            [
                'toStorageFieldDefinition',
            ]
        );

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('insertType')
            ->with(
                self::isInstanceOf(
                    Type::class
                )
            )
            ->will(self::returnValue(23));
        $gatewayMock->expects(self::once())
            ->method('insertGroupAssignment')
            ->with(
                self::equalTo(42),
                self::equalTo(23),
                self::equalTo(1)
            );
        $gatewayMock->expects(self::exactly(2))
            ->method('insertFieldDefinition')
            ->with(
                self::equalTo(23),
                self::equalTo(1),
                self::isInstanceOf(FieldDefinition::class),
                self::isInstanceOf(StorageFieldDefinition::class)
            )
            ->will(self::returnValue(42));

        $mapperMock->expects(self::exactly(2))
            ->method('toStorageFieldDefinition')
            ->with(
                self::isInstanceOf(FieldDefinition::class),
                self::isInstanceOf(StorageFieldDefinition::class)
            );

        $handler = $this->getHandler();
        $type = $handler->create($createStructFix);

        self::assertInstanceOf(
            Type::class,
            $type,
            'Incorrect type returned from create()'
        );
        self::assertEquals(
            23,
            $type->id,
            'Incorrect ID for Type.'
        );

        self::assertEquals(
            42,
            $type->fieldDefinitions[0]->id,
            'Field definition ID not set correctly'
        );
        self::assertEquals(
            42,
            $type->fieldDefinitions[1]->id,
            'Field definition ID not set correctly'
        );

        self::assertEquals(
            $createStructClone,
            $createStructFix,
            'Create struct manipulated'
        );
    }

    public function testUpdate()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('updateType')
            ->with(
                self::equalTo(23),
                self::equalTo(1),
                self::isInstanceOf(
                    Type::class
                )
            );

        $handlerMock = $this->getMockBuilder(Handler::class)
            ->setMethods(['load'])
            ->setConstructorArgs([
                $gatewayMock,
                $this->getMapperMock(),
                $this->getUpdateHandlerMock(),
                $this->getStorageDispatcherMock(),
            ])
            ->getMock();

        $handlerMock->expects(self::once())
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(1)
            )
            ->will(self::returnValue(new Type()));

        $res = $handlerMock->update(23, 1, new UpdateStruct());

        self::assertInstanceOf(
            Type::class,
            $res
        );
    }

    public function testDeleteSuccess()
    {
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(
            self::once()
        )->method(
            'countInstancesOfType'
        )->with(
            self::equalTo(23)
        )->will(
            self::returnValue(0)
        );

        $gatewayMock->expects(self::once())->method('loadTypeData')->with(23, 0)->willReturn([]);

        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())->method('extractTypesFromRows')->with([])->willReturn([new Type()]);

        $gatewayMock->expects(
            self::once()
        )->method(
            'delete'
        )->with(
            self::equalTo(23),
            self::equalTo(0)
        );

        $handler = $this->getHandler();
        $res = $handler->delete(23, 0);

        self::assertTrue($res);
    }

    public function testDeleteThrowsBadStateException()
    {
        $this->expectException(BadStateException::class);

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(
            self::once()
        )->method(
            'countInstancesOfType'
        )->with(
            self::equalTo(23)
        )->will(
            self::returnValue(1)
        );

        $gatewayMock->expects(self::never())->method('delete');

        $handler = $this->getHandler();
        $res = $handler->delete(23, 0);
    }

    public function testCreateVersion()
    {
        $userId = 42;
        $contentTypeId = 23;

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('createCreateStructFromType')
            ->with(
                self::isInstanceOf(
                    Type::class
                )
            )->will(
                self::returnValue(new CreateStruct())
            );

        $handlerMock = $this->getMockBuilder(Handler::class)
            ->setMethods(['load', 'internalCreate'])
            ->setConstructorArgs([
                $gatewayMock,
                $mapperMock,
                $this->getUpdateHandlerMock(),
                $this->getStorageDispatcherMock(),
            ])
            ->getMock();

        $handlerMock->expects(self::once())
            ->method('load')
            ->with(
                self::equalTo($contentTypeId),
                self::equalTo(Type::STATUS_DEFINED),
            )->will(
                self::returnValue(
                    new Type()
                )
            );

        $typeDraft = new Type();
        $handlerMock->expects(self::once())
            ->method('internalCreate')
            ->with(
                self::isInstanceOf(CreateStruct::class),
                self::equalTo($contentTypeId)
            )->will(
                self::returnValue($typeDraft)
            );

        $res = $handlerMock->createDraft($userId, $contentTypeId);

        self::assertSame(
            $typeDraft,
            $res
        );
    }

    public function testCopy()
    {
        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock(['createCreateStructFromType']);
        $mapperMock->expects(self::once())
            ->method('createCreateStructFromType')
            ->with(
                self::isInstanceOf(
                    Type::class
                )
            )->willReturn(
                new CreateStruct(['identifier' => 'testCopy'])
            );

        $handlerMock = $this->getMockBuilder(Handler::class)
            ->setMethods(['load', 'internalCreate', 'update'])
            ->setConstructorArgs([
                $gatewayMock,
                $mapperMock,
                $this->getUpdateHandlerMock(),
                $this->getStorageDispatcherMock(),
            ])
            ->getMock();

        $userId = 42;
        $type = new Type([
            'id' => 23,
            'identifier' => md5(uniqid(get_class($handlerMock), true)),
            'status' => Type::STATUS_DEFINED,
        ]);

        $handlerMock->expects(self::once())
            ->method('load')
            ->with(
                self::equalTo($type->id),
                self::equalTo(Type::STATUS_DEFINED),
            )->willReturn(
                $type
            );

        $typeCopy = clone $type;
        $typeCopy->id = 24;
        $typeCopy->identifier = 'copy_of' . $type->identifier . '_' . $type->id;

        $handlerMock->expects(self::once())
            ->method('internalCreate')
            ->with(
                self::isInstanceOf(CreateStruct::class),
            )->willReturn(
                $typeCopy
            );

        $handlerMock->expects(self::once())
            ->method('update')
            ->with(
                self::equalTo($typeCopy->id),
                self::equalTo(Type::STATUS_DEFINED),
                self::isInstanceOf(UpdateStruct::class)
            )
            ->will(
                self::returnValue($typeCopy)
            );

        $res = $handlerMock->copy($userId, $type->id, Type::STATUS_DEFINED);

        self::assertEquals(
            $typeCopy,
            $res
        );
    }

    public function testLink()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('insertGroupAssignment')
            ->with(
                self::equalTo(3),
                self::equalTo(23),
                self::equalTo(1)
            );

        $mapperMock = $this->getMapperMock();

        $handler = $this->getHandler();
        $res = $handler->link(3, 23, 1);

        self::assertTrue($res);
    }

    public function testUnlinkSuccess()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('countGroupsForType')
            ->with(
                self::equalTo(23),
                self::equalTo(1)
            )->will(self::returnValue(2));

        $gatewayMock->expects(self::once())
            ->method('deleteGroupAssignment')
            ->with(
                self::equalTo(3),
                self::equalTo(23),
                self::equalTo(1)
            );

        $mapperMock = $this->getMapperMock();

        $handler = $this->getHandler();
        $res = $handler->unlink(3, 23, 1);

        self::assertTrue($res);
    }

    public function testUnlinkFailure()
    {
        $this->expectException(Exception\RemoveLastGroupFromType::class);
        $this->expectExceptionMessage('Type with ID "23" in status "1" cannot be unlinked from its last group.');

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('countGroupsForType')
            ->with(
                self::equalTo(23),
                self::equalTo(1)
            )
            // Only 1 group assigned
            ->will(self::returnValue(1));

        $mapperMock = $this->getMapperMock();

        $handler = $this->getHandler();
        $res = $handler->unlink(3, 23, 1);
    }

    public function testGetFieldDefinition()
    {
        $mapperMock = $this->getMapperMock(
            [
                'extractFieldFromRow',
                'extractMultilingualData',
            ]
        );
        $mapperMock->expects(self::once())
            ->method('extractFieldFromRow')
            ->with(
                self::equalTo([])
            )->will(
                self::returnValue(new FieldDefinition())
            );

        $mapperMock->expects(self::once())
            ->method('extractMultilingualData')
            ->with(
                self::equalTo([
                    [],
                ])
            )->will(
                self::returnValue([])
            );

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('loadFieldDefinition')
            ->with(
                self::equalTo(42),
                self::equalTo(Type::STATUS_DEFINED)
            )->will(
                self::returnValue([
                    [],
                ])
            );

        $handler = $this->getHandler();
        $fieldDefinition = $handler->getFieldDefinition(42, Type::STATUS_DEFINED);

        self::assertInstanceOf(
            FieldDefinition::class,
            $fieldDefinition
        );
    }

    public function testAddFieldDefinition()
    {
        $mapperMock = $this->getMapperMock(
            ['toStorageFieldDefinition']
        );
        $mapperMock->expects(self::once())
            ->method('toStorageFieldDefinition')
            ->with(
                self::isInstanceOf(
                    FieldDefinition::class
                ),
                self::isInstanceOf(
                    StorageFieldDefinition::class
                )
            );

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('insertFieldDefinition')
            ->with(
                self::equalTo(23),
                self::equalTo(1),
                self::isInstanceOf(
                    FieldDefinition::class
                ),
                self::isInstanceOf(
                    StorageFieldDefinition::class
                )
            )->will(
                self::returnValue(42)
            );

        $fieldDef = new FieldDefinition();

        $storageDispatcherMock = $this->getStorageDispatcherMock();
        $storageDispatcherMock
            ->expects(self::once())
            ->method('storeFieldConstraintsData')
            ->with($fieldDef);

        $handler = $this->getHandler();
        $handler->addFieldDefinition(23, 1, $fieldDef);

        self::assertEquals(
            42,
            $fieldDef->id
        );
    }

    public function testGetContentCount()
    {
        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('countInstancesOfType')
            ->with(
                self::equalTo(23)
            )->will(
                self::returnValue(42)
            );

        $handler = $this->getHandler();

        self::assertEquals(
            42,
            $handler->getContentCount(23)
        );
    }

    public function testRemoveFieldDefinition()
    {
        $storageDispatcherMock = $this->getStorageDispatcherMock();
        $storageDispatcherMock
            ->expects(self::once())
            ->method('deleteFieldConstraintsData')
            ->with('ibexa_string', 42);

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('deleteFieldDefinition')
            ->with(
                self::equalTo(23),
                self::equalTo(1),
                self::equalTo(42)
            );

        $handler = $this->getHandler();
        $handler->removeFieldDefinition(23, 1, new FieldDefinition(['id' => 42, 'fieldType' => 'ibexa_string']));
    }

    public function testUpdateFieldDefinition()
    {
        $fieldDef = new FieldDefinition();

        $mapperMock = $this->getMapperMock(
            ['toStorageFieldDefinition']
        );
        $mapperMock->expects(self::once())
            ->method('toStorageFieldDefinition')
            ->with(
                self::identicalTo($fieldDef),
                self::isInstanceOf(StorageFieldDefinition::class)
            );

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('updateFieldDefinition')
            ->with(
                self::equalTo(23),
                self::equalTo(1),
                $fieldDef
            );

        $storageDispatcherMock = $this->getStorageDispatcherMock();
        $storageDispatcherMock
            ->expects(self::once())
            ->method('storeFieldConstraintsData')
            ->with($fieldDef);

        $handler = $this->getHandler();
        $handler->updateFieldDefinition(23, 1, $fieldDef);
    }

    public function testPublish()
    {
        $handler = $this->getPartlyMockedHandler(['load']);
        $updateHandlerMock = $this->getUpdateHandlerMock();

        $handler->expects(self::exactly(2))
            ->method('load')
            ->with(
                self::equalTo(23),
                self::logicalOr(
                    self::equalTo(0),
                    self::equalTo(1)
                )
            )->will(
                self::returnValue(new Type())
            );

        $updateHandlerMock->expects(self::never())
            ->method('updateContentObjects');
        $updateHandlerMock->expects(self::once())
            ->method('deleteOldType')
            ->with(
                self::isInstanceOf(Type::class)
            );
        $updateHandlerMock->expects(self::once())
            ->method('publishNewType')
            ->with(
                self::isInstanceOf(Type::class),
                self::equalTo(0)
            );

        $handler->publish(23);
    }

    public function testPublishNoOldType()
    {
        $handler = $this->getPartlyMockedHandler(['load']);
        $updateHandlerMock = $this->getUpdateHandlerMock();

        $handler->expects(self::at(0))
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(1)
            )->will(
                self::returnValue(new Type())
            );

        $handler->expects(self::at(1))
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(0)
            )->will(
                self::throwException(new Exception\TypeNotFound(23, 0))
            );

        $updateHandlerMock->expects(self::never())
            ->method('updateContentObjects');
        $updateHandlerMock->expects(self::never())
            ->method('deleteOldType');
        $updateHandlerMock->expects(self::once())
            ->method('publishNewType')
            ->with(
                self::isInstanceOf(Type::class),
                self::equalTo(0)
            );

        $handler->publish(23);
    }

    /**
     * Returns a handler to test, based on mock objects.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Type\Handler
     */
    protected function getHandler()
    {
        return new Handler(
            $this->getGatewayMock(),
            $this->getMapperMock(),
            $this->getUpdateHandlerMock(),
            $this->getStorageDispatcherMock()
        );
    }

    /**
     * Returns a handler to test with $methods mocked.
     *
     * @param array $methods
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Type\Handler
     */
    protected function getPartlyMockedHandler(array $methods)
    {
        return $this->getMockBuilder(Handler::class)
            ->setMethods($methods)
            ->setConstructorArgs(
                [
                    $this->getGatewayMock(),
                    $this->getMapperMock(),
                    $this->getUpdateHandlerMock(),
                    $this->getStorageDispatcherMock(),
                ]
            )
            ->getMock();
    }

    /**
     * Returns a gateway mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Type\Gateway
     */
    protected function getGatewayMock()
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->getMockForAbstractClass(
                Gateway::class
            );
        }

        return $this->gatewayMock;
    }

    /**
     * Returns a mapper mock.
     *
     * @param array $methods
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Type\Mapper
     */
    protected function getMapperMock($methods = [])
    {
        if (!isset($this->mapperMock)) {
            $this->mapperMock = $this->getMockBuilder(Mapper::class)
                ->disableOriginalConstructor()
                ->setMethods($methods)
                ->getMock();
        }

        return $this->mapperMock;
    }

    /**
     * Returns a Update\Handler mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler
     */
    public function getUpdateHandlerMock()
    {
        if (!isset($this->updateHandlerMock)) {
            $this->updateHandlerMock = $this->getMockBuilder(UpdateHandler::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();
        }

        return $this->updateHandlerMock;
    }

    /**
     * @return \Ibexa\Core\Persistence\Legacy\Content\Type\StorageDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getStorageDispatcherMock(): StorageDispatcherInterface
    {
        if (!isset($this->storageDispatcherMock)) {
            $this->storageDispatcherMock = $this->createMock(StorageDispatcherInterface::class);
        }

        return $this->storageDispatcherMock;
    }

    /**
     * Returns a CreateStruct fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\CreateStruct
     */
    protected function getContentTypeCreateStructFixture()
    {
        $struct = new CreateStruct();
        $struct->status = 1;
        $struct->groupIds = [
            42,
        ];
        $struct->name = [
            'eng-GB' => 'test name',
        ];

        $fieldDefName = new FieldDefinition(['position' => 1]);
        $fieldDefShortDescription = new FieldDefinition(['position' => 2]);

        $struct->fieldDefinitions = [
            $fieldDefName,
            $fieldDefShortDescription,
        ];

        return $struct;
    }

    public function testRemoveContentTypeTranslation()
    {
        $mapperMock = $this->getMapperMock();
        $mapperMock->expects(self::once())
            ->method('createUpdateStructFromType')
            ->with(
                self::isInstanceOf(
                    Type::class
                )
            )
            ->will(
                self::returnValue(new UpdateStruct())
            );

        $handlerMock = $this->getMockBuilder(Handler::class)
            ->setMethods(['load', 'update'])
            ->setConstructorArgs([
                $this->getGatewayMock(),
                $mapperMock,
                $this->getUpdateHandlerMock(),
                $this->getStorageDispatcherMock(),
            ])
            ->getMock();

        $handlerMock->expects(self::once())
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(1)
            )
            ->will(self::returnValue(new Type(['id' => 23])));

        $handlerMock->expects(self::once())
            ->method('update')
            ->with(
                self::equalTo(23),
                self::equalTo(1),
                self::isInstanceOf(
                    UpdateStruct::class
                )
            )
            ->will(self::returnValue(new Type()));

        $res = $handlerMock->removeContentTypeTranslation(23, 'eng-GB');

        self::assertInstanceOf(
            Type::class,
            $res
        );
    }
}

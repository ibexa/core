<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Location\CreateStruct as LocationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Relation;
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct as RelationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\FieldHandler;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Handler;
use Ibexa\Core\Persistence\Legacy\Content\Language\Handler as LanguageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\TreeHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway as ContentTypeGateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway as UrlAliasGateway;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Handler
 */
class ContentHandlerTest extends TestCase
{
    private const RELATION_ID = 1;

    /**
     * Content handler to test.
     *
     * @var Handler
     */
    protected $contentHandler;

    /**
     * Gateway mock.
     *
     * @var Gateway
     */
    protected $gatewayMock;

    /**
     * Location gateway mock.
     *
     * @var LocationGateway
     */
    protected $locationGatewayMock;

    /**
     * Type gateway mock.
     *
     * @var ContentTypeGateway
     */
    protected $typeGatewayMock;

    /**
     * Mapper mock.
     *
     * @var Mapper
     */
    protected $mapperMock;

    /**
     * Field handler mock.
     *
     * @var FieldHandler
     */
    protected $fieldHandlerMock;

    /**
     * Location handler mock.
     *
     * @var TreeHandler
     */
    protected $treeHandlerMock;

    /**
     * Slug converter mock.
     *
     * @var SlugConverter
     */
    protected $slugConverterMock;

    /**
     * Location handler mock.
     *
     * @var UrlAliasGateway
     */
    protected $urlAliasGatewayMock;

    /**
     * ContentType handler mock.
     *
     * @var ContentTypeHandler
     */
    protected $contentTypeHandlerMock;

    /**
     * @var MockObject&LanguageHandler
     */
    private LanguageHandler $languageHandlerMock;

    /**
     * @todo Current method way to complex to test, refactor!
     */
    public function testCreate()
    {
        $handler = $this->getContentHandler();

        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $locationMock = $this->getLocationGatewayMock();
        $contentTypeHandlerMock = $this->getContentTypeHandlerMock();
        $contentTypeMock = $this->createMock(Type::class);
        $createStruct = $this->getCreateStructFixture();

        $contentTypeHandlerMock->expects(self::once())
            ->method('load')
            ->with($createStruct->typeId)
            ->will(self::returnValue($contentTypeMock));

        $mapperMock->expects(self::once())
            ->method('createVersionInfoFromCreateStruct')
            ->with(
                self::isInstanceOf(
                    CreateStruct::class
                )
            )->will(
                self::returnValue(
                    new VersionInfo(
                        [
                            'names' => [],
                            'contentInfo' => new ContentInfo(),
                        ]
                    )
                )
            );

        $gatewayMock->expects(self::once())
            ->method('insertContentObject')
            ->with(
                self::isInstanceOf(CreateStruct::class)
            )->will(self::returnValue(23));

        $gatewayMock->expects(self::once())
            ->method('insertVersion')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isType('array')
            )->will(self::returnValue(1));

        $fieldHandlerMock->expects(self::once())
            ->method('createNewFields')
            ->with(
                self::isInstanceOf(Content::class),
                self::isInstanceOf(Type::class)
            );

        $locationMock->expects(self::once())
            ->method('createNodeAssignment')
            ->with(
                self::isInstanceOf(
                    LocationCreateStruct::class
                ),
                self::equalTo(42),
                self::equalTo(3) // Location\Gateway::NODE_ASSIGNMENT_OP_CODE_CREATE
            );

        $res = $handler->create($createStruct);

        // @todo Make subsequent tests

        self::assertInstanceOf(
            Content::class,
            $res,
            'Content not created'
        );
        self::assertEquals(
            23,
            $res->versionInfo->contentInfo->id,
            'Content ID not set correctly'
        );
        self::assertInstanceOf(
            VersionInfo::class,
            $res->versionInfo,
            'Version infos not created'
        );
        self::assertEquals(
            1,
            $res->versionInfo->id,
            'Version ID not set correctly'
        );
        self::assertCount(
            2,
            $res->fields,
            'Fields not set correctly in version'
        );
    }

    public function testPublishFirstVersion()
    {
        $handler = $this->getPartlyMockedHandler(['loadVersionInfo']);

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $locationMock = $this->getLocationGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $metadataUpdateStruct = new MetadataUpdateStruct();

        $handler->expects(self::once())
            ->method('loadVersionInfo')
            ->with(23, 1)
            ->will(
                self::returnValue(
                    new VersionInfo([
                        'contentInfo' => new ContentInfo([
                            'currentVersionNo' => 1,
                            'mainLanguageCode' => 'eng-GB',
                        ]),
                        'names' => [
                            'eng-GB' => '',
                        ],
                    ])
                )
            );

        $contentRows = [['content_version_version' => 1]];

        $gatewayMock->expects(self::once())
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(1),
                self::equalTo(null)
            )->willReturn($contentRows);

        $gatewayMock->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(
                self::equalTo([['id' => 23, 'version' => 1]])
            )->will(
                self::returnValue([22])
            );

        $mapperMock->expects(self::once())
            ->method('extractContentFromRows')
            ->with(self::equalTo($contentRows), self::equalTo([22]))
            ->will(self::returnValue([$this->getContentFixtureForDraft()]));

        $fieldHandlerMock->expects(self::once())
            ->method('loadExternalFieldData')
            ->with(self::isInstanceOf(Content::class));

        $gatewayMock
            ->expects(self::once())
            ->method('updateContent')
            ->with(23, $metadataUpdateStruct);

        $locationMock
            ->expects(self::once())
            ->method('createLocationsFromNodeAssignments')
            ->with(23, 1);

        $locationMock
            ->expects(self::once())
            ->method('updateLocationsContentVersionNo')
            ->with(23, 1);

        $gatewayMock
            ->expects(self::once())
            ->method('setPublishedStatus')
            ->with(23, 1);

        $handler->publish(23, 1, $metadataUpdateStruct);
    }

    public function testPublish()
    {
        $handler = $this->getPartlyMockedHandler(['loadVersionInfo', 'setStatus']);

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $locationMock = $this->getLocationGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $metadataUpdateStruct = new MetadataUpdateStruct();

        $handler->expects(self::once())
            ->method('loadVersionInfo')
            ->with(23, 2)
            ->will(
                self::returnValue(
                    new VersionInfo([
                        'contentInfo' => new ContentInfo([
                            'currentVersionNo' => 1,
                            'mainLanguageCode' => 'eng-GB',
                        ]),
                        'names' => [
                            'eng-GB' => '',
                        ],
                    ])
                )
            );

        $handler
            ->expects(self::once())
            ->method('setStatus')
            ->with(23, VersionInfo::STATUS_ARCHIVED, 1);

        $contentRows = [['content_version_version' => 2]];

        $gatewayMock->expects(self::once())
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(2),
                self::equalTo(null)
            )
            ->willReturn($contentRows);

        $gatewayMock->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(
                self::equalTo([['id' => 23, 'version' => 2]])
            )->will(
                self::returnValue([22])
            );

        $mapperMock->expects(self::once())
            ->method('extractContentFromRows')
            ->with(self::equalTo($contentRows), self::equalTo([22]))
            ->will(self::returnValue([$this->getContentFixtureForDraft()]));

        $fieldHandlerMock->expects(self::once())
            ->method('loadExternalFieldData')
            ->with(self::isInstanceOf(Content::class));

        $gatewayMock
            ->expects(self::once())
            ->method('updateContent')
            ->with(23, $metadataUpdateStruct, self::isInstanceOf(VersionInfo::class));

        $locationMock
            ->expects(self::once())
            ->method('createLocationsFromNodeAssignments')
            ->with(23, 2);

        $locationMock
            ->expects(self::once())
            ->method('updateLocationsContentVersionNo')
            ->with(23, 2);

        $gatewayMock
            ->expects(self::once())
            ->method('setPublishedStatus')
            ->with(23, 2);

        $handler->publish(23, 2, $metadataUpdateStruct);
    }

    public function testCreateDraftFromVersion()
    {
        $handler = $this->getPartlyMockedHandler(['load']);

        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $languageHandlerMock = $this->getLanguageHandlerMock();
        $contentTypeHandlerMock = $this->getContentTypeHandlerMock();

        $handler->expects(self::once())
            ->method('load')
            ->with(23, 2)
            ->will(self::returnValue($this->getContentFixtureForDraft()));

        $mapperMock->expects(self::once())
            ->method('createVersionInfoForContent')
            ->with(
                self::isInstanceOf(Content::class),
                self::equalTo(3),
                self::equalTo(14)
            )->will(
                self::returnValue(
                    new VersionInfo(
                        [
                            'names' => [],
                            'versionNo' => 3,
                            'contentInfo' => new ContentInfo(),
                        ]
                    )
                )
            );

        $languageHandlerMock->method('loadByLanguageCode')
            ->willReturn(new Content\Language());

        $contentTypeHandlerMock->method('load')
            ->willReturn(new Type());

        $gatewayMock->expects(self::once())
            ->method('insertVersion')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                $this->getContentFixtureForDraft()->fields
            )->will(self::returnValue(42));

        $gatewayMock->expects(self::once())
            ->method('getLastVersionNumber')
            ->with(self::equalTo(23))
            ->will(self::returnValue(2));

        $fieldHandlerMock->expects(self::once())
            ->method('createExistingFieldsInNewVersion')
            ->with(self::isInstanceOf(Content::class));

        $relationData = [
            [
                'content_link_content_type_field_definition_id' => 0,
                'content_link_to_contentobject_id' => 42,
                'content_link_relation_type' => 1,
            ],
        ];

        $gatewayMock->expects(self::once())
            ->method('loadRelations')
            ->with(
                self::equalTo(23),
                self::equalTo(2)
            )
            ->will(self::returnValue($relationData));

        $relationStruct = new RelationCreateStruct(
            [
                'sourceContentId' => 23,
                'sourceContentVersionNo' => 3,
                'sourceFieldDefinitionId' => 0,
                'destinationContentId' => 42,
                'type' => 1,
            ]
        );

        $gatewayMock->expects(self::once())
            ->method('insertRelation')
            ->with(self::equalTo($relationStruct));

        $result = $handler->createDraftFromVersion(23, 2, 14);

        self::assertInstanceOf(
            Content::class,
            $result
        );
        self::assertEquals(
            42,
            $result->versionInfo->id
        );
    }

    public function testLoad()
    {
        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();

        $contentRows = [['content_version_version' => 2]];

        $gatewayMock->expects(self::once())
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(2),
                self::equalTo(['eng-GB'])
            )->will(
                self::returnValue($contentRows)
            );

        $gatewayMock->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(
                self::equalTo([['id' => 23, 'version' => 2]])
            )->will(
                self::returnValue([22])
            );

        $mapperMock->expects(self::once())
            ->method('extractContentFromRows')
            ->with(self::equalTo($contentRows), self::equalTo([22]))
            ->will(self::returnValue([$this->getContentFixtureForDraft()]));

        $fieldHandlerMock->expects(self::once())
            ->method('loadExternalFieldData')
            ->with(self::isInstanceOf(Content::class));

        $result = $handler->load(23, 2, ['eng-GB']);

        self::assertEquals(
            $result,
            $this->getContentFixtureForDraft()
        );
    }

    public function testLoadContentList()
    {
        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $contentRows = [
            ['content_id' => 2, 'content_version_version' => 2],
            ['content_id' => 3, 'content_version_version' => 1],
        ];
        $gatewayMock->expects(self::once())
            ->method('loadContentList')
            ->with([2, 3], ['eng-GB', 'eng-US'])
            ->willReturn($contentRows);

        $nameDataRows = [
            ['content_name_contentobject_id' => 2, 'content_name_content_version' => 2],
            ['content_name_contentobject_id' => 3, 'content_name_content_version' => 1],
        ];

        $gatewayMock->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => 2, 'version' => 2], ['id' => 3, 'version' => 1]]))
            ->willReturn($nameDataRows);

        $expected = [
            2 => $this->getContentFixtureForDraft(2, 2),
            3 => $this->getContentFixtureForDraft(3, 1),
        ];
        $mapperMock->expects(self::exactly(2))
            ->method('extractContentFromRows')
            ->willReturnCallback(static function (
                $rows,
                $nameData
            ) use ($contentRows, $nameDataRows, $expected) {
                if ($rows === [$contentRows[0]] && $nameData === [$nameDataRows[0]]) {
                    return [$expected[2]];
                }
                if ($rows === [$contentRows[1]] && $nameData === [$nameDataRows[1]]) {
                    return [$expected[3]];
                }

                return [];
            });

        $fieldHandlerMock->expects(self::exactly(2))
            ->method('loadExternalFieldData')
            ->with(self::isInstanceOf(Content::class));

        $result = $handler->loadContentList([2, 3], ['eng-GB', 'eng-US']);

        self::assertEquals(
            $expected,
            $result
        );
    }

    public function testLoadContentInfoByRemoteId()
    {
        $contentInfoData = [new ContentInfo()];
        $this->getGatewayMock()->expects(self::once())
            ->method('loadContentInfoByRemoteId')
            ->with(
                self::equalTo('15b256dbea2ae72418ff5facc999e8f9')
            )->will(
                self::returnValue([42])
            );

        $this->getMapperMock()->expects(self::once())
            ->method('extractContentInfoFromRow')
            ->with(self::equalTo([42]))
            ->will(self::returnValue($contentInfoData));

        self::assertSame(
            $contentInfoData,
            $this->getContentHandler()->loadContentInfoByRemoteId('15b256dbea2ae72418ff5facc999e8f9')
        );
    }

    public function testLoadErrorNotFound()
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('load')
            ->will(
                self::returnValue([])
            );

        $result = $handler->load(23, 2, ['eng-GB']);
    }

    /**
     * Returns a Content for {@link testCreateDraftFromVersion()}.
     *
     * @param int $id Optional id
     * @param int $versionNo Optional version number
     *
     * @return Content
     */
    protected function getContentFixtureForDraft(
        int $id = 23,
        int $versionNo = 2
    ) {
        $content = new Content();
        $content->versionInfo = new VersionInfo();
        $content->versionInfo->versionNo = $versionNo;

        $content->versionInfo->contentInfo = new ContentInfo(['id' => $id]);

        $field = new Field();
        $field->versionNo = $versionNo;

        $content->fields = [$field];

        return $content;
    }

    public function testUpdateContent()
    {
        $handler = $this->getPartlyMockedHandler(['load', 'loadContentInfo']);

        $gatewayMock = $this->getGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $contentTypeHandlerMock = $this->getContentTypeHandlerMock();
        $contentTypeMock = $this->createMock(Type::class);
        $contentStub = new Content(
            [
                'versionInfo' => new VersionInfo(
                    [
                        'contentInfo' => new ContentInfo(
                            [
                                'contentTypeId' => 4242,
                            ]
                        ),
                    ]
                ),
            ]
        );

        $contentTypeHandlerMock->expects(self::once())
            ->method('load')
            ->with($contentStub->versionInfo->contentInfo->contentTypeId)
            ->will(self::returnValue($contentTypeMock));

        $gatewayMock->expects(self::once())
            ->method('updateContent')
            ->with(14, self::isInstanceOf(MetadataUpdateStruct::class));
        $gatewayMock->expects(self::once())
            ->method('updateVersion')
            ->with(14, 4, self::isInstanceOf(UpdateStruct::class));

        $fieldHandlerMock->expects(self::once())
            ->method('updateFields')
            ->with(
                self::isInstanceOf(Content::class),
                self::isInstanceOf(UpdateStruct::class),
                self::isInstanceOf(Type::class)
            );

        $handler->expects(self::exactly(2))
            ->method('load')
            ->with(14, 4)
            ->willReturn($contentStub);

        $handler->expects(self::once())
            ->method('loadContentInfo')
            ->with(14);

        $resultContent = $handler->updateContent(
            14, // ContentId
            4, // VersionNo
            new UpdateStruct(
                [
                    'creatorId' => 14,
                    'modificationDate' => time(),
                    'initialLanguageId' => 2,
                    'fields' => [
                        new Field(
                            [
                                'id' => 23,
                                'fieldDefinitionId' => 42,
                                'type' => 'some-type',
                                'value' => new FieldValue(),
                            ]
                        ),
                        new Field(
                            [
                                'id' => 23,
                                'fieldDefinitionId' => 43,
                                'type' => 'some-type',
                                'value' => new FieldValue(),
                            ]
                        ),
                    ],
                ]
            )
        );

        $resultContentInfo = $handler->updateMetadata(
            14, // ContentId
            new MetadataUpdateStruct(
                [
                    'ownerId' => 14,
                    'name' => 'Some name',
                    'modificationDate' => time(),
                    'alwaysAvailable' => true,
                ]
            )
        );
    }

    public function testUpdateMetadata()
    {
        $handler = $this->getPartlyMockedHandler(['load', 'loadContentInfo']);

        $gatewayMock = $this->getGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $updateStruct = new MetadataUpdateStruct(
            [
                'ownerId' => 14,
                'name' => 'Some name',
                'modificationDate' => time(),
                'alwaysAvailable' => true,
            ]
        );

        $gatewayMock->expects(self::once())
            ->method('updateContent')
            ->with(14, $updateStruct);

        $handler->expects(self::once())
            ->method('loadContentInfo')
            ->with(14)
            ->will(
                self::returnValue(
                    $this->createMock(ContentInfo::class)
                )
            );

        $resultContentInfo = $handler->updateMetadata(
            14, // ContentId
            $updateStruct
        );
        self::assertInstanceOf(ContentInfo::class, $resultContentInfo);
    }

    public function testUpdateMetadataUpdatesPathIdentificationString()
    {
        $handler = $this->getPartlyMockedHandler(['load', 'loadContentInfo']);
        $locationGatewayMock = $this->getLocationGatewayMock();
        $slugConverterMock = $this->getSlugConverterMock();
        $urlAliasGatewayMock = $this->getUrlAliasGatewayMock();
        $gatewayMock = $this->getGatewayMock();
        $updateStruct = new MetadataUpdateStruct(['mainLanguageId' => 2]);

        $gatewayMock->expects(self::once())
            ->method('updateContent')
            ->with(14, $updateStruct);

        $locationGatewayMock->expects(self::once())
            ->method('loadLocationDataByContent')
            ->with(14)
            ->will(
                self::returnValue(
                    [
                        [
                            'node_id' => 100,
                            'parent_node_id' => 200,
                        ],
                    ]
                )
            );

        $urlAliasGatewayMock->expects(self::once())
            ->method('loadLocationEntries')
            ->with(100, false, 2)
            ->will(
                self::returnValue(
                    [
                        [
                            'text' => 'slug',
                        ],
                    ]
                )
            );

        $slugConverterMock->expects(self::once())
            ->method('convert')
            ->with('slug', 'node_100', 'urlalias_compat')
            ->will(self::returnValue('transformed_slug'));

        $locationGatewayMock->expects(self::once())
            ->method('updatePathIdentificationString')
            ->with(100, 200, 'transformed_slug');

        $handler->expects(self::once())
            ->method('loadContentInfo')
            ->with(14)
            ->will(
                self::returnValue(
                    $this->createMock(ContentInfo::class)
                )
            );

        $handler->updateMetadata(
            14, // ContentId
            $updateStruct
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testLoadRelation(): void
    {
        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $relationFixture = $this->getRelationFixture();

        $gatewayMock
            ->expects(self::once())
            ->method('loadRelation')
            ->with(self::RELATION_ID)
            ->willReturn([self::RELATION_ID]);

        $mapperMock
            ->expects(self::once())
            ->method('extractRelationFromRow')
            ->with([self::RELATION_ID])
            ->willReturn($relationFixture);

        $result = $handler->loadRelation(self::RELATION_ID);

        self::assertEquals(
            $result,
            $relationFixture
        );
    }

    public function testLoadRelationList(): void
    {
        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();

        $gatewayMock->expects(self::once())
            ->method('listRelations')
            ->with(
                self::equalTo(23)
            )->will(
                self::returnValue([42])
            );

        $mapperMock->expects(self::once())
            ->method('extractRelationsFromRows')
            ->with(self::equalTo([42]))
            ->will(self::returnValue([$this->getRelationFixture()]));

        $result = $handler->loadRelationList(23, 10);

        self::assertEquals(
            $result,
            [$this->getRelationFixture()]
        );
    }

    public function testLoadReverseRelations()
    {
        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();

        $gatewayMock->expects(self::once())
            ->method('loadReverseRelations')
            ->with(
                self::equalTo(23),
                self::equalTo(null)
            )->will(
                self::returnValue([42])
            );

        $mapperMock->expects(self::once())
            ->method('extractRelationsFromRows')
            ->with(self::equalTo([42]))
            ->will(self::returnValue($this->getRelationFixture()));

        $result = $handler->loadReverseRelations(23);

        self::assertEquals(
            $result,
            $this->getRelationFixture()
        );
    }

    public function testAddRelation()
    {
        // expected relation object after creation
        $expectedRelationObject = new Relation();
        $expectedRelationObject->id = 42; // mocked value, not a real one
        $expectedRelationObject->sourceContentId = 23;
        $expectedRelationObject->sourceContentVersionNo = 1;
        $expectedRelationObject->destinationContentId = 66;
        $expectedRelationObject->type = RelationType::COMMON->value;

        // relation create struct
        $relationCreateStruct = new RelationCreateStruct();
        $relationCreateStruct->destinationContentId = 66;
        $relationCreateStruct->sourceContentId = 23;
        $relationCreateStruct->sourceContentVersionNo = 1;
        $relationCreateStruct->type = RelationType::COMMON->value;

        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();

        $mapperMock->expects(self::once())
            ->method('createRelationFromCreateStruct')
            // @todo Connected with the todo above
            ->with(self::equalTo($relationCreateStruct))
            ->will(self::returnValue($expectedRelationObject));

        $gatewayMock->expects(self::once())
            ->method('insertRelation')
            ->with(self::equalTo($relationCreateStruct))
            ->will(
                // @todo Should this return a row as if it was selected from the database, the id... ? Check with other, similar create methods
                self::returnValue(42)
            );

        $result = $handler->addRelation($relationCreateStruct);

        self::assertEquals(
            $result,
            $expectedRelationObject
        );
    }

    public function testRemoveRelation()
    {
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('deleteRelation')
            ->with(
                self::equalTo(1),
                self::equalTo(RelationType::COMMON->value),
            );

        $this->getContentHandler()->removeRelation(1, RelationType::COMMON->value);
    }

    protected function getRelationFixture()
    {
        $relation = new Relation();
        $relation->id = self::RELATION_ID;
        $relation->sourceContentId = 23;
        $relation->sourceContentVersionNo = 1;
        $relation->destinationContentId = 69;

        return $relation;
    }

    /**
     * Returns a CreateStruct fixture.
     *
     * @return CreateStruct
     */
    public function getCreateStructFixture()
    {
        $struct = new CreateStruct();

        $struct->typeId = 4242;

        $firstField = new Field();
        $firstField->type = 'some-type';
        $firstField->value = new FieldValue();

        $secondField = clone $firstField;

        $struct->fields = [
            $firstField, $secondField,
        ];

        $struct->locations = [
            new LocationCreateStruct(
                ['parentId' => 42]
            ),
        ];

        $struct->name = [
            'eng-GB' => 'This is a test name',
        ];

        return $struct;
    }

    public function testLoadDraftsForUser()
    {
        $handler = $this->getContentHandler();
        $rows = [['content_version_contentobject_id' => 42, 'content_version_version' => 2]];

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();

        $gatewayMock->expects(self::once())
            ->method('listVersionsForUser')
            ->with(self::equalTo(23))
            ->will(self::returnValue($rows));

        $gatewayMock->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => 42, 'version' => 2]]))
            ->will(self::returnValue([]));

        $mapperMock->expects(self::once())
            ->method('extractVersionInfoListFromRows')
            ->with(self::equalTo($rows), self::equalTo([]))
            ->will(self::returnValue([new VersionInfo()]));

        $res = $handler->loadDraftsForUser(23);

        self::assertEquals(
            [new VersionInfo()],
            $res
        );
    }

    public function testListVersions()
    {
        $handler = $this->getContentHandler();

        $treeHandlerMock = $this->getTreeHandlerMock();

        $treeHandlerMock
            ->expects(self::once())
            ->method('listVersions')
            ->with(23)
            ->will(self::returnValue([new VersionInfo()]));

        $versions = $handler->listVersions(23);

        self::assertEquals(
            [new VersionInfo()],
            $versions
        );
    }

    public function testRemoveRawContent()
    {
        $handler = $this->getContentHandler();
        $treeHandlerMock = $this->getTreeHandlerMock();

        $treeHandlerMock
            ->expects(self::once())
            ->method('removeRawContent')
            ->with(23);

        $handler->removeRawContent(23);
    }

    /**
     * Test for the deleteContent() method.
     */
    public function testDeleteContentWithLocations()
    {
        $handlerMock = $this->getPartlyMockedHandler(['getAllLocationIds']);
        $gatewayMock = $this->getGatewayMock();
        $treeHandlerMock = $this->getTreeHandlerMock();

        $gatewayMock->expects(self::once())
            ->method('getAllLocationIds')
            ->with(self::equalTo(23))
            ->will(self::returnValue([42, 24]));
        $treeHandlerMock->expects(self::exactly(2))
            ->method('removeSubtree')
            ->with(
                self::logicalOr(
                    self::equalTo(42),
                    self::equalTo(24)
                )
            );

        $handlerMock->deleteContent(23);
    }

    /**
     * Test for the deleteContent() method.
     */
    public function testDeleteContentWithoutLocations()
    {
        $handlerMock = $this->getPartlyMockedHandler(['removeRawContent']);
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('getAllLocationIds')
            ->with(self::equalTo(23))
            ->will(self::returnValue([]));
        $handlerMock->expects(self::once())
            ->method('removeRawContent')
            ->with(self::equalTo(23));

        $handlerMock->deleteContent(23);
    }

    public function testDeleteVersion()
    {
        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $locationHandlerMock = $this->getLocationGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();

        $rows = [['content_version_version' => 2]];

        // Load VersionInfo to delete fields
        $gatewayMock->expects(self::once())
            ->method('loadVersionInfo')
            ->with(self::equalTo(225), self::equalTo(2))
            ->willReturn($rows);

        $gatewayMock->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => 225, 'version' => 2]]))
            ->will(self::returnValue([22]));

        $mapperMock->expects(self::once())
            ->method('extractVersionInfoListFromRows')
            ->with(self::equalTo($rows), self::equalTo([22]))
            ->will(self::returnValue([new VersionInfo()]));

        $locationHandlerMock->expects(self::once())
            ->method('deleteNodeAssignment')
            ->with(
                self::equalTo(225),
                self::equalTo(2)
            );

        $fieldHandlerMock->expects(self::once())
            ->method('deleteFields')
            ->with(
                self::equalTo(225),
                self::isInstanceOf(VersionInfo::class)
            );
        $gatewayMock->expects(self::once())
            ->method('deleteRelations')
            ->with(
                self::equalTo(225),
                self::equalTo(2)
            );
        $gatewayMock->expects(self::once())
            ->method('deleteVersions')
            ->with(
                self::equalTo(225),
                self::equalTo(2)
            );
        $gatewayMock->expects(self::once())
            ->method('deleteNames')
            ->with(
                self::equalTo(225),
                self::equalTo(2)
            );

        $handler->deleteVersion(225, 2);
    }

    public function testCopySingleVersion()
    {
        $handler = $this->getPartlyMockedHandler(['load', 'internalCreate']);
        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();

        $handler->expects(
            self::once()
        )->method(
            'load'
        )->with(
            self::equalTo(23),
            self::equalTo(32)
        )->will(
            self::returnValue(new Content())
        );

        $mapperMock->expects(
            self::once()
        )->method(
            'createCreateStructFromContent'
        )->with(
            self::isInstanceOf(Content::class)
        )->will(
            self::returnValue(new CreateStruct())
        );

        $handler->expects(
            self::once()
        )->method(
            'internalCreate'
        )->with(
            self::isInstanceOf(CreateStruct::class),
            self::equalTo(32)
        )->will(
            self::returnValue(
                new Content(
                    [
                        'versionInfo' => new VersionInfo(['contentInfo' => new ContentInfo(['id' => 24])]),
                    ]
                )
            )
        );

        $gatewayMock->expects(self::once())
            ->method('copyRelations')
            ->with(
                self::equalTo(23),
                self::equalTo(24),
                self::equalTo(32)
            )
            ->will(self::returnValue(null));

        $result = $handler->copy(23, 32);

        self::assertInstanceOf(
            Content::class,
            $result
        );
    }

    public function testCopyAllVersions()
    {
        $handler = $this->getPartlyMockedHandler(
            [
                'loadContentInfo',
                'load',
                'internalCreate',
                'listVersions',
            ]
        );
        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $contentTypeHandlerMock = $this->getContentTypeHandlerMock();
        $contentTypeMock = $this->createMock(Type::class);
        $time = time();
        $createStructStub = new CreateStruct(
            [
                'modified' => $time,
                'typeId' => 4242,
            ]
        );

        $contentTypeHandlerMock->expects(self::once())
            ->method('load')
            ->with($createStructStub->typeId)
            ->will(self::returnValue($contentTypeMock));

        $handler->expects(self::once())
            ->method('loadContentInfo')
            ->with(self::equalTo(23))
            ->will(self::returnValue(new ContentInfo(['currentVersionNo' => 2])));

        $loadCallCount = 0;
        $handler->expects(self::exactly(2))
            ->method('load')
            ->willReturnCallback(static function (
                $contentId,
                $versionNo
            ) use (&$loadCallCount) {
                ++$loadCallCount;
                if ($loadCallCount === 1) {
                    self::assertEquals(23, $contentId);
                    self::assertEquals(2, $versionNo);

                    return new Content();
                }
                // Second call is for loading version 1
                self::assertEquals(23, $contentId);
                self::assertEquals(1, $versionNo);

                return new Content(
                    [
                        'versionInfo' => new VersionInfo(
                            [
                                'names' => ['eng-US' => 'Test'],
                                'contentInfo' => new ContentInfo(
                                    [
                                        'id' => 24,
                                        'alwaysAvailable' => true,
                                    ]
                                ),
                            ]
                        ),
                        'fields' => [],
                    ]
                );
            });

        $mapperMock->expects(self::once())
            ->method('createCreateStructFromContent')
            ->with(self::isInstanceOf(Content::class))
            ->will(
                self::returnValue($createStructStub)
            );

        $handler->expects(self::once())
            ->method('internalCreate')
            ->with(
                self::isInstanceOf(CreateStruct::class),
                self::equalTo(2)
            )->will(
                self::returnValue(
                    new Content(
                        [
                            'versionInfo' => new VersionInfo(
                                [
                                    'contentInfo' => new ContentInfo(['id' => 24]),
                                ]
                            ),
                        ]
                    )
                )
            );

        $handler->expects(self::once())
            ->method('listVersions')
            ->with(self::equalTo(23))
            ->will(
                self::returnValue(
                    [
                        new VersionInfo(['versionNo' => 1]),
                        new VersionInfo(['versionNo' => 2]),
                    ]
                )
            );

        $versionInfo = new VersionInfo(
            [
                'names' => ['eng-US' => 'Test'],
                'contentInfo' => new ContentInfo(
                    [
                        'id' => 24,
                        'alwaysAvailable' => true,
                    ]
                ),
            ]
        );

        $versionInfo->creationDate = $time;
        $versionInfo->modificationDate = $time;
        $gatewayMock->expects(self::once())
            ->method('insertVersion')
            ->with(
                self::equalTo($versionInfo),
                self::isType('array')
            )->will(self::returnValue(42));

        $versionInfo = clone $versionInfo;
        $versionInfo->id = 42;
        $fieldHandlerMock->expects(self::once())
            ->method('createNewFields')
            ->with(
                self::equalTo(
                    new Content(
                        [
                            'versionInfo' => $versionInfo,
                            'fields' => [],
                        ]
                    )
                ),
                self::isInstanceOf(Type::class)
            );

        $gatewayMock->expects(self::once())
            ->method('setName')
            ->with(
                self::equalTo(24),
                self::equalTo(1),
                self::equalTo('Test'),
                self::equalTo('eng-US')
            );

        $gatewayMock->expects(self::once())
            ->method('copyRelations')
            ->with(
                self::equalTo(23),
                self::equalTo(24),
                self::equalTo(null)
            )
            ->will(self::returnValue(null));

        $result = $handler->copy(23);

        self::assertInstanceOf(
            Content::class,
            $result
        );
    }

    public function testCopyThrowsNotFoundExceptionContentNotFound()
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getContentHandler();

        $treeHandlerMock = $this->getTreeHandlerMock();
        $treeHandlerMock
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with(self::equalTo(23))
            ->will(
                self::throwException(new NotFoundException('ContentInfo', 23))
            );

        $handler->copy(23);
    }

    public function testCopyThrowsNotFoundExceptionVersionNotFound()
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects(self::once())
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(32),
            )
            ->will(self::returnValue([]));

        $result = $handler->copy(23, 32);
    }

    public function testSetStatus()
    {
        $handler = $this->getContentHandler();

        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects(self::once())
            ->method('setStatus')
            ->with(23, 5, 2)
            ->will(self::returnValue(true));

        self::assertTrue(
            $handler->setStatus(23, 2, 5)
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Persistence\Legacy\Content\Handler::loadVersionInfoList
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testLoadVersionInfoList(): void
    {
        $handler = $this->getContentHandler();
        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();

        $contentIds = [2, 3];
        $versionInfo1 = new VersionInfo([
            'contentInfo' => new ContentInfo(['id' => 2]),
        ]);
        $versionInfo2 = new VersionInfo([
            'contentInfo' => new ContentInfo(['id' => 3]),
        ]);

        $versionRows = [
            ['content_id' => 2, 'content_version_version' => 2],
            ['content_id' => 3, 'content_version_version' => 1],
        ];

        $gatewayMock->expects(self::once())
            ->method('loadVersionInfoList')
            ->with($contentIds)
            ->willReturn($versionRows);

        $nameDataRows = [
            ['content_name_contentobject_id' => 2, 'content_name_content_version' => 2],
            ['content_name_contentobject_id' => 3, 'content_name_content_version' => 1],
        ];

        $gatewayMock->expects(self::once())
            ->method('loadVersionedNameData')
            ->with([['id' => 2, 'version' => 2], ['id' => 3, 'version' => 1]])
            ->willReturn($nameDataRows);

        $mapperMock->expects(self::once())
            ->method('extractVersionInfoListFromRows')
            ->with($versionRows)
            ->willReturn([
                $versionInfo1,
                $versionInfo2,
            ]);

        $expected = [
            2 => $versionInfo1,
            3 => $versionInfo2,
        ];

        $result = $handler->loadVersionInfoList($contentIds);

        self::assertEquals($expected, $result);
    }

    /**
     * Returns the handler to test.
     *
     * @return Handler
     */
    protected function getContentHandler()
    {
        if (!isset($this->contentHandler)) {
            $this->contentHandler = new Handler(
                $this->getGatewayMock(),
                $this->getLocationGatewayMock(),
                $this->getMapperMock(),
                $this->getFieldHandlerMock(),
                $this->getSlugConverterMock(),
                $this->getUrlAliasGatewayMock(),
                $this->getContentTypeHandlerMock(),
                $this->getTreeHandlerMock(),
                $this->getLanguageHandlerMock(),
            );
        }

        return $this->contentHandler;
    }

    /**
     * Returns the handler to test with $methods mocked.
     *
     * @param string[] $methods
     *
     * @return Handler
     */
    protected function getPartlyMockedHandler(array $methods)
    {
        return $this->getMockBuilder(Handler::class)
            ->setMethods($methods)
            ->setConstructorArgs(
                [
                    $this->getGatewayMock(),
                    $this->getLocationGatewayMock(),
                    $this->getMapperMock(),
                    $this->getFieldHandlerMock(),
                    $this->getSlugConverterMock(),
                    $this->getUrlAliasGatewayMock(),
                    $this->getContentTypeHandlerMock(),
                    $this->getTreeHandlerMock(),
                    $this->getLanguageHandlerMock(),
                ]
            )
            ->getMock();
    }

    /**
     * Returns a TreeHandler mock.
     *
     * @return MockObject|TreeHandler
     */
    protected function getTreeHandlerMock()
    {
        if (!isset($this->treeHandlerMock)) {
            $this->treeHandlerMock = $this->createMock(TreeHandler::class);
        }

        return $this->treeHandlerMock;
    }

    /**
     * Returns a ContentTypeHandler mock.
     *
     * @return MockObject|ContentTypeHandler
     */
    protected function getContentTypeHandlerMock()
    {
        if (!isset($this->contentTypeHandlerMock)) {
            $this->contentTypeHandlerMock = $this->createMock(ContentTypeHandler::class);
        }

        return $this->contentTypeHandlerMock;
    }

    /**
     * @return MockObject&LanguageHandler
     */
    protected function getLanguageHandlerMock(): LanguageHandler
    {
        if (!isset($this->languageHandlerMock)) {
            $this->languageHandlerMock = $this->createMock(LanguageHandler::class);
        }

        return $this->languageHandlerMock;
    }

    /**
     * Returns a FieldHandler mock.
     *
     * @return FieldHandler
     */
    protected function getFieldHandlerMock()
    {
        if (!isset($this->fieldHandlerMock)) {
            $this->fieldHandlerMock = $this->createMock(FieldHandler::class);
        }

        return $this->fieldHandlerMock;
    }

    /**
     * Returns a Mapper mock.
     *
     * @return Mapper
     */
    protected function getMapperMock()
    {
        if (!isset($this->mapperMock)) {
            $this->mapperMock = $this->createMock(Mapper::class);
        }

        return $this->mapperMock;
    }

    /**
     * Returns a Location Gateway mock.
     *
     * @return LocationGateway
     */
    protected function getLocationGatewayMock()
    {
        if (!isset($this->locationGatewayMock)) {
            $this->locationGatewayMock = $this->createMock(LocationGateway::class);
        }

        return $this->locationGatewayMock;
    }

    /**
     * Returns a content type gateway mock.
     *
     * @return ContentTypeGateway
     */
    protected function getTypeGatewayMock()
    {
        if (!isset($this->typeGatewayMock)) {
            $this->typeGatewayMock = $this->createMock(ContentTypeGateway::class);
        }

        return $this->typeGatewayMock;
    }

    /**
     * Returns a mock object for the Content Gateway.
     *
     * @return Gateway|MockObject
     */
    protected function getGatewayMock()
    {
        if (!isset($this->gatewayMock)) {
            try {
                $this->gatewayMock = $this->getMockForAbstractClass(ContentGateway::class);
            } catch (ReflectionException $e) {
                self::fail($e);
            }
        }

        return $this->gatewayMock;
    }

    /**
     * Returns a mock object for the UrlAlias Handler.
     *
     * @return SlugConverter
     */
    protected function getSlugConverterMock()
    {
        if (!isset($this->slugConverterMock)) {
            $this->slugConverterMock = $this->createMock(SlugConverter::class);
        }

        return $this->slugConverterMock;
    }

    /**
     * Returns a mock object for the UrlAlias Gateway.
     *
     * @return UrlAliasGateway
     */
    protected function getUrlAliasGatewayMock()
    {
        if (!isset($this->urlAliasGatewayMock)) {
            $this->urlAliasGatewayMock = $this->getMockForAbstractClass(UrlAliasGateway::class);
        }

        return $this->urlAliasGatewayMock;
    }
}

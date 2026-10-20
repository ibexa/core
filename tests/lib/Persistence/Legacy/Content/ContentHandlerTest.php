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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

#[CoversClass(Handler::class)]
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

    private LanguageHandler & MockObject $languageHandlerMock;

    /**
     * @todo Current method way to complex to test, refactor!
     */
    public function testCreate(): void
    {
        $handler = $this->getContentHandler();

        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $locationMock = $this->getLocationGatewayMock();
        $contentTypeHandlerMock = $this->getContentTypeHandlerMock();
        $contentTypeMock = self::createStub(Type::class);
        $createStruct = $this->getCreateStructFixture();

        $contentTypeHandlerMock->expects($this->once())
            ->method('load')
            ->with($createStruct->typeId)
            ->will($this->returnValue($contentTypeMock));

        $mapperMock->expects($this->once())
            ->method('createVersionInfoFromCreateStruct')
            ->with(
                self::isInstanceOf(
                    CreateStruct::class
                )
            )->will(
                $this->returnValue(
                    new VersionInfo(
                        [
                            'names' => [],
                            'contentInfo' => new ContentInfo(),
                        ]
                    )
                )
            );

        $gatewayMock->expects($this->once())
            ->method('insertContentObject')
            ->with(
                self::isInstanceOf(CreateStruct::class)
            )->will($this->returnValue(23));

        $gatewayMock->expects($this->once())
            ->method('insertVersion')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                self::isArray()
            )->will($this->returnValue(1));

        $fieldHandlerMock->expects($this->once())
            ->method('createNewFields')
            ->with(
                self::isInstanceOf(Content::class),
                self::isInstanceOf(Type::class)
            );

        $locationMock->expects($this->once())
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

    public function testPublishFirstVersion(): void
    {
        $handler = $this->getPartlyMockedHandler(['loadVersionInfo']);

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $locationMock = $this->getLocationGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $metadataUpdateStruct = new MetadataUpdateStruct();

        $handler->expects($this->once())
            ->method('loadVersionInfo')
            ->with(23, 1)
            ->will(
                $this->returnValue(
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

        $gatewayMock->expects($this->once())
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(1),
                self::equalTo(null)
            )->willReturn($contentRows);

        $gatewayMock->expects($this->once())
            ->method('loadVersionedNameData')
            ->with(
                self::equalTo([['id' => 23, 'version' => 1]])
            )->will(
                $this->returnValue([22])
            );

        $mapperMock->expects($this->once())
            ->method('extractContentFromRows')
            ->with(self::equalTo($contentRows), self::equalTo([22]))
            ->will($this->returnValue([$this->getContentFixtureForDraft()]));

        $fieldHandlerMock->expects($this->once())
            ->method('loadExternalFieldData')
            ->with(self::isInstanceOf(Content::class));

        $gatewayMock
            ->expects($this->once())
            ->method('updateContent')
            ->with(23, $metadataUpdateStruct);

        $locationMock
            ->expects($this->once())
            ->method('createLocationsFromNodeAssignments')
            ->with(23, 1);

        $locationMock
            ->expects($this->once())
            ->method('updateLocationsContentVersionNo')
            ->with(23, 1);

        $gatewayMock
            ->expects($this->once())
            ->method('setPublishedStatus')
            ->with(23, 1);

        $handler->publish(23, 1, $metadataUpdateStruct);
    }

    public function testPublish(): void
    {
        $handler = $this->getPartlyMockedHandler(['loadVersionInfo', 'setStatus']);

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $locationMock = $this->getLocationGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $metadataUpdateStruct = new MetadataUpdateStruct();

        $handler->expects($this->once())
            ->method('loadVersionInfo')
            ->with(23, 2)
            ->will(
                $this->returnValue(
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
            ->expects($this->once())
            ->method('setStatus')
            ->with(23, VersionInfo::STATUS_ARCHIVED, 1);

        $contentRows = [['content_version_version' => 2]];

        $gatewayMock->expects($this->once())
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(2),
                self::equalTo(null)
            )
            ->willReturn($contentRows);

        $gatewayMock->expects($this->once())
            ->method('loadVersionedNameData')
            ->with(
                self::equalTo([['id' => 23, 'version' => 2]])
            )->will(
                $this->returnValue([22])
            );

        $mapperMock->expects($this->once())
            ->method('extractContentFromRows')
            ->with(self::equalTo($contentRows), self::equalTo([22]))
            ->will($this->returnValue([$this->getContentFixtureForDraft()]));

        $fieldHandlerMock->expects($this->once())
            ->method('loadExternalFieldData')
            ->with(self::isInstanceOf(Content::class));

        $gatewayMock
            ->expects($this->once())
            ->method('updateContent')
            ->with(23, $metadataUpdateStruct, self::isInstanceOf(VersionInfo::class));

        $locationMock
            ->expects($this->once())
            ->method('createLocationsFromNodeAssignments')
            ->with(23, 2);

        $locationMock
            ->expects($this->once())
            ->method('updateLocationsContentVersionNo')
            ->with(23, 2);

        $gatewayMock
            ->expects($this->once())
            ->method('setPublishedStatus')
            ->with(23, 2);

        $handler->publish(23, 2, $metadataUpdateStruct);
    }

    public function testCreateDraftFromVersion(): void
    {
        $handler = $this->getPartlyMockedHandler(['load']);

        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $languageHandlerMock = $this->getLanguageHandlerMock();
        $contentTypeHandlerMock = $this->getContentTypeHandlerMock();

        $handler->expects($this->once())
            ->method('load')
            ->with(23, 2)
            ->will($this->returnValue($this->getContentFixtureForDraft()));

        $mapperMock->expects($this->once())
            ->method('createVersionInfoForContent')
            ->with(
                self::isInstanceOf(Content::class),
                self::equalTo(3),
                self::equalTo(14)
            )->will(
                $this->returnValue(
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

        $gatewayMock->expects($this->once())
            ->method('insertVersion')
            ->with(
                self::isInstanceOf(VersionInfo::class),
                $this->getContentFixtureForDraft()->fields
            )->will($this->returnValue(42));

        $gatewayMock->expects($this->once())
            ->method('getLastVersionNumber')
            ->with(self::equalTo(23))
            ->will($this->returnValue(2));

        $fieldHandlerMock->expects($this->once())
            ->method('createExistingFieldsInNewVersion')
            ->with(self::isInstanceOf(Content::class));

        $relationData = [
            [
                'content_link_content_type_field_definition_id' => 0,
                'content_link_to_contentobject_id' => 42,
                'content_link_relation_type' => 1,
            ],
        ];

        $gatewayMock->expects($this->once())
            ->method('loadRelations')
            ->with(
                self::equalTo(23),
                self::equalTo(2)
            )
            ->will($this->returnValue($relationData));

        $relationStruct = new RelationCreateStruct(
            [
                'sourceContentId' => 23,
                'sourceContentVersionNo' => 3,
                'sourceFieldDefinitionId' => 0,
                'destinationContentId' => 42,
                'type' => 1,
            ]
        );

        $gatewayMock->expects($this->once())
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

    public function testLoad(): void
    {
        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();

        $contentRows = [['content_version_version' => 2]];

        $gatewayMock->expects($this->once())
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(2),
                self::equalTo(['eng-GB'])
            )->will(
                $this->returnValue($contentRows)
            );

        $gatewayMock->expects($this->once())
            ->method('loadVersionedNameData')
            ->with(
                self::equalTo([['id' => 23, 'version' => 2]])
            )->will(
                $this->returnValue([22])
            );

        $mapperMock->expects($this->once())
            ->method('extractContentFromRows')
            ->with(self::equalTo($contentRows), self::equalTo([22]))
            ->will($this->returnValue([$this->getContentFixtureForDraft()]));

        $fieldHandlerMock->expects($this->once())
            ->method('loadExternalFieldData')
            ->with(self::isInstanceOf(Content::class));

        $result = $handler->load(23, 2, ['eng-GB']);

        self::assertEquals(
            $result,
            $this->getContentFixtureForDraft()
        );
    }

    public function testLoadContentList(): void
    {
        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $contentRows = [
            ['content_id' => 2, 'content_version_version' => 2],
            ['content_id' => 3, 'content_version_version' => 1],
        ];
        $gatewayMock->expects($this->once())
            ->method('loadContentList')
            ->with([2, 3], ['eng-GB', 'eng-US'])
            ->willReturn($contentRows);

        $nameDataRows = [
            ['content_name_contentobject_id' => 2, 'content_name_content_version' => 2],
            ['content_name_contentobject_id' => 3, 'content_name_content_version' => 1],
        ];

        $gatewayMock->expects($this->once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => 2, 'version' => 2], ['id' => 3, 'version' => 1]]))
            ->willReturn($nameDataRows);

        $expected = [
            2 => $this->getContentFixtureForDraft(2, 2),
            3 => $this->getContentFixtureForDraft(3, 1),
        ];
        $extractContentFromRowsMatcher = $this->exactly(2);
        $mapperMock->expects($extractContentFromRowsMatcher)
            ->method('extractContentFromRows')
            ->willReturnCallback(static function (
                array $rows,
                array $nameRows,
                string $prefix = 'content_',
                ?array $translations = null
            ) use ($extractContentFromRowsMatcher, $contentRows, $nameDataRows, $expected): array {
                if ($extractContentFromRowsMatcher->numberOfInvocations() === 1) {
                    self::assertSame([$contentRows[0]], $rows);
                    self::assertSame([$nameDataRows[0]], $nameRows);

                    return [$expected[2]];
                }

                self::assertSame([$contentRows[1]], $rows);
                self::assertSame([$nameDataRows[1]], $nameRows);

                return [$expected[3]];
            });

        $fieldHandlerMock->expects($this->exactly(2))
            ->method('loadExternalFieldData')
            ->with(self::isInstanceOf(Content::class));

        $result = $handler->loadContentList([2, 3], ['eng-GB', 'eng-US']);

        self::assertEquals(
            $expected,
            $result
        );
    }

    public function testLoadContentInfoByRemoteId(): void
    {
        $contentInfoData = [new ContentInfo()];
        $this->getGatewayMock()->expects($this->once())
            ->method('loadContentInfoByRemoteId')
            ->with(
                self::equalTo('15b256dbea2ae72418ff5facc999e8f9')
            )->will(
                $this->returnValue([42])
            );

        $this->getMapperMock()->expects($this->once())
            ->method('extractContentInfoFromRow')
            ->with(self::equalTo([42]))
            ->will($this->returnValue($contentInfoData));

        self::assertSame(
            $contentInfoData,
            $this->getContentHandler()->loadContentInfoByRemoteId('15b256dbea2ae72418ff5facc999e8f9')
        );
    }

    public function testLoadErrorNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('load')
            ->will(
                $this->returnValue([])
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

    public function testUpdateContent(): void
    {
        $handler = $this->getPartlyMockedHandler(['load', 'loadContentInfo']);

        $gatewayMock = $this->getGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();
        $contentTypeHandlerMock = $this->getContentTypeHandlerMock();
        $contentTypeMock = self::createStub(Type::class);
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

        $contentTypeHandlerMock->expects($this->once())
            ->method('load')
            ->with($contentStub->versionInfo->contentInfo->contentTypeId)
            ->will($this->returnValue($contentTypeMock));

        $gatewayMock->expects($this->once())
            ->method('updateContent')
            ->with(14, self::isInstanceOf(MetadataUpdateStruct::class));
        $gatewayMock->expects($this->once())
            ->method('updateVersion')
            ->with(14, 4, self::isInstanceOf(UpdateStruct::class));

        $fieldHandlerMock->expects($this->once())
            ->method('updateFields')
            ->with(
                self::isInstanceOf(Content::class),
                self::isInstanceOf(UpdateStruct::class),
                self::isInstanceOf(Type::class)
            );

        $loadMatcher = $this->exactly(2);
        $handler->expects($loadMatcher)
            ->method('load')
            ->with(14, 4)
            ->willReturnCallback(static function () use ($loadMatcher, $contentStub): ?Content {
                if ($loadMatcher->numberOfInvocations() === 1) {
                    return $contentStub;
                }

                return null;
            });

        $handler->expects($this->once())
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

    public function testUpdateMetadata(): void
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

        $gatewayMock->expects($this->once())
            ->method('updateContent')
            ->with(14, $updateStruct);

        $handler->expects($this->once())
            ->method('loadContentInfo')
            ->with(14)
            ->will(
                $this->returnValue(
                    self::createStub(ContentInfo::class)
                )
            );

        $resultContentInfo = $handler->updateMetadata(
            14, // ContentId
            $updateStruct
        );
        self::assertInstanceOf(ContentInfo::class, $resultContentInfo);
    }

    public function testUpdateMetadataUpdatesPathIdentificationString(): void
    {
        $handler = $this->getPartlyMockedHandler(['load', 'loadContentInfo']);
        $locationGatewayMock = $this->getLocationGatewayMock();
        $slugConverterMock = $this->getSlugConverterMock();
        $urlAliasGatewayMock = $this->getUrlAliasGatewayMock();
        $gatewayMock = $this->getGatewayMock();
        $updateStruct = new MetadataUpdateStruct(['mainLanguageId' => 2]);

        $gatewayMock->expects($this->once())
            ->method('updateContent')
            ->with(14, $updateStruct);

        $locationGatewayMock->expects($this->once())
            ->method('loadLocationDataByContent')
            ->with(14)
            ->will(
                $this->returnValue(
                    [
                        [
                            'node_id' => 100,
                            'parent_node_id' => 200,
                        ],
                    ]
                )
            );

        $urlAliasGatewayMock->expects($this->once())
            ->method('loadLocationEntries')
            ->with(100, false, 2)
            ->will(
                $this->returnValue(
                    [
                        [
                            'text' => 'slug',
                        ],
                    ]
                )
            );

        $slugConverterMock->expects($this->once())
            ->method('convert')
            ->with('slug', 'node_100', 'urlalias_compat')
            ->will($this->returnValue('transformed_slug'));

        $locationGatewayMock->expects($this->once())
            ->method('updatePathIdentificationString')
            ->with(100, 200, 'transformed_slug');

        $handler->expects($this->once())
            ->method('loadContentInfo')
            ->with(14)
            ->will(
                $this->returnValue(
                    self::createStub(ContentInfo::class)
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
            ->expects($this->once())
            ->method('loadRelation')
            ->with(self::RELATION_ID)
            ->willReturn([self::RELATION_ID]);

        $mapperMock
            ->expects($this->once())
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

        $gatewayMock->expects($this->once())
            ->method('listRelations')
            ->with(
                self::equalTo(23)
            )->will(
                $this->returnValue([42])
            );

        $mapperMock->expects($this->once())
            ->method('extractRelationsFromRows')
            ->with(self::equalTo([42]))
            ->will($this->returnValue([$this->getRelationFixture()]));

        $result = $handler->loadRelationList(23, 10);

        self::assertEquals(
            $result,
            [$this->getRelationFixture()]
        );
    }

    public function testLoadReverseRelations(): void
    {
        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();

        $gatewayMock->expects($this->once())
            ->method('loadReverseRelations')
            ->with(
                self::equalTo(23),
                self::equalTo(null)
            )->will(
                $this->returnValue([42])
            );

        $mapperMock->expects($this->once())
            ->method('extractRelationsFromRows')
            ->with(self::equalTo([42]))
            ->will($this->returnValue($this->getRelationFixture()));

        $result = $handler->loadReverseRelations(23);

        self::assertEquals(
            $result,
            $this->getRelationFixture()
        );
    }

    public function testAddRelation(): void
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

        $mapperMock->expects($this->once())
            ->method('createRelationFromCreateStruct')
            // @todo Connected with the todo above
            ->with(self::equalTo($relationCreateStruct))
            ->will($this->returnValue($expectedRelationObject));

        $gatewayMock->expects($this->once())
            ->method('insertRelation')
            ->with(self::equalTo($relationCreateStruct))
            ->will(
                // @todo Should this return a row as if it was selected from the database, the id... ? Check with other, similar create methods
                $this->returnValue(42)
            );

        $result = $handler->addRelation($relationCreateStruct);

        self::assertEquals(
            $result,
            $expectedRelationObject
        );
    }

    public function testRemoveRelation(): void
    {
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
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

    public function testLoadDraftsForUser(): void
    {
        $handler = $this->getContentHandler();
        $rows = [['content_version_contentobject_id' => 42, 'content_version_version' => 2]];

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();

        $gatewayMock->expects($this->once())
            ->method('listVersionsForUser')
            ->with(self::equalTo(23))
            ->will($this->returnValue($rows));

        $gatewayMock->expects($this->once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => 42, 'version' => 2]]))
            ->will($this->returnValue([]));

        $mapperMock->expects($this->once())
            ->method('extractVersionInfoListFromRows')
            ->with(self::equalTo($rows), self::equalTo([]))
            ->will($this->returnValue([new VersionInfo()]));

        $res = $handler->loadDraftsForUser(23);

        self::assertEquals(
            [new VersionInfo()],
            $res
        );
    }

    public function testListVersions(): void
    {
        $handler = $this->getContentHandler();

        $treeHandlerMock = $this->getTreeHandlerMock();

        $treeHandlerMock
            ->expects($this->once())
            ->method('listVersions')
            ->with(23)
            ->will($this->returnValue([new VersionInfo()]));

        $versions = $handler->listVersions(23);

        self::assertEquals(
            [new VersionInfo()],
            $versions
        );
    }

    public function testRemoveRawContent(): void
    {
        $handler = $this->getContentHandler();
        $treeHandlerMock = $this->getTreeHandlerMock();

        $treeHandlerMock
            ->expects($this->once())
            ->method('removeRawContent')
            ->with(23);

        $handler->removeRawContent(23);
    }

    /**
     * Test for the deleteContent() method.
     */
    public function testDeleteContentWithLocations(): void
    {
        $handlerMock = $this->getPartlyMockedHandler([]);
        $gatewayMock = $this->getGatewayMock();
        $treeHandlerMock = $this->getTreeHandlerMock();

        $gatewayMock->expects($this->once())
            ->method('getAllLocationIds')
            ->with(self::equalTo(23))
            ->will($this->returnValue([42, 24]));
        $treeHandlerMock->expects($this->exactly(2))
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
    public function testDeleteContentWithoutLocations(): void
    {
        $handlerMock = $this->getPartlyMockedHandler(['removeRawContent']);
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('getAllLocationIds')
            ->with(self::equalTo(23))
            ->will($this->returnValue([]));
        $handlerMock->expects($this->once())
            ->method('removeRawContent')
            ->with(self::equalTo(23));

        $handlerMock->deleteContent(23);
    }

    public function testDeleteVersion(): void
    {
        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();
        $locationHandlerMock = $this->getLocationGatewayMock();
        $fieldHandlerMock = $this->getFieldHandlerMock();

        $rows = [['content_version_version' => 2]];

        // Load VersionInfo to delete fields
        $gatewayMock->expects($this->once())
            ->method('loadVersionInfo')
            ->with(self::equalTo(225), self::equalTo(2))
            ->willReturn($rows);

        $gatewayMock->expects($this->once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => 225, 'version' => 2]]))
            ->will($this->returnValue([22]));

        $mapperMock->expects($this->once())
            ->method('extractVersionInfoListFromRows')
            ->with(self::equalTo($rows), self::equalTo([22]))
            ->will($this->returnValue([new VersionInfo()]));

        $locationHandlerMock->expects($this->once())
            ->method('deleteNodeAssignment')
            ->with(
                self::equalTo(225),
                self::equalTo(2)
            );

        $fieldHandlerMock->expects($this->once())
            ->method('deleteFields')
            ->with(
                self::equalTo(225),
                self::isInstanceOf(VersionInfo::class)
            );
        $gatewayMock->expects($this->once())
            ->method('deleteRelations')
            ->with(
                self::equalTo(225),
                self::equalTo(2)
            );
        $gatewayMock->expects($this->once())
            ->method('deleteVersions')
            ->with(
                self::equalTo(225),
                self::equalTo(2)
            );
        $gatewayMock->expects($this->once())
            ->method('deleteNames')
            ->with(
                self::equalTo(225),
                self::equalTo(2)
            );

        $handler->deleteVersion(225, 2);
    }

    public function testCopySingleVersion(): void
    {
        $handler = $this->getPartlyMockedHandler(['load', 'internalCreate']);
        $gatewayMock = $this->getGatewayMock();
        $mapperMock = $this->getMapperMock();

        $handler->expects(
            $this->once()
        )->method(
            'load'
        )->with(
            self::equalTo(23),
            self::equalTo(32)
        )->will(
            $this->returnValue(new Content())
        );

        $mapperMock->expects(
            $this->once()
        )->method(
            'createCreateStructFromContent'
        )->with(
            self::isInstanceOf(Content::class)
        )->will(
            $this->returnValue(new CreateStruct())
        );

        $handler->expects(
            $this->once()
        )->method(
            'internalCreate'
        )->with(
            self::isInstanceOf(CreateStruct::class),
            self::equalTo(32)
        )->will(
            $this->returnValue(
                new Content(
                    [
                        'versionInfo' => new VersionInfo(['contentInfo' => new ContentInfo(['id' => 24])]),
                    ]
                )
            )
        );

        $gatewayMock->expects($this->once())
            ->method('copyRelations')
            ->with(
                self::equalTo(23),
                self::equalTo(24),
                self::equalTo(32)
            )
            ->will($this->returnValue(null));

        $result = $handler->copy(23, 32);

        self::assertInstanceOf(
            Content::class,
            $result
        );
    }

    public function testCopyAllVersions(): void
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
        $contentTypeMock = self::createStub(Type::class);
        $time = time();
        $createStructStub = new CreateStruct(
            [
                'modified' => $time,
                'typeId' => 4242,
            ]
        );

        $contentTypeHandlerMock->expects($this->once())
            ->method('load')
            ->with($createStructStub->typeId)
            ->will($this->returnValue($contentTypeMock));

        $handler->expects($this->once())
            ->method('loadContentInfo')
            ->with(self::equalTo(23))
            ->will($this->returnValue(new ContentInfo(['currentVersionNo' => 2])));

        $mapperMock->expects($this->once())
            ->method('createCreateStructFromContent')
            ->with(self::isInstanceOf(Content::class))
            ->will(
                $this->returnValue($createStructStub)
            );

        $handler->expects($this->once())
            ->method('internalCreate')
            ->with(
                self::isInstanceOf(CreateStruct::class),
                self::equalTo(2)
            )->will(
                $this->returnValue(
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

        $handler->expects($this->once())
            ->method('listVersions')
            ->with(self::equalTo(23))
            ->will(
                $this->returnValue(
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
        $copyLoadMatcher = $this->exactly(2);
        $handler->expects($copyLoadMatcher)
            ->method('load')
            ->willReturnCallback(static function (
                $contentId,
                $versionNo
            ) use ($copyLoadMatcher, $versionInfo): Content {
                self::assertSame(23, $contentId);
                if ($copyLoadMatcher->numberOfInvocations() === 1) {
                    self::assertSame(2, $versionNo);

                    return new Content();
                }

                self::assertSame(1, $versionNo);

                return new Content(
                    [
                        'versionInfo' => $versionInfo,
                        'fields' => [],
                    ]
                );
            });

        $versionInfo->creationDate = $time;
        $versionInfo->modificationDate = $time;
        $gatewayMock->expects($this->once())
            ->method('insertVersion')
            ->with(
                self::equalTo($versionInfo),
                self::isArray()
            )->will($this->returnValue(42));

        $versionInfo = clone $versionInfo;
        $versionInfo->id = 42;
        $fieldHandlerMock->expects($this->once())
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

        $gatewayMock->expects($this->once())
            ->method('setName')
            ->with(
                self::equalTo(24),
                self::equalTo(1),
                self::equalTo('Test'),
                self::equalTo('eng-US')
            );

        $gatewayMock->expects($this->once())
            ->method('copyRelations')
            ->with(
                self::equalTo(23),
                self::equalTo(24),
                self::equalTo(null)
            )
            ->will($this->returnValue(null));

        $result = $handler->copy(23);

        self::assertInstanceOf(
            Content::class,
            $result
        );
    }

    public function testCopyThrowsNotFoundExceptionContentNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getContentHandler();

        $treeHandlerMock = $this->getTreeHandlerMock();
        $treeHandlerMock
            ->expects($this->once())
            ->method('loadContentInfo')
            ->with(self::equalTo(23))
            ->will(
                $this->throwException(new NotFoundException('ContentInfo', 23))
            );

        $handler->copy(23);
    }

    public function testCopyThrowsNotFoundExceptionVersionNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $handler = $this->getContentHandler();

        $gatewayMock = $this->getGatewayMock();
        $gatewayMock->expects($this->once())
            ->method('load')
            ->with(
                self::equalTo(23),
                self::equalTo(32),
            )
            ->will($this->returnValue([]));

        $result = $handler->copy(23, 32);
    }

    public function testSetStatus(): void
    {
        $handler = $this->getContentHandler();

        $mapperMock = $this->getMapperMock();
        $gatewayMock = $this->getGatewayMock();

        $gatewayMock->expects($this->once())
            ->method('setStatus')
            ->with(23, 5, 2)
            ->will($this->returnValue(true));

        self::assertTrue(
            $handler->setStatus(23, 2, 5)
        );
    }

    /**
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

        $gatewayMock->expects($this->once())
            ->method('loadVersionInfoList')
            ->with($contentIds)
            ->willReturn($versionRows);

        $nameDataRows = [
            ['content_name_contentobject_id' => 2, 'content_name_content_version' => 2],
            ['content_name_contentobject_id' => 3, 'content_name_content_version' => 1],
        ];

        $gatewayMock->expects($this->once())
            ->method('loadVersionedNameData')
            ->with([['id' => 2, 'version' => 2], ['id' => 3, 'version' => 1]])
            ->willReturn($nameDataRows);

        $mapperMock->expects($this->once())
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
            ->onlyMethods(array_values($methods))
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

    protected function getLanguageHandlerMock(): LanguageHandler & MockObject
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

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Handler as SPIContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as PersistenceLocationHandler;
use Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Relation;
use Ibexa\Contracts\Core\Persistence\Content\Relation as SPIRelation;
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct as RelationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation as APIRelation;
use Ibexa\Core\Persistence\Cache\ContentHandler;

/**
 * @covers \Ibexa\Core\Persistence\Cache\ContentHandler
 */
class ContentHandlerTest extends AbstractInMemoryCacheHandlerTest
{
    public function getHandlerMethodName(): string
    {
        return 'contentHandler';
    }

    public function getHandlerClassName(): string
    {
        return SPIContentHandler::class;
    }

    /**
     * @return array
     */
    public function providerForUnCachedMethods(): array
    {
        // string $method, array $arguments, array? $tagGeneratingArguments, array? $keyGeneratingArguments, array? $tags, array? $key, ?mixed $returnValue
        return [
            ['create', [new CreateStruct()]],
            ['createDraftFromVersion', [2, 1, 14], null, [['content_version_list', [2], true]], [], ['ibx-c-2-vl']],
            ['copy', [2, 1]],
            ['loadDraftsForUser', [14]],
            ['setStatus', [2, 0, 1], [['content_version', [2, 1], false]], null, ['c-2-v-1']],
            ['setStatus', [2, 1, 1], [['content', [2], false]], null, ['c-2']],
            ['updateMetadata', [2, new MetadataUpdateStruct()], [['content', [2], false]], null, ['c-2']],
            //updateContent has its own test now due to relations complexity
            //['updateContent', [2, 1, new UpdateStruct()], [['content_version', [2, 1], false]], null, ['c-2-v-1']],
            //['deleteContent', [2]], own tests for relations complexity
            ['deleteVersion', [2, 1], [['content_version', [2, 1], false]], null, ['c-2-v-1']],
            ['addRelation', [new RelationCreateStruct(['destinationContentId' => 2, 'sourceContentId' => 4])], [['content', [2], false], ['content', [4], false]], null, ['c-2', 'c-4']],
            ['removeRelation', [66, APIRelation::COMMON, 2], [['content', [2], false], ['relation', [66], false]], null, ['c-2', 're-66']],
            ['loadRelations', [2, 1, 3]],
            ['loadReverseRelations', [2, 3]],
            ['publish', [2, 3, new MetadataUpdateStruct()], [['content', [2], false]], null, ['c-2']],
            [
                'listVersions',
                [2, 1],
                [['content', [2], false]],
                [['content_version_list', [2], true]],
                [],
                [],
                [
                    new VersionInfo([
                        'versionNo' => 1,
                        'contentInfo' => new ContentInfo([
                            'id' => 2,
                        ]),
                    ]),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerForCachedLoadMethodsHit(): array
    {
        $info = new ContentInfo(['id' => 2]);
        $version = new VersionInfo(['versionNo' => 1, 'contentInfo' => $info]);
        $content = new Content(['fields' => [], 'versionInfo' => $version]);
        $relation = new Relation();
        $relation->id = 1;
        $relation->sourceContentId = 2;
        $relation->sourceContentVersionNo = 2;
        $relation->destinationContentId = 1;
        $relation->type = 1;
        $relationList[1] = $relation;

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi = false, array $additionalCalls
        return [
            ['countReverseRelations', [2, null], 'ibx-crrc-2-t-', null, null, [['content_reverse_relations_count', [2, null], true]], ['ibx-crrc-2-t-'], 10],
            ['countReverseRelations', [2, 8], 'ibx-crrc-2-t-8', null, null, [['content_reverse_relations_count', [2, 8], true]], ['ibx-crrc-2-t-8'], 10],
            ['countRelations', [2], 'ibx-crc-2-v--t-', null, null, [['content_relations_count_with_by_version_type_suffix', [2, null, null], true]], ['ibx-crc-2-v--t-'], 10],
            ['countRelations', [2, 2], 'ibx-crc-2-v-2-t-', null, null, [['content_relations_count_with_by_version_type_suffix', [2, 2, null], true]], ['ibx-crc-2-v-2-t-'], 10],
            ['countRelations', [2, null, 1], 'ibx-crc-2-v--t-1', null, null, [['content_relations_count_with_by_version_type_suffix', [2, null, 1], true]], ['ibx-crc-2-v--t-1'], 10],
            ['countRelations', [2, 2, 1], 'ibx-crc-2-v-2-t-1', null, null, [['content_relations_count_with_by_version_type_suffix', [2, 2, 1], true]], ['ibx-crc-2-v-2-t-1'], 10],
            ['loadRelationList', [2, 1, 0], 'ibx-crl-2-l-1-o-0-v--t-', null, null, [['content_relations_list_with_by_version_type_suffix', [2, 1, 0, null, null], true]], ['ibx-crl-2-l-1-o-0-v--t-'], $relationList],
            ['loadRelationList', [2, 1, 0, 2], 'ibx-crl-2-l-1-o-0-v-2-t-', null, null, [['content_relations_list_with_by_version_type_suffix', [2, 1, 0, 2, null], true]], ['ibx-crl-2-l-1-o-0-v-2-t-'], $relationList],
            ['loadRelationList', [2, 1, 0, null, 1], 'ibx-crl-2-l-1-o-0-v--t-1', null, null, [['content_relations_list_with_by_version_type_suffix', [2, 1, 0, null, 1], true]], ['ibx-crl-2-l-1-o-0-v--t-1'], $relationList],
            ['loadRelationList', [2, 1, 0, 2, 1], 'ibx-crl-2-l-1-o-0-v-2-t-1', null, null, [['content_relations_list_with_by_version_type_suffix', [2, 1, 0, 2, 1], true]], ['ibx-crl-2-l-1-o-0-v-2-t-1'], $relationList],
            ['load', [2, 1], 'ibx-c-2-1-' . ContentHandler::ALL_TRANSLATIONS_KEY, null, null, [['content', [], true]], ['ibx-c'], $content],
            ['load', [2, 1, ['eng-GB', 'eng-US']], 'ibx-c-2-1-eng-GB|eng-US', null, null, [['content', [], true]], ['ibx-c'], $content],
            ['load', [2], 'ibx-c-2-' . ContentHandler::ALL_TRANSLATIONS_KEY, null, null, [['content', [], true]], ['ibx-c'], $content],
            ['load', [2, null, ['eng-GB', 'eng-US']], 'ibx-c-2-eng-GB|eng-US', null, null, [['content', [], true]], ['ibx-c'], $content],
            ['loadContentList', [[2]], 'ibx-c-2-' . ContentHandler::ALL_TRANSLATIONS_KEY, null, null, [['content', [], true]], ['ibx-c'], [2 => $content], true],
            ['loadContentList', [[5], ['eng-GB', 'eng-US']], 'ibx-c-5-eng-GB|eng-US', null, null, [['content', [], true]], ['ibx-c'], [5 => $content], true],
            ['loadContentInfo', [2], 'ibx-ci-2', null, null, [['content_info', [], true]], ['ibx-ci'], $info],
            ['loadContentInfoList', [[2]], 'ibx-ci-2', null, null, [['content_info', [], true]], ['ibx-ci'], [2 => $info], true],
            ['loadContentInfoByRemoteId', ['3d8jrj'], 'ibx-cibri-3d8jrj', null, null, [['content_info_by_remote_id', [], true]], ['ibx-cibri'], $info],
            ['loadRelation', [66], 'ibx-re-66', null, null, [['relation', [66], true]], ['ibx-re-66'], new SPIRelation()],
            ['loadVersionInfo', [2, 1], 'ibx-cvi-2-1', null, null, [['content_version_info', [2], true]], ['ibx-cvi-2'], $version],
            ['loadVersionInfo', [2], 'ibx-cvi-2', null, null, [['content_version_info', [2], true]], ['ibx-cvi-2'], $version],
            ['listVersions', [2], 'ibx-c-2-vl', null, null, [['content_version_list', [2], true]], ['ibx-c-2-vl'], [$version]],
            ['loadVersionInfoList', [[2]], 'ibx-cvi-2', null, null, [['content_version_info', [], true]], ['ibx-cvi'], [2 => $version], true],
        ];
    }

    /**
     * @return array
     */
    public function providerForCachedLoadMethodsMiss(): array
    {
        $info = new ContentInfo([
            'id' => 2,
            'contentTypeId' => 3,
        ]);
        $version = new VersionInfo(['versionNo' => 1, 'contentInfo' => $info]);
        $content = new Content(['fields' => [], 'versionInfo' => $version]);

        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi = false, array $additionalCalls
        return [
            [
                'countReverseRelations',
                [2],
                'ibx-crrc-2-t-',
                [
                    ['content', [2], false],
                ],
                ['c-2'],
                [
                    ['content_reverse_relations_count', [2, null], true],
                ],
                ['ibx-crrc-2-t-'],
                10,
            ],
            [
                'countReverseRelations',
                [2, 8],
                'ibx-crrc-2-t-8',
                [
                    ['content', [2], false],
                ],
                ['c-2'],
                [
                    ['content_reverse_relations_count', [2, 8], true],
                ],
                ['ibx-crrc-2-t-8'],
                10,
            ],
            [
                'countRelations',
                [2],
                'ibx-crc-2-v--t-',
                [
                    ['content', [2], false],
                ],
                ['c-2'],
                [
                    ['content_relations_count_with_by_version_type_suffix', [2, null, null], true],
                ],
                ['ibx-crc-2-v--t-'],
                10,
            ],
            [
                'countRelations',
                [2, 3],
                'ibx-crc-2-v-3-t-',
                [
                    ['content', [2], false],
                ],
                ['c-2'],
                [
                    ['content_relations_count_with_by_version_type_suffix', [2, 3, null], true],
                ],
                ['ibx-crc-2-v-3-t-'],
                10,
            ],
            [
                'countRelations',
                [2, null, 1],
                'ibx-crc-2-v--t-1',
                [
                    ['content', [2], false],
                ],
                ['c-2'],
                [
                    ['content_relations_count_with_by_version_type_suffix', [2, null, 1], true],
                ],
                ['ibx-crc-2-v--t-1'],
                10,
            ],
            [
                'countRelations',
                [2, 3, 1],
                'ibx-crc-2-v-3-t-',
                [
                    ['content', [2], false],
                ],
                ['c-2'],
                [
                    ['content_relations_count_with_by_version_type_suffix', [2, 3, 1], true],
                ],
                ['ibx-crc-2-v-3-t-'],
                10,
            ],
            [
                'load',
                [2, 1],
                'ibx-c-2-1-' . ContentHandler::ALL_TRANSLATIONS_KEY,
                [
                    ['content_fields_type', [3], false],
                    ['content_version', [2, 1], false],
                    ['content', [2], false],
                ],
                ['cft-3', 'c-2-v-1', 'c-2'],
                [
                    ['content', [], true],
                ],
                ['ibx-c'],
                $content,
            ],
            [
                'load',
                [2, 1, ['eng-GB', 'eng-US']],
                'ibx-c-2-1-eng-GB|eng-US',
                [
                    ['content_fields_type', [3], false],
                    ['content_version', [2, 1], false],
                    ['content', [2], false],
                ],
                ['cft-2', 'c-2-v-1', 'c-2'],
                [
                    ['content', [], true],
                ],
                ['ibx-c'],
                $content,
            ],
            [
                'load',
                [2],
                'ibx-c-2-' . ContentHandler::ALL_TRANSLATIONS_KEY,
                [
                    ['content_fields_type', [3], false],
                    ['content_version', [2, 1], false],
                    ['content', [2], false],
                ],
                ['cft-2', 'c-2-v-1', 'c-2'],
                [
                    ['content', [], true],
                ],
                ['ibx-c'],
                $content,
            ],
            [
                'load',
                [2, null, ['eng-GB', 'eng-US']],
                'ibx-c-2-eng-GB|eng-US',
                [
                    ['content_fields_type', [3], false],
                    ['content_version', [2, 1], false],
                    ['content', [2], false],
                ],
                ['cft-2', 'c-2-v-1', 'c-2'],
                [
                    ['content', [], true],
                ],
                ['ibx-c'],
                $content,
            ],
            [
                'loadContentList',
                [[2]],
                'ibx-c-2-' . ContentHandler::ALL_TRANSLATIONS_KEY,
                [
                    ['content_fields_type', [3], false],
                    ['content_version', [2, 1], false],
                    ['content', [2], false],
                ],
                ['cft-2', 'c-2-v-1', 'c-2'],
                [
                    ['content', [], true],
                ],
                ['ibx-c'],
                [2 => $content],
                true,
            ],
            [
                'loadContentList',
                [[5], ['eng-GB', 'eng-US']],
                'ibx-c-5-eng-GB|eng-US',
                [
                    ['content_fields_type', [3], false],
                    ['content_version', [2, 1], false],
                    ['content', [2], false],
                ],
                ['cft-2', 'c-2-v-1', 'c-2'],
                [
                    ['content', [], true],
                ],
                ['ibx-c'],
                [5 => $content],
                true,
            ],
            [
                'loadContentInfo',
                [2],
                'ibx-ci-2',
                [
                    ['content', [2], false],
                ],
                ['c-2'],
                [
                    ['content_info', [], true],
                ],
                ['ibx-ci'],
                $info,
            ],
            [
                'loadContentInfoList',
                [[2]],
                'ibx-ci-2',
                [
                    ['content', [2], false],
                ],
                ['c-2'],
                [
                    ['content_info', [], true],
                ],
                ['ibx-ci'],
                [2 => $info],
                true,
            ],
            [
                'loadContentInfoByRemoteId',
                ['3d8jrj'], 'ibx-cibri-3d8jrj',
                [
                    ['content', [2], false],
                ],
                ['c-2'],
                [
                    ['content_info_by_remote_id', [], true],
                ],
                ['ibx-cibri'],
                $info,
            ],
            [
                'loadVersionInfo',
                [2, 1],
                'ibx-cvi-2-1',
                [
                    ['content_version', [2, 1], false],
                    ['content', [2], false],
                ],
                ['c-2-v-1', 'c-2'],
                [
                    ['content_version_info', [2], true],
                ],
                ['ibx-cvi-2'],
                $version,
            ],
            [
                'loadVersionInfo',
                [2],
                'ibx-cvi-2',
                [
                    ['content_version', [2, 1], false],
                    ['content', [2], false],
                ],
                ['c-2-v-1', 'c-2'],
                [
                    ['content_version_info', [2], true],
                ],
                ['ibx-cvi-2'],
                $version,
            ],
            [
                'listVersions',
                [2],
                'ibx-c-2-vl',
                [
                    ['content', [2], false],
                    ['content_version', [2, 1], false],
                    ['content', [2], false],
                ],
                ['c-2', 'c-2-v-1', 'c-2'],
                [
                    ['content_version_list', [2], true],
                ],
                ['ibx-c-2-vl'],
                [$version],
            ],
            [
                'loadRelation',
                [66],
                'ibx-re-66',
                [
                    ['relation', [66], false],
                ],
                ['re-66'],
                [
                    ['relation', [66], true],
                ],
                ['ibx-re-66'],
                new SPIRelation(),
            ],
            [
                'loadVersionInfoList',
                [[2]],
                'ibx-cvi-2',
                [
                    ['content_version', [2, 1], false],
                    ['content', [2], false],
                ],
                ['c-2-v-1', 'c-2'],
                [
                    ['content_version_info', [], true],
                ],
                ['ibx-cvi'],
                [2 => $version],
                true,
            ],
        ];
    }

    public function testUpdateContent(): void
    {
        $this->loggerMock->expects($this->once())->method('logCall');

        $innerContentHandlerMock = $this->createMock(SPIContentHandler::class);
        $innerLocationHandlerMock = $this->createMock(PersistenceLocationHandler::class);

        $this->prepareHandlerMocks(
            $innerContentHandlerMock,
            $innerLocationHandlerMock,
            1,
            1,
            0
        );

        $innerContentHandlerMock
            ->expects(self::once())
            ->method('updateContent')
            ->with(2, 1, new UpdateStruct())
            ->willReturn(new Content());

        $this->cacheIdentifierGeneratorMock
            ->expects(self::exactly(5))
            ->method('generateTag')
            ->willReturnMap(
                [
                    ['location', [3], false, 'l-3'],
                    ['location', [4], false, 'l-4'],
                    ['location_path', [3], false, 'lp-3'],
                    ['location_path', [4], false, 'lp-4'],
                    ['content_version', [2, 1], false, 'c-2-v-1'],
                ]
            );

        $this->cacheMock
            ->expects(self::once())
            ->method('invalidateTags')
            ->with(['l-3', 'l-4', 'lp-3', 'lp-4', 'c-2-v-1']);

        $handler = $this->persistenceCacheHandler->contentHandler();
        $handler->updateContent(2, 1, new UpdateStruct());
    }

    public function testDeleteContent(): void
    {
        $this->loggerMock->expects(self::once())->method('logCall');

        $innerContentHandlerMock = $this->createMock(SPIContentHandler::class);
        $innerLocationHandlerMock = $this->createMock(PersistenceLocationHandler::class);

        $this->prepareHandlerMocks(
            $innerContentHandlerMock,
            $innerLocationHandlerMock,
            2
        );

        $innerContentHandlerMock
            ->expects(self::once())
            ->method('deleteContent')
            ->with(2)
            ->willReturn(true);

        $this->cacheMock
            ->expects(self::never())
            ->method('deleteItem');

        $this->cacheIdentifierGeneratorMock
            ->expects(self::exactly(4))
            ->method('generateTag')
            ->withConsecutive(
                ['content', [42], false],
                ['content', [2], false],
                ['location', [3], false],
                ['location', [4], false]
            )
            ->willReturnOnConsecutiveCalls('c-42', 'c-2', 'l-3', 'l-4');

        $this->cacheMock
            ->expects(self::once())
            ->method('invalidateTags')
            ->with(['c-42', 'c-2', 'l-3', 'l-4']);

        $handler = $this->persistenceCacheHandler->contentHandler();
        $handler->deleteContent(2);
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\Handler|\PHPUnit\Framework\MockObject\MockObject $innerContentHandlerMock
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location\Handler|\PHPUnit\Framework\MockObject\MockObject $innerLocationHandlerMock
     */
    private function prepareHandlerMocks(
        SPIContentHandler $innerContentHandlerMock,
        PersistenceLocationHandler $innerLocationHandlerMock,
        int $contentHandlerCount = 1,
        int $locationHandlerCount = 1,
        int $loadReverseRelationsCount = 1,
        int $loadLocationsByContentCount = 1
    ): void {
        $this->persistenceHandlerMock
            ->expects(self::exactly($contentHandlerCount))
            ->method('contentHandler')
            ->willReturn($innerContentHandlerMock);

        $innerContentHandlerMock
            ->expects(self::exactly($loadReverseRelationsCount))
            ->method('loadReverseRelations')
            ->with(2, APIRelation::FIELD | APIRelation::ASSET)
            ->willReturn(
                [
                    new SPIRelation(['sourceContentId' => 42]),
                ]
            );

        $this->persistenceHandlerMock
            ->expects(self::exactly($locationHandlerCount))
            ->method('locationHandler')
            ->willReturn($innerLocationHandlerMock);

        $innerLocationHandlerMock
            ->expects(self::exactly($loadLocationsByContentCount))
            ->method('loadLocationsByContent')
            ->with(2)
            ->willReturn(
                [
                    new Content\Location(['id' => 3]),
                    new Content\Location(['id' => 4]),
                ]
            );
    }
}

class_alias(ContentHandlerTest::class, 'eZ\Publish\Core\Persistence\Cache\Tests\ContentHandlerTest');

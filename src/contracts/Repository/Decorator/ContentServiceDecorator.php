<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentDraftList;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentList;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentMetadataUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationList;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

abstract class ContentServiceDecorator implements ContentService
{
    protected ContentService $innerService;

    public function __construct(ContentService $innerService)
    {
        $this->innerService = $innerService;
    }

    public function loadContentInfo(int $contentId): ContentInfo
    {
        return $this->innerService->loadContentInfo($contentId);
    }

    public function loadContentInfoList(array $contentIds): iterable
    {
        return $this->innerService->loadContentInfoList($contentIds);
    }

    public function loadContentInfoByRemoteId(string $remoteId): ContentInfo
    {
        return $this->innerService->loadContentInfoByRemoteId($remoteId);
    }

    public function loadVersionInfo(
        ContentInfo $contentInfo,
        ?int $versionNo = null
    ): VersionInfo {
        return $this->innerService->loadVersionInfo($contentInfo, $versionNo);
    }

    public function loadVersionInfoById(
        int $contentId,
        ?int $versionNo = null
    ): VersionInfo {
        return $this->innerService->loadVersionInfoById($contentId, $versionNo);
    }

    public function loadVersionInfoListByContentInfo(array $contentInfoList): array
    {
        return $this->innerService->loadVersionInfoListByContentInfo($contentInfoList);
    }

    public function loadContentByContentInfo(
        ContentInfo $contentInfo,
        array $languages = null,
        ?int $versionNo = null,
        bool $useAlwaysAvailable = true
    ): Content {
        return $this->innerService->loadContentByContentInfo($contentInfo, $languages, $versionNo, $useAlwaysAvailable);
    }

    public function loadContentByVersionInfo(
        VersionInfo $versionInfo,
        array $languages = null,
        bool $useAlwaysAvailable = true
    ): Content {
        return $this->innerService->loadContentByVersionInfo($versionInfo, $languages, $useAlwaysAvailable);
    }

    public function loadContent(
        int $contentId,
        array $languages = null,
        ?int $versionNo = null,
        bool $useAlwaysAvailable = true
    ): Content {
        return $this->innerService->loadContent($contentId, $languages, $versionNo, $useAlwaysAvailable);
    }

    public function loadContentByRemoteId(
        string $remoteId,
        array $languages = null,
        ?int $versionNo = null,
        bool $useAlwaysAvailable = true
    ): Content {
        return $this->innerService->loadContentByRemoteId($remoteId, $languages, $versionNo, $useAlwaysAvailable);
    }

    public function loadContentListByContentInfo(
        array $contentInfoList,
        array $languages = [],
        bool $useAlwaysAvailable = true
    ): iterable {
        return $this->innerService->loadContentListByContentInfo($contentInfoList, $languages, $useAlwaysAvailable);
    }

    public function createContent(
        ContentCreateStruct $contentCreateStruct,
        array $locationCreateStructs = [],
        ?array $fieldIdentifiersToValidate = null
    ): Content {
        return $this->innerService->createContent($contentCreateStruct, $locationCreateStructs, $fieldIdentifiersToValidate);
    }

    public function updateContentMetadata(
        ContentInfo $contentInfo,
        ContentMetadataUpdateStruct $contentMetadataUpdateStruct
    ): Content {
        return $this->innerService->updateContentMetadata($contentInfo, $contentMetadataUpdateStruct);
    }

    public function deleteContent(ContentInfo $contentInfo): array
    {
        return $this->innerService->deleteContent($contentInfo);
    }

    public function createContentDraft(
        ContentInfo $contentInfo,
        ?VersionInfo $versionInfo = null,
        ?User $creator = null,
        ?Language $language = null
    ): Content {
        return $this->innerService->createContentDraft($contentInfo, $versionInfo, $creator, $language);
    }

    public function countContentDrafts(User $user = null): int
    {
        return $this->innerService->countContentDrafts($user);
    }

    public function loadContentDraftList(?User $user = null, int $offset = 0, int $limit = -1): ContentDraftList
    {
        return $this->innerService->loadContentDraftList($user, $offset, $limit);
    }

    public function updateContent(
        VersionInfo $versionInfo,
        ContentUpdateStruct $contentUpdateStruct,
        ?array $fieldIdentifiersToValidate = null
    ): Content {
        return $this->innerService->updateContent($versionInfo, $contentUpdateStruct, $fieldIdentifiersToValidate);
    }

    public function publishVersion(VersionInfo $versionInfo, array $translations = Language::ALL): Content
    {
        return $this->innerService->publishVersion($versionInfo, $translations);
    }

    public function deleteVersion(VersionInfo $versionInfo): void
    {
        $this->innerService->deleteVersion($versionInfo);
    }

    public function loadVersions(ContentInfo $contentInfo, ?int $status = null): iterable
    {
        return $this->innerService->loadVersions($contentInfo, $status);
    }

    public function copyContent(
        ContentInfo $contentInfo,
        LocationCreateStruct $destinationLocationCreateStruct,
        ?VersionInfo $versionInfo = null
    ): Content {
        return $this->innerService->copyContent($contentInfo, $destinationLocationCreateStruct, $versionInfo);
    }

    public function countRelations(VersionInfo $versionInfo, ?RelationType $type = null): int
    {
        return $this->innerService->countRelations($versionInfo, $type);
    }

    public function loadRelationList(
        VersionInfo $versionInfo,
        int $offset = 0,
        int $limit = self::DEFAULT_PAGE_SIZE,
        ?RelationType $type = null
    ): RelationList {
        return $this->innerService->loadRelationList($versionInfo, $offset, $limit, $type);
    }

    public function countReverseRelations(ContentInfo $contentInfo, ?RelationType $type = null): int
    {
        return $this->innerService->countReverseRelations($contentInfo, $type);
    }

    public function loadReverseRelations(ContentInfo $contentInfo, ?RelationType $type = null): iterable
    {
        return $this->innerService->loadReverseRelations($contentInfo, $type);
    }

    public function loadReverseRelationList(
        ContentInfo $contentInfo,
        int $offset = 0,
        int $limit = -1,
        ?RelationType $type = null
    ): RelationList {
        return $this->innerService->loadReverseRelationList($contentInfo, $offset, $limit, $type);
    }

    public function addRelation(
        VersionInfo $sourceVersion,
        ContentInfo $destinationContent
    ): Relation {
        return $this->innerService->addRelation($sourceVersion, $destinationContent);
    }

    public function deleteRelation(
        VersionInfo $sourceVersion,
        ContentInfo $destinationContent
    ): void {
        $this->innerService->deleteRelation($sourceVersion, $destinationContent);
    }

    public function deleteTranslation(
        ContentInfo $contentInfo,
        string $languageCode
    ): void {
        $this->innerService->deleteTranslation($contentInfo, $languageCode);
    }

    public function deleteTranslationFromDraft(
        VersionInfo $versionInfo,
        string $languageCode
    ): Content {
        return $this->innerService->deleteTranslationFromDraft($versionInfo, $languageCode);
    }

    public function hideContent(ContentInfo $contentInfo): void
    {
        $this->innerService->hideContent($contentInfo);
    }

    public function revealContent(ContentInfo $contentInfo): void
    {
        $this->innerService->revealContent($contentInfo);
    }

    public function newContentCreateStruct(
        ContentType $contentType,
        string $mainLanguageCode
    ): ContentCreateStruct {
        return $this->innerService->newContentCreateStruct($contentType, $mainLanguageCode);
    }

    public function newContentMetadataUpdateStruct(): ContentMetadataUpdateStruct
    {
        return $this->innerService->newContentMetadataUpdateStruct();
    }

    public function newContentUpdateStruct(): ContentUpdateStruct
    {
        return $this->innerService->newContentUpdateStruct();
    }

    public function validate(
        ValueObject $object,
        array $context,
        ?array $fieldIdentifiersToValidate = null
    ): array {
        return $this->innerService->validate($object, $context, $fieldIdentifiersToValidate);
    }

    public function find(Filter $filter, ?array $languages = null): ContentList
    {
        return $this->innerService->find($filter, $languages);
    }

    public function count(Filter $filter, ?array $languages = null): int
    {
        return $this->innerService->count($filter, $languages);
    }
}

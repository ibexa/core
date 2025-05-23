<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content;

// @todo We must verify whether we want to type cast on the "Criterion" interface or abstract class
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct as RelationCreateStruct;

/**
 * The Content Handler interface defines content operations on the storage engine.
 *
 * The basic operations which are performed on content objects are collected in
 * this interface. Typically this interface would be used by a service managing
 * business logic for content objects.
 */
interface Handler
{
    /**
     * Creates a new Content entity in the storage engine.
     *
     * The values contained inside the $content will form the basis of stored
     * entity.
     *
     * Will contain always a complete list of fields.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\CreateStruct $content Content creation struct.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content Content value object
     */
    public function create(CreateStruct $content);

    /**
     * Creates a new draft version from $contentId in $srcVersion number.
     *
     * Copies all fields from $contentId in $srcVersion and creates a new
     * version of the referred Content from it.
     *
     * @param mixed $contentId
     * @param mixed $srcVersion
     * @param mixed $userId
     * @param string|null $languageCode
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    public function createDraftFromVersion($contentId, $srcVersion, $userId, ?string $languageCode = null);

    /**
     * Returns the raw data of a content object identified by $id, in a struct.
     *
     * If you want to load current version, $version number can be omitted to make sure
     * you don't need to rely on search index (async) or having to load in two steps
     * (first content info then content, risking changes in between to current version).
     *
     * Optionally a translation filter may be specified. If specified only the
     * translations with the listed language codes will be retrieved. If not,
     * all translations will be retrieved.
     *
     * @param int|string $id
     * @param int|null $version
     * @param string[]|null $translations
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content Content value object
     */
    public function load($id, $version = null, array $translations = null);

    /**
     * Return list of unique Content, with content id as key.
     *
     * Missing items (NotFound) will be missing from the array and not cause an exception, it's up
     * to calling logic to determine if this should cause exception or not.
     *
     * If items are missing but for other reasons then not being found, for instance exceptions during loading field
     * data. Then the exception will be logged as warning or error depending on severity.
     * The most common case of possible exceptions during loading of Content data is migration,
     * where either custom Field Type configuration or implementation might not be aligned with new
     * version of the system.
     *
     * NOTE!!: If you want to take always available flag into account, append main language
     * to the list of languages(unless caller is asking for all languages). In some edge cases you'll end up with a
     * bit more data returned, but upside is that storage engine is able to handle far larger datasets.
     *
     * @param int[] $contentIds
     * @param string[]|null $translations
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content[]
     */
    public function loadContentList(array $contentIds, array $translations = null): iterable;

    /**
     * Returns the metadata object for a content identified by $contentId.
     *
     * @param int|string $contentId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo
     */
    public function loadContentInfo($contentId);

    /**
     * Return list of unique Content Info, with content id as key.
     *
     * Missing items (NotFound) will be missing from the array and not cause an exception, it's up
     * to calling logic to determine if this should cause exception or not.
     *
     * @param array $contentIds
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo[]
     */
    public function loadContentInfoList(array $contentIds);

    /**
     * Returns the metadata object for a content identified by $remoteId.
     *
     * @param mixed $remoteId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo
     */
    public function loadContentInfoByRemoteId($remoteId);

    /**
     * Returns the version object for a content/version identified by $contentId and $versionNo.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If version is not found
     *
     * @param int|string $contentId
     * @param int|null $versionNo Version number to load, loads current version if null.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo
     */
    public function loadVersionInfo($contentId, $versionNo = null);

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @return int[]
     */
    public function loadVersionNoArchivedWithin(int $contentId, int $seconds): array;

    /**
     * Returns the number of versions with draft status created by the given $userId.
     *
     * @param int $userId
     *
     * @return int
     */
    public function countDraftsForUser(int $userId): int;

    /**
     * Returns all versions with draft status created by the given $userId.
     *
     * @param int $userId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo[]
     */
    public function loadDraftsForUser($userId);

    /**
     * Loads drafts for a user when content is not in the trash. The list is sorted by modification date.
     *
     * @param int $userId
     * @param int $offset
     * @param int $limit
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo[]
     */
    public function loadDraftListForUser(int $userId, int $offset = 0, int $limit = -1): array;

    /**
     * Sets the status of object identified by $contentId and $version to $status.
     *
     * The $status can be one of VersionInfo::STATUS_DRAFT, VersionInfo::STATUS_PUBLISHED, VersionInfo::STATUS_ARCHIVED
     * When status is set to VersionInfo::STATUS_PUBLISHED content status is updated to ContentInfo::STATUS_PUBLISHED
     *
     * @param int $contentId
     * @param int $status
     * @param int $version
     *
     * @return bool
     */
    public function setStatus($contentId, $status, $version);

    /**
     * Updates a content object meta data, identified by $contentId.
     *
     * @param int $contentId
     * @param \Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct $content
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo
     */
    public function updateMetadata($contentId, MetadataUpdateStruct $content);

    /**
     * Updates a content version, identified by $contentId and $versionNo.
     *
     * @param int $contentId
     * @param int $versionNo
     * @param \Ibexa\Contracts\Core\Persistence\Content\UpdateStruct $content
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    public function updateContent($contentId, $versionNo, UpdateStruct $content);

    /**
     * Deletes all versions and fields, all locations (subtree), and all relations.
     *
     * Removes the relations, but not the related objects. All subtrees of the
     * assigned nodes of this content objects are removed (recursively).
     *
     * @param int $contentId
     *
     * @return bool
     */
    public function deleteContent($contentId);

    /**
     * Deletes given version, its fields, node assignment, relations and names.
     *
     * Removes the relations, but not the related objects.
     *
     * @param int $contentId
     * @param int $versionNo
     *
     * @return bool
     */
    public function deleteVersion($contentId, $versionNo);

    /**
     * Returns the versions for $contentId.
     *
     * Result is returned with oldest version first (sorted by created, alternatively version number or id if auto increment).
     *
     * @param int $contentId
     * @param mixed|null $status Optional argument to filter versions by status, like {@see VersionInfo::STATUS_ARCHIVED}.
     * @param int $limit Limit for items returned, -1 means none.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo[]
     */
    public function listVersions($contentId, $status = null, $limit = -1);

    /**
     * Copy Content with Fields, Versions & Relations from $contentId in $version.
     *
     * Copies all fields and relations from $contentId in $version (or all versions if false)
     * to a new object which is returned. Version numbers are maintained.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If content or version is not found
     *
     * @param mixed $contentId
     * @param mixed|null $versionNo Copy all versions if left null
     * @param int|null $newOwnerId By default owner is same content we copy, for other cases set owner here to change it.
     *        E.g. In order to give person copying access to edit (if owner limitation), use this to set copier as owner.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    public function copy($contentId, $versionNo = null, $newOwnerId = null);

    /**
     * Creates a relation between $sourceContentId in $sourceContentVersionNo
     * and $destinationContentId with a specific $type.
     *
     * @todo Should the existence verifications happen here or is this supposed to be handled at a higher level?
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct $createStruct
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Relation
     */
    public function addRelation(RelationCreateStruct $createStruct);

    /**
     * Load a relation by $relationId.
     */
    public function loadRelation(int $relationId): Relation;

    /**
     * Removes a relation by relation Id.
     *
     * @todo Should the existence verifications happen here or is this supposed to be handled at a higher level?
     *
     * @param int $relationId
     * @param int $type {@see \Ibexa\Contracts\Core\Repository\Values\Content\Relation::COMMON,
     *                 \Ibexa\Contracts\Core\Repository\Values\Content\Relation::EMBED,
     *                 \Ibexa\Contracts\Core\Repository\Values\Content\Relation::LINK,
     *                 \Ibexa\Contracts\Core\Repository\Values\Content\Relation::FIELD}
     * @param ?int $destinationContentId Content id to invalidate cache tag related to content reverse relations count
     */
    public function removeRelation($relationId, $type, ?int $destinationContentId = null);

    /**
     * Counts all outgoing relations for the given version.
     */
    public function countRelations(
        int $sourceContentId,
        ?int $sourceContentVersionNo = null,
        ?int $type = null
    ): int;

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Relation[]
     */
    public function loadRelationList(
        int $sourceContentId,
        int $limit,
        int $offset = 0,
        ?int $sourceContentVersionNo = null,
        ?int $type = null
    ): array;

    /**
     * Counts relations from $destinationContentId only against published versions. Optionally, count only those with $type.
     *
     * @param int $destinationContentId Destination Content ID
     * @param int|null $type The relation type bitmask {@see \Ibexa\Contracts\Core\Repository\Values\Content\Relation}
     *
     * @return int
     */
    public function countReverseRelations(int $destinationContentId, ?int $type = null): int;

    /**
     * Loads relations from $contentId. Optionally, loads only those with $type.
     *
     * Only loads relations against published versions.
     *
     * @param mixed $destinationContentId Destination Content ID
     * @param int|null $type {@see \Ibexa\Contracts\Core\Repository\Values\Content\Relation::COMMON,
     *                 \Ibexa\Contracts\Core\Repository\Values\Content\Relation::EMBED,
     *                 \Ibexa\Contracts\Core\Repository\Values\Content\Relation::LINK,
     *                 \Ibexa\Contracts\Core\Repository\Values\Content\Relation::FIELD}
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Relation[]
     */
    public function loadReverseRelations($destinationContentId, $type = null);

    /**
     * Loads paginated relations from $contentId. Optionally, loads only those with $type.
     *
     * Only loads relations against published versions.
     *
     * @param int $destinationContentId Destination Content ID
     * @param int $offset
     * @param int $limit
     * @param int|null $type The relation type bitmask {@see \Ibexa\Contracts\Core\Repository\Values\Content\Relation}
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Relation[]
     */
    public function loadReverseRelationList(
        int $destinationContentId,
        int $offset = 0,
        int $limit = -1,
        ?int $type = null
    ): array;

    /**
     * Performs the publishing operations required to set the version identified by $updateStruct->versionNo and
     * $updateStruct->id as the published one.
     *
     * @param int $contentId
     * @param int $versionNo
     * @param \Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct $metaDataUpdateStruct
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content The published Content
     */
    public function publish($contentId, $versionNo, MetadataUpdateStruct $metaDataUpdateStruct);

    /**
     * Delete the specified translation from all the Versions of a Content Object.
     *
     * @param int $contentId
     * @param string $languageCode language code of the translation
     */
    public function deleteTranslationFromContent($contentId, $languageCode);

    /**
     * Remove the specified Translation from the given Version Draft of a Content Object.
     *
     * @param int $contentId
     * @param int $versionNo
     * @param string $languageCode
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content The Content Draft w/o removed Translation
     */
    public function deleteTranslationFromDraft($contentId, $versionNo, $languageCode);

    /**
     * @param array<int> $contentIds
     *
     * @return array<\Ibexa\Contracts\Core\Persistence\Content\VersionInfo>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function loadVersionInfoList(array $contentIds): array;
}

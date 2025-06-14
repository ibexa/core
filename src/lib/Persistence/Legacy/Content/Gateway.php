<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct as RelationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;

/**
 * Base class for content gateways.
 *
 * @internal For internal use by Persistence Handlers.
 */
abstract class Gateway
{
    public const CONTENT_ITEM_TABLE = 'ibexa_content';
    public const CONTENT_NAME_TABLE = 'ibexa_content_name';
    public const CONTENT_FIELD_TABLE = 'ibexa_content_field';
    public const CONTENT_VERSION_TABLE = 'ibexa_content_version';
    public const CONTENT_RELATION_TABLE = 'ibexa_content_relation';

    public const CONTENT_ITEM_SEQ = 'ibexa_content_id_seq';
    public const CONTENT_VERSION_SEQ = 'ibexa_content_version_id_seq';
    public const CONTENT_FIELD_SEQ = 'ibexa_content_field_id_seq';
    public const CONTENT_RELATION_SEQ = 'ibexa_content_relation_id_seq';

    /**
     * Insert a new Content item.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function insertContentObject(
        CreateStruct $struct,
        int $currentVersionNo = 1
    ): int;

    /**
     * Insert a new Version.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field[] $fields
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function insertVersion(VersionInfo $versionInfo, array $fields): int;

    /**
     * Update an existing content identified by $contentId based on $struct.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function updateContent(
        int $contentId,
        MetadataUpdateStruct $struct,
        ?VersionInfo $prePublishVersionInfo = null
    ): void;

    /**
     * Updates version $versionNo for content identified by $contentId, in respect to $struct.
     */
    abstract public function updateVersion(int $contentId, int $versionNo, UpdateStruct $struct): void;

    /**
     * Update "always available" flag for content identified by $contentId based on $alwaysAvailable.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function updateAlwaysAvailableFlag(
        int $contentId,
        ?bool $newAlwaysAvailable = null
    ): void;

    /**
     * Set the state of object identified by $contentId and $version to $state.
     *
     * @param int $status the one of STATUS_DRAFT, STATUS_PUBLISHED, STATUS_ARCHIVED
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    abstract public function setStatus(int $contentId, int $version, int $status): bool;

    /**
     * Dedicated operation which sets Version status as published, similar to setStatus, but checking
     * state of all versions to avoid race conditions.
     *
     * IMPORTANT: This method expects prior published version to have been set to another status then published before called, otherwise you'll get a BadStateException.
     *
     * @see setStatus
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if other operation affected publishing process
     */
    abstract public function setPublishedStatus(int $contentId, int $status): void;

    /**
     * Insert a new field.
     *
     * Only used when a new field is created (i.e. a new object or a field in a
     * new language!). After that, field IDs need to stay the same, only the
     * version number changes.
     */
    abstract public function insertNewField(
        Content $content,
        Field $field,
        StorageFieldValue $value
    ): int;

    /**
     * Insert an existing field.
     *
     * Used to insert a field with an existing ID but a new version number.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function insertExistingField(
        Content $content,
        Field $field,
        StorageFieldValue $value
    ): void;

    /**
     * Update an existing field.
     */
    abstract public function updateField(Field $field, StorageFieldValue $value): void;

    /**
     * Update an existing, non-translatable field.
     */
    abstract public function updateNonTranslatableField(
        Field $field,
        StorageFieldValue $value,
        int $contentId
    ): void;

    /**
     * Load data for a content object.
     *
     * Returns an array with the relevant data.
     *
     * @param int|null $version Current version on null value.
     * @param string[]|null $translations
     */
    abstract public function load(
        int $contentId,
        ?int $version = null,
        ?array $translations = null
    ): array;

    /**
     * Load current versions for a list of content item numeric IDs.
     *
     * @param int[] $contentIds
     * @param string[]|null $translations If languages is not set, ALL will be loaded.
     *
     * @return array[]
     */
    abstract public function loadContentList(array $contentIds, ?array $translations = null): array;

    /**
     * Load info for a content object identified by its remote ID.
     *
     * Returns an array with the relevant data.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function loadContentInfoByRemoteId(string $remoteId): array;

    /**
     * Load info for a content object identified by its location ID (node ID).
     *
     * Returns an array with the relevant data.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function loadContentInfoByLocationId(int $locationId): array;

    /**
     * Load info for content identified by $contentId.
     * Will basically return a hash containing all field values for ibexa_content table plus following keys:
     *  - always_available => Boolean indicating if content's language mask contains alwaysAvailable bit field
     *  - main_language_code => Language code for main (initial) language. E.g. "eng-GB".
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function loadContentInfo(int $contentId): array;

    /**
     * Loads rows of info for content identified by $contentIds.
     *
     * @see loadContentInfo For the returned structure.
     * @see \Ibexa\Contracts\Core\Persistence\Content\Handler::loadContentInfoList For how this will only return items found and not throw.
     *
     * @param int[] $contentIds
     */
    abstract public function loadContentInfoList(array $contentIds): array;

    /**
     * Load version info for content identified by $contentId and $versionNo.
     * Will basically return a hash containing all field values from ibexa_content_version table plus following keys:
     *  - names => Hash of content object names. Key is the language code, value is the name.
     *  - languages => Hash of language ids. Key is the language code (e.g. "eng-GB"), value is the language numeric id without the always available bit.
     *  - initial_language_code => Language code for initial language in this version.
     *
     * @param int|null $versionNo Load current version if null.
     */
    abstract public function loadVersionInfo(int $contentId, ?int $versionNo = null): array;

    /**
     * @return array<mixed>
     */
    abstract public function loadVersionNoArchivedWithin(int $contentId, int $seconds): array;

    /**
     * Return the number of all versions with given status created by the given $userId.
     */
    abstract public function countVersionsForUser(
        int $userId,
        int $status = VersionInfo::STATUS_DRAFT
    ): int;

    /**
     * Returns data for all versions with given status created by the given $userId.
     *
     * @return string[][]
     */
    abstract public function listVersionsForUser(
        int $userId,
        int $status = VersionInfo::STATUS_DRAFT
    );

    /**
     * Return data for all versions with given status created by the given $userId when content is not in the trash.
     *
     * The list is sorted by modification date.
     */
    abstract public function loadVersionsForUser(
        int $userId,
        int $status = VersionInfo::STATUS_DRAFT,
        int $offset = 0,
        int $limit = -1
    ): array;

    /**
     * Returns all version data for the given $contentId.
     *
     * Result is returned with oldest version first (using version id as it has index and is auto increment).
     *
     * @param int|null $status Optional argument to filter versions by status, like {@see VersionInfo::STATUS_ARCHIVED}.
     * @param int $limit Limit for items returned, -1 means none.
     *
     * @return string[][]
     */
    abstract public function listVersions(
        int $contentId,
        ?int $status = null,
        int $limit = -1
    ): array;

    /**
     * Return all version numbers for the given $contentId.
     *
     * @return int[]
     */
    abstract public function listVersionNumbers(int $contentId): array;

    /**
     * Return last version number for content identified by $contentId.
     */
    abstract public function getLastVersionNumber(int $contentId): int;

    /**
     * Returns all IDs for locations that refer to $contentId.
     *
     * @return int[]
     */
    abstract public function getAllLocationIds(int $contentId): array;

    /**
     * Returns all field IDs of $contentId grouped by their type.
     * If $versionNo is set only field IDs for that version are returned.
     * If $languageCode is set, only field IDs for that language are returned.
     *
     * @return int[][]
     */
    abstract public function getFieldIdsByType(
        int $contentId,
        ?int $versionNo = null,
        ?string $languageCode = null
    ): array;

    /**
     * Deletes relations to and from $contentId.
     * If $versionNo is set only relations for that version are deleted.
     */
    abstract public function deleteRelations(int $contentId, ?int $versionNo = null): void;

    /**
     * Remove relations to Content with $contentId from Relation and RelationList field type fields.
     */
    abstract public function removeReverseFieldRelations(int $contentId): void;

    /**
     * Removes orphaned relations resulting from deleted relation fieldtype.
     */
    abstract public function removeRelationsByFieldDefinitionId(int $fieldDefinitionId): void;

    /**
     * Delete the field with the given $fieldId.
     */
    abstract public function deleteField(int $fieldId): void;

    /**
     * Delete all fields of $contentId in all versions.
     *
     * If $versionNo is set only fields for that version are deleted.
     */
    abstract public function deleteFields(int $contentId, ?int $versionNo = null): void;

    /**
     * Delete all versions of $contentId.
     *
     * If $versionNo is set only that version is deleted.
     */
    abstract public function deleteVersions(int $contentId, ?int $versionNo = null): void;

    /**
     * Delete all names of $contentId.
     *
     * If $versionNo is set only names for that version are deleted.
     */
    abstract public function deleteNames(int $contentId, ?int $versionNo = null): void;

    /**
     * Set the content object name.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function setName(
        int $contentId,
        int $version,
        string $name,
        string $languageCode
    ): void;

    /**
     * Delete the actual content object referred to by $contentId.
     */
    abstract public function deleteContent(int $contentId): void;

    /**
     * Load data of related to/from $contentId.
     *
     * @return array Content data, array structured like {@see \Ibexa\Core\Persistence\Legacy\Content\Gateway::load}
     */
    abstract public function loadRelations(
        int $contentId,
        ?int $contentVersionNo = null,
        ?int $relationType = null
    ): array;

    /**
     * Counts number of related to/from $contentId.
     */
    abstract public function countRelations(
        int $contentId,
        ?int $contentVersionNo = null,
        ?int $relationType = null
    ): int;

    /**
     * Loads paginated data of related to/from $contentId.
     *
     * @return array<array<string, mixed>>
     */
    abstract public function listRelations(
        int $contentId,
        int $limit,
        int $offset = 0,
        ?int $contentVersionNo = null,
        ?int $relationType = null
    ): array;

    /**
     * Counts number of related to/from $contentId.
     */
    abstract public function countReverseRelations(int $contentId, ?int $relationType = null): int;

    /**
     * Load data of related to/from $contentId.
     *
     * @return array Content data, array structured like {@see \Ibexa\Core\Persistence\Legacy\Content\Gateway::load}
     */
    abstract public function loadReverseRelations(int $contentId, ?int $relationType = null): array;

    /**
     * Load paginated data of related to/from $contentId.
     */
    abstract public function listReverseRelations(
        int $contentId,
        int $offset = 0,
        int $limit = -1,
        ?int $relationType = null
    ): array;

    /**
     * Delete the relation with the given $relationId.
     *
     * @param int $type one of Relation type constants.
     *
     * @see \Ibexa\Contracts\Core\Repository\Values\Content\Relation
     */
    abstract public function deleteRelation(int $relationId, int $type): void;

    /**
     * Insert a new content relation.
     */
    abstract public function insertRelation(RelationCreateStruct $createStruct): int;

    /**
     * Load Relation object.
     *
     * @see \Ibexa\Contracts\Core\Persistence\Content\Relation
     */
    abstract public function loadRelation(int $relationId): array;

    /**
     * Return all Content IDs for the given $contentTypeId.
     *
     * @return int[]
     */
    abstract public function getContentIdsByContentTypeId(int $contentTypeId): array;

    /**
     * Load name data for set of content id's and corresponding version number.
     *
     * @param array[] $rows array of hashes with 'id' and 'version' to load names for
     */
    abstract public function loadVersionedNameData(array $rows): array;

    /**
     * Bulk-copy all relations meta data for a copied Content item.
     *
     * Is meant to be used during content copy, so assumes the following:
     * - version number is the same
     * - content type, and hence content type attribute is the same
     * - relation type is the same
     * - target relation is the same
     *
     * @param int|null $versionNo If specified only copy for a given version number, otherwise all.
     */
    abstract public function copyRelations(
        int $originalContentId,
        int $copiedContentId,
        ?int $versionNo = null
    ): void;

    /**
     * Remove the specified translation from the Content Object Version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    abstract public function deleteTranslationFromContent(
        int $contentId,
        string $languageCode
    ): void;

    /**
     * Delete Content fields (attributes) for the given Translation.
     *
     * If $versionNo is given, fields for that Version only will be deleted.
     */
    abstract public function deleteTranslatedFields(
        string $languageCode,
        int $contentId,
        ?int $versionNo = null
    ): void;

    /**
     * Delete the specified Translation from the given Version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    abstract public function deleteTranslationFromVersion(
        int $contentId,
        int $versionNo,
        string $languageCode
    ): void;

    /**
     * @param array<int> $contentIds
     *
     * @throws \Ibexa\Core\Base\Exceptions\DatabaseException
     */
    abstract public function loadVersionInfoList(array $contentIds): array;
}

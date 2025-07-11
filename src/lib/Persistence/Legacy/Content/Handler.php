<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content;

use Exception;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Handler as BaseContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Relation;
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct as RelationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Persistence\Content\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\Base\Exceptions\NotFoundException as NotFound;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway as UrlAliasGateway;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The Content Handler stores Content and ContentType objects.
 */
class Handler implements BaseContentHandler
{
    /**
     * Content gateway.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Gateway
     */
    protected $contentGateway;

    /**
     * Location gateway.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Location\Gateway
     */
    protected $locationGateway;

    /**
     * Mapper.
     *
     * @var Mapper
     */
    protected $mapper;

    /**
     * FieldHandler.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\FieldHandler
     */
    protected $fieldHandler;

    /**
     * URL slug converter.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter
     */
    protected $slugConverter;

    /**
     * UrlAlias gateway.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway
     */
    protected $urlAliasGateway;

    /**
     * ContentType handler.
     *
     * @var \Ibexa\Contracts\Core\Persistence\Content\Type\Handler
     */
    protected $contentTypeHandler;

    /**
     * Tree handler.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\TreeHandler
     */
    protected $treeHandler;

    protected LanguageHandler $languageHandler;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Creates a new content handler.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\Gateway $contentGateway
     * @param \Ibexa\Core\Persistence\Legacy\Content\Location\Gateway $locationGateway
     * @param \Ibexa\Core\Persistence\Legacy\Content\Mapper $mapper
     * @param \Ibexa\Core\Persistence\Legacy\Content\FieldHandler $fieldHandler
     * @param \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter $slugConverter
     * @param \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway $urlAliasGateway
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\Handler $contentTypeHandler
     * @param \Ibexa\Core\Persistence\Legacy\Content\TreeHandler $treeHandler
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(
        Gateway $contentGateway,
        LocationGateway $locationGateway,
        Mapper $mapper,
        FieldHandler $fieldHandler,
        SlugConverter $slugConverter,
        UrlAliasGateway $urlAliasGateway,
        ContentTypeHandler $contentTypeHandler,
        TreeHandler $treeHandler,
        LanguageHandler $languageHandler,
        LoggerInterface $logger = null
    ) {
        $this->contentGateway = $contentGateway;
        $this->locationGateway = $locationGateway;
        $this->mapper = $mapper;
        $this->fieldHandler = $fieldHandler;
        $this->slugConverter = $slugConverter;
        $this->urlAliasGateway = $urlAliasGateway;
        $this->contentTypeHandler = $contentTypeHandler;
        $this->treeHandler = $treeHandler;
        $this->languageHandler = $languageHandler;
        $this->logger = null !== $logger ? $logger : new NullLogger();
    }

    /**
     * Creates a new Content entity in the storage engine.
     *
     * The values contained inside the $content will form the basis of stored
     * entity.
     *
     * Will contain always a complete list of fields.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\CreateStruct $struct Content creation struct.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content Content value object
     */
    public function create(CreateStruct $struct)
    {
        return $this->internalCreate($struct);
    }

    /**
     * Creates a new Content entity in the storage engine.
     *
     * The values contained inside the $content will form the basis of stored
     * entity.
     *
     * Will contain always a complete list of fields.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\CreateStruct $struct Content creation struct.
     * @param mixed $versionNo Used by self::copy() to maintain version numbers
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content Content value object
     */
    protected function internalCreate(CreateStruct $struct, $versionNo = 1)
    {
        $content = new Content();

        $content->fields = $struct->fields;
        $content->versionInfo = $this->mapper->createVersionInfoFromCreateStruct($struct, $versionNo);

        $content->versionInfo->contentInfo->id = $this->contentGateway->insertContentObject($struct, $versionNo);
        $content->versionInfo->id = $this->contentGateway->insertVersion(
            $content->versionInfo,
            $struct->fields
        );

        $contentType = $this->contentTypeHandler->load($struct->typeId);
        $this->fieldHandler->createNewFields($content, $contentType);

        // Create node assignments
        foreach ($struct->locations as $location) {
            $location->contentId = $content->versionInfo->contentInfo->id;
            $location->contentVersion = $content->versionInfo->versionNo;
            $this->locationGateway->createNodeAssignment(
                $location,
                $location->parentId,
                LocationGateway::NODE_ASSIGNMENT_OP_CODE_CREATE
            );
        }

        // Create names
        foreach ($content->versionInfo->names as $language => $name) {
            $this->contentGateway->setName(
                $content->versionInfo->contentInfo->id,
                $content->versionInfo->versionNo,
                $name,
                $language
            );
        }

        return $content;
    }

    /**
     * Performs the publishing operations required to set the version identified by $updateStruct->versionNo and
     * $updateStruct->id as the published one.
     *
     * The publish procedure will:
     * - Create location nodes based on the node assignments
     * - Update the content object using the provided metadata update struct
     * - Update the node assignments
     * - Update location nodes of the content with the new published version
     * - Set content and version status to published
     *
     * @param int $contentId
     * @param int $versionNo
     * @param \Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct $metaDataUpdateStruct
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content The published Content
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function publish($contentId, $versionNo, MetadataUpdateStruct $metaDataUpdateStruct)
    {
        // Archive currently published version
        $versionInfo = $this->loadVersionInfo($contentId, $versionNo);
        if ($versionInfo->contentInfo->currentVersionNo != $versionNo) {
            $this->setStatus(
                $contentId,
                VersionInfo::STATUS_ARCHIVED,
                $versionInfo->contentInfo->currentVersionNo
            );
        }

        // Set always available name for the content
        $metaDataUpdateStruct->name = $versionInfo->names[$versionInfo->contentInfo->mainLanguageCode];

        $this->contentGateway->updateContent($contentId, $metaDataUpdateStruct, $versionInfo);
        $this->locationGateway->createLocationsFromNodeAssignments(
            $contentId,
            $versionNo
        );

        $this->locationGateway->updateLocationsContentVersionNo($contentId, $versionNo);
        $this->contentGateway->setPublishedStatus($contentId, $versionNo);

        return $this->load($contentId, $versionNo);
    }

    /**
     * Creates a new draft version from $contentId in $version.
     *
     * Copies all fields from $contentId in $srcVersion and creates a new
     * version of the referred Content from it.
     *
     * Note: When creating a new draft in the old admin interface there will
     * also be an entry in the `ibexa_node_assignment` created for the draft. This
     * is ignored in this implementation.
     *
     * @param mixed $contentId
     * @param mixed $srcVersion
     * @param mixed $userId
     * @param string|null $languageCode
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function createDraftFromVersion($contentId, $srcVersion, $userId, ?string $languageCode = null)
    {
        $content = $this->load($contentId, $srcVersion);

        // Create new version
        $content->versionInfo = $this->mapper->createVersionInfoForContent(
            $content,
            $this->contentGateway->getLastVersionNumber($contentId) + 1,
            $userId,
            $languageCode
        );
        $content->versionInfo->id = $this->contentGateway->insertVersion(
            $content->versionInfo,
            $content->fields
        );

        // Clone fields from previous version and append them to the new one
        $this->fieldHandler->createExistingFieldsInNewVersion($content);

        // Persist virtual fields
        $contentType = $this->contentTypeHandler->load($content->versionInfo->contentInfo->contentTypeId);
        $this->fieldHandler->updateFields($content, new UpdateStruct([
            'initialLanguageId' => $this->languageHandler->loadByLanguageCode(
                $content->versionInfo->initialLanguageCode
            )->id,
        ]), $contentType);

        // Create relations for new version
        $relations = $this->contentGateway->loadRelations($contentId, $srcVersion);
        foreach ($relations as $relation) {
            $this->contentGateway->insertRelation(
                new RelationCreateStruct(
                    [
                        'sourceContentId' => $contentId,
                        'sourceContentVersionNo' => $content->versionInfo->versionNo,
                        'sourceFieldDefinitionId' => $relation['content_link_content_type_field_definition_id'],
                        'destinationContentId' => $relation['content_link_to_contentobject_id'],
                        'type' => (int)$relation['content_link_relation_type'],
                    ]
                )
            );
        }

        // Create content names for new version
        foreach ($content->versionInfo->names as $language => $name) {
            $this->contentGateway->setName(
                $contentId,
                $content->versionInfo->versionNo,
                $name,
                $language
            );
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function load($id, $version = null, array $translations = null)
    {
        $rows = $this->contentGateway->load($id, $version, $translations);

        if (empty($rows)) {
            throw new NotFound('content', "contentId: $id, versionNo: $version");
        }

        $contentObjects = $this->mapper->extractContentFromRows(
            $rows,
            $this->contentGateway->loadVersionedNameData([[
                'id' => $id,
                'version' => $rows[0]['content_version_version'],
            ]]),
            'content_',
            $translations
        );
        $content = $contentObjects[0];
        unset($rows, $contentObjects);

        $this->fieldHandler->loadExternalFieldData($content);

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function loadContentList(array $contentIds, array $translations = null): array
    {
        $rawList = $this->contentGateway->loadContentList($contentIds, $translations);
        if (empty($rawList)) {
            return [];
        }

        $idVersionPairs = [];
        foreach ($rawList as $row) {
            // As there is only one version per id, set id as key to avoid duplicates
            $idVersionPairs[$row['content_id']] = [
                'id' => $row['content_id'],
                'version' => $row['content_version_version'],
            ];
        }

        // group name data per Content Id
        $nameData = $this->contentGateway->loadVersionedNameData(array_values($idVersionPairs));
        $contentItemNameData = [];
        foreach ($nameData as $nameDataRow) {
            $contentId = $nameDataRow['content_name_contentobject_id'];
            $contentItemNameData[$contentId][] = $nameDataRow;
        }

        // group rows per Content Id be able to ignore Content items with erroneous data
        $contentItemsRows = [];
        foreach ($rawList as $row) {
            $contentId = $row['content_id'];
            $contentItemsRows[$contentId][] = $row;
        }
        unset($rawList, $idVersionPairs);

        // try to extract Content from each Content data
        $contentItems = [];
        foreach ($contentItemsRows as $contentId => $contentItemsRow) {
            try {
                $contentList = $this->mapper->extractContentFromRows(
                    $contentItemsRow,
                    $contentItemNameData[$contentId],
                    'content_',
                    $translations
                );
                $contentItems[$contentId] = $contentList[0];
            } catch (Exception $e) {
                $this->logger->warning(
                    sprintf(
                        '%s: Content %d not loaded: %s',
                        __METHOD__,
                        $contentId,
                        $e->getMessage()
                    )
                );
            }
        }

        // try to load External Storage data for each Content, ignore Content items for which it failed
        foreach ($contentItems as $contentId => $content) {
            try {
                $this->fieldHandler->loadExternalFieldData($content);
            } catch (Exception $e) {
                unset($contentItems[$contentId]);
                $this->logger->warning(
                    sprintf(
                        '%s: Content %d not loaded: %s',
                        __METHOD__,
                        $contentId,
                        $e->getMessage()
                    )
                );
            }
        }

        return $contentItems;
    }

    /**
     * Returns the metadata object for a content identified by $contentId.
     *
     * @param int|string $contentId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo
     */
    public function loadContentInfo($contentId)
    {
        return $this->treeHandler->loadContentInfo($contentId);
    }

    public function loadContentInfoList(array $contentIds)
    {
        $list = $this->mapper->extractContentInfoFromRows(
            $this->contentGateway->loadContentInfoList($contentIds)
        );

        $listByContentId = [];
        foreach ($list as $item) {
            $listByContentId[$item->id] = $item;
        }

        return $listByContentId;
    }

    /**
     * Returns the metadata object for a content identified by $remoteId.
     *
     * @param mixed $remoteId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo
     */
    public function loadContentInfoByRemoteId($remoteId)
    {
        return $this->mapper->extractContentInfoFromRow(
            $this->contentGateway->loadContentInfoByRemoteId($remoteId)
        );
    }

    public function loadVersionInfo($contentId, $versionNo = null): VersionInfo
    {
        $rows = $this->contentGateway->loadVersionInfo((int)$contentId, $versionNo);
        if (empty($rows)) {
            throw new NotFound('content', $contentId);
        }

        $versionInfo = $this->mapper->extractVersionInfoListFromRows(
            $rows,
            $this->contentGateway->loadVersionedNameData([['id' => $contentId, 'version' => $rows[0]['content_version_version']]])
        );

        $versionInfo = reset($versionInfo);
        if (false === $versionInfo) {
            throw new NotFound('versionInfo', $contentId);
        }

        return $versionInfo;
    }

    public function loadVersionNoArchivedWithin(int $contentId, int $seconds): array
    {
        $rows = $this->contentGateway->loadVersionNoArchivedWithin($contentId, $seconds);
        if (empty($rows)) {
            throw new NotFound('content', $contentId);
        }

        $archivedVersionNos = [];
        foreach ($rows as $row) {
            $archivedVersionNos[] = (int) $row['content_version_version'];
        }

        return $archivedVersionNos;
    }

    public function countDraftsForUser(int $userId): int
    {
        return $this->contentGateway->countVersionsForUser($userId, VersionInfo::STATUS_DRAFT);
    }

    /**
     * Returns all versions with draft status created by the given $userId.
     *
     * @param int $userId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo[]
     */
    public function loadDraftsForUser($userId)
    {
        $rows = $this->contentGateway->listVersionsForUser($userId, VersionInfo::STATUS_DRAFT);
        if (empty($rows)) {
            return [];
        }

        $idVersionPairs = array_map(
            static function ($row) {
                return [
                    'id' => $row['content_version_contentobject_id'],
                    'version' => $row['content_version_version'],
                ];
            },
            $rows
        );
        $nameRows = $this->contentGateway->loadVersionedNameData($idVersionPairs);

        return $this->mapper->extractVersionInfoListFromRows($rows, $nameRows);
    }

    public function loadDraftListForUser(int $userId, int $offset = 0, int $limit = -1): array
    {
        $rows = $this->contentGateway->loadVersionsForUser($userId, VersionInfo::STATUS_DRAFT, $offset, $limit);
        if (empty($rows)) {
            return [];
        }

        $idVersionPairs = array_map(
            static function (array $row): array {
                return [
                    'id' => $row['content_version_contentobject_id'],
                    'version' => $row['content_version_version'],
                ];
            },
            $rows
        );
        $nameRows = $this->contentGateway->loadVersionedNameData($idVersionPairs);

        return $this->mapper->extractVersionInfoListFromRows($rows, $nameRows);
    }

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
    public function setStatus($contentId, $status, $version)
    {
        return $this->contentGateway->setStatus($contentId, $version, $status);
    }

    /**
     * Updates a content object meta data, identified by $contentId.
     *
     * @param int $contentId
     * @param \Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct $content
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo
     */
    public function updateMetadata($contentId, MetadataUpdateStruct $content)
    {
        $this->contentGateway->updateContent($contentId, $content);
        $this->updatePathIdentificationString($contentId, $content);

        return $this->loadContentInfo($contentId);
    }

    /**
     * Updates path identification string for locations of given $contentId if main language
     * is set in update struct.
     *
     * This is specific to the Legacy storage engine, as path identification string is deprecated.
     *
     * @param int $contentId
     * @param \Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct $content
     */
    protected function updatePathIdentificationString($contentId, MetadataUpdateStruct $content)
    {
        if (isset($content->mainLanguageId)) {
            $contentLocationsRows = $this->locationGateway->loadLocationDataByContent($contentId);
            foreach ($contentLocationsRows as $row) {
                $locationName = '';
                $urlAliasRows = $this->urlAliasGateway->loadLocationEntries(
                    $row['node_id'],
                    false,
                    $content->mainLanguageId
                );
                if (!empty($urlAliasRows)) {
                    $locationName = $urlAliasRows[0]['text'];
                }
                $this->locationGateway->updatePathIdentificationString(
                    $row['node_id'],
                    $row['parent_node_id'],
                    $this->slugConverter->convert(
                        $locationName,
                        'node_' . $row['node_id'],
                        'urlalias_compat'
                    )
                );
            }
        }
    }

    /**
     * Updates a content version, identified by $contentId and $versionNo.
     *
     * @param int $contentId
     * @param int $versionNo
     * @param \Ibexa\Contracts\Core\Persistence\Content\UpdateStruct $updateStruct
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    public function updateContent($contentId, $versionNo, UpdateStruct $updateStruct)
    {
        $content = $this->load($contentId, $versionNo);
        $this->contentGateway->updateVersion($contentId, $versionNo, $updateStruct);
        $contentType = $this->contentTypeHandler->load($content->versionInfo->contentInfo->contentTypeId);
        $this->fieldHandler->updateFields($content, $updateStruct, $contentType);
        foreach ($updateStruct->name as $language => $name) {
            $this->contentGateway->setName(
                $contentId,
                $versionNo,
                $name,
                $language
            );
        }

        return $this->load($contentId, $versionNo);
    }

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
    public function deleteContent($contentId)
    {
        $contentLocations = $this->contentGateway->getAllLocationIds($contentId);
        if (empty($contentLocations)) {
            $this->removeRawContent($contentId);
        } else {
            foreach ($contentLocations as $locationId) {
                $this->treeHandler->deleteChildrenDrafts($locationId);
                $this->treeHandler->removeSubtree($locationId);
            }
        }
    }

    /**
     * Deletes raw content data.
     *
     * @param int $contentId
     */
    public function removeRawContent($contentId)
    {
        $this->treeHandler->removeRawContent($contentId);
    }

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
    public function deleteVersion($contentId, $versionNo)
    {
        $versionInfo = $this->loadVersionInfo($contentId, $versionNo);

        $this->locationGateway->deleteNodeAssignment($contentId, $versionNo);

        $this->fieldHandler->deleteFields($contentId, $versionInfo);

        $this->contentGateway->deleteRelations($contentId, $versionNo);
        $this->contentGateway->deleteVersions($contentId, $versionNo);
        $this->contentGateway->deleteNames($contentId, $versionNo);
    }

    /**
     * Returns the versions for $contentId.
     *
     * Result is returned with oldest version first (sorted by created, alternatively version id if auto increment).
     *
     * @param int $contentId
     * @param mixed|null $status Optional argument to filter versions by status, like {@see VersionInfo::STATUS_ARCHIVED}.
     * @param int $limit Limit for items returned, -1 means none.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo[]
     */
    public function listVersions($contentId, $status = null, $limit = -1)
    {
        return $this->treeHandler->listVersions($contentId, $status, $limit);
    }

    /**
     * Copy Content with Fields, Versions & Relations from $contentId in $version.
     *
     * {@inheritdoc}
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If content or version is not found
     *
     * @param mixed $contentId
     * @param mixed|null $versionNo Copy all versions if left null
     * @param int|null $newOwnerId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    public function copy($contentId, $versionNo = null, $newOwnerId = null)
    {
        $currentVersionNo = isset($versionNo) ?
            $versionNo :
            $this->loadContentInfo($contentId)->currentVersionNo;

        // Copy content in given version or current version
        $createStruct = $this->mapper->createCreateStructFromContent(
            $this->load($contentId, $currentVersionNo)
        );
        if ($newOwnerId) {
            $createStruct->ownerId = $newOwnerId;
        }
        $content = $this->internalCreate($createStruct, $currentVersionNo);

        // If version was not passed also copy other versions
        if (!isset($versionNo)) {
            $contentType = $this->contentTypeHandler->load($createStruct->typeId);

            foreach ($this->listVersions($contentId) as $versionInfo) {
                if ($versionInfo->versionNo === $currentVersionNo) {
                    continue;
                }

                $versionContent = $this->load($contentId, $versionInfo->versionNo);

                $versionContent->versionInfo->contentInfo->id = $content->versionInfo->contentInfo->id;
                $versionContent->versionInfo->modificationDate = $createStruct->modified;
                $versionContent->versionInfo->creationDate = $createStruct->modified;
                $versionContent->versionInfo->creatorId = $createStruct->ownerId;

                $versionContent->versionInfo->id = $this->contentGateway->insertVersion(
                    $versionContent->versionInfo,
                    $versionContent->fields
                );

                $this->fieldHandler->createNewFields($versionContent, $contentType);

                // Create names
                foreach ($versionContent->versionInfo->names as $language => $name) {
                    $this->contentGateway->setName(
                        $content->versionInfo->contentInfo->id,
                        $versionInfo->versionNo,
                        $name,
                        $language
                    );
                }
            }

            // Batch copy relations for all versions
            $this->contentGateway->copyRelations($contentId, $content->versionInfo->contentInfo->id);
        } else {
            // Batch copy relations for published version
            $this->contentGateway->copyRelations($contentId, $content->versionInfo->contentInfo->id, $versionNo);
        }

        return $content;
    }

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
    public function addRelation(RelationCreateStruct $createStruct)
    {
        $relation = $this->mapper->createRelationFromCreateStruct($createStruct);

        $relation->id = $this->contentGateway->insertRelation($createStruct);

        return $relation;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function loadRelation(int $relationId): Relation
    {
        return $this->mapper->extractRelationFromRow(
            $this->contentGateway->loadRelation($relationId)
        );
    }

    /**
     * Removes a relation by relation Id.
     *
     * @todo Should the existence verifications happen here or is this supposed to be handled at a higher level?
     *
     * @param mixed $relationId
     * @param int $type {@see \Ibexa\Contracts\Core\Repository\Values\Content\Relation::COMMON,
     *                 \Ibexa\Contracts\Core\Repository\Values\Content\Relation::EMBED,
     *                 \Ibexa\Contracts\Core\Repository\Values\Content\Relation::LINK,
     *                 \Ibexa\Contracts\Core\Repository\Values\Content\Relation::FIELD}
     */
    public function removeRelation($relationId, $type, ?int $destinationContentId = null): void
    {
        $this->contentGateway->deleteRelation($relationId, $type);
    }

    public function countRelations(int $sourceContentId, ?int $sourceContentVersionNo = null, ?int $type = null): int
    {
        return $this->contentGateway->countRelations($sourceContentId, $sourceContentVersionNo, $type);
    }

    public function loadRelationList(
        int $sourceContentId,
        int $limit,
        int $offset = 0,
        ?int $sourceContentVersionNo = null,
        ?int $type = null
    ): array {
        return $this->mapper->extractRelationsFromRows(
            $this->contentGateway->listRelations($sourceContentId, $limit, $offset, $sourceContentVersionNo, $type)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function countReverseRelations(int $destinationContentId, ?int $type = null): int
    {
        return $this->contentGateway->countReverseRelations($destinationContentId, $type);
    }

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
    public function loadReverseRelations($destinationContentId, $type = null)
    {
        return $this->mapper->extractRelationsFromRows(
            $this->contentGateway->loadReverseRelations($destinationContentId, $type)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadReverseRelationList(
        int $destinationContentId,
        int $offset = 0,
        int $limit = -1,
        ?int $type = null
    ): array {
        return $this->mapper->extractRelationsFromRows(
            $this->contentGateway->listReverseRelations($destinationContentId, $offset, $limit, $type)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTranslationFromContent($contentId, $languageCode)
    {
        $this->fieldHandler->deleteTranslationFromContentFields(
            $contentId,
            $this->listVersions($contentId),
            $languageCode
        );
        $this->contentGateway->deleteTranslationFromContent($contentId, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTranslationFromDraft($contentId, $versionNo, $languageCode)
    {
        $versionInfo = $this->loadVersionInfo($contentId, $versionNo);

        $this->fieldHandler->deleteTranslationFromVersionFields(
            $versionInfo,
            $languageCode
        );
        $this->contentGateway->deleteTranslationFromVersion(
            $contentId,
            $versionNo,
            $languageCode
        );

        // get all [languageCode => name] entries except the removed Translation
        $names = array_filter(
            $versionInfo->names,
            static function ($lang) use ($languageCode): bool {
                return $lang !== $languageCode;
            },
            ARRAY_FILTER_USE_KEY
        );
        // set new Content name
        foreach ($names as $language => $name) {
            $this->contentGateway->setName(
                $contentId,
                $versionNo,
                $name,
                $language
            );
        }

        // reload entire Version w/o removed Translation
        return $this->load($contentId, $versionNo);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function loadVersionInfoList(array $contentIds): array
    {
        $rows = $this->contentGateway->loadVersionInfoList($contentIds);

        if (empty($rows)) {
            return [];
        }

        $mappedRows = array_map(
            static function (array $row): array {
                return [
                    'id' => $row['content_id'],
                    'version' => $row['content_version_version'],
                ];
            },
            $rows,
        );

        $versionInfoList = $this->mapper->extractVersionInfoListFromRows(
            $rows,
            $this->contentGateway->loadVersionedNameData($mappedRows)
        );

        $versionInfoListById = [];
        foreach ($versionInfoList as $versionInfo) {
            $versionInfoListById[$versionInfo->contentInfo->id] = $versionInfo;
        }

        return $versionInfoListById;
    }
}

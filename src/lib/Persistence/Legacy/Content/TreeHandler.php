<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Mapper as LocationMapper;
use Ibexa\Core\Persistence\Legacy\Content\Mapper as ContentMapper;

/**
 * The TreeHandler is an intersect between ContentHandler and LocationHandler,
 * used to avoid circular dependency between them.
 */
class TreeHandler
{
    /**
     * Gateway for handling location data.
     */
    protected LocationGateway $locationGateway;

    /**
     * Location Mapper.
     */
    protected LocationMapper $locationMapper;

    /**
     * Content gateway.
     */
    protected ContentGateway $contentGateway;

    /**
     * Content handler.
     */
    protected ContentMapper $contentMapper;

    /**
     * FieldHandler.
     */
    protected FieldHandler $fieldHandler;

    /**
     * @param \Ibexa\Core\Persistence\Legacy\Content\Location\Gateway $locationGateway
     * @param \Ibexa\Core\Persistence\Legacy\Content\Location\Mapper $locationMapper
     * @param \Ibexa\Core\Persistence\Legacy\Content\Gateway $contentGateway
     * @param \Ibexa\Core\Persistence\Legacy\Content\Mapper $contentMapper
     * @param \Ibexa\Core\Persistence\Legacy\Content\FieldHandler $fieldHandler
     */
    public function __construct(
        LocationGateway $locationGateway,
        LocationMapper $locationMapper,
        ContentGateway $contentGateway,
        ContentMapper $contentMapper,
        FieldHandler $fieldHandler
    ) {
        $this->locationGateway = $locationGateway;
        $this->locationMapper = $locationMapper;
        $this->contentGateway = $contentGateway;
        $this->contentMapper = $contentMapper;
        $this->fieldHandler = $fieldHandler;
    }

    /**
     * Returns the metadata object for a content identified by $contentId.
     *
     * @param int|string $contentId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo
     */
    public function loadContentInfo(int $contentId)
    {
        return $this->contentMapper->extractContentInfoFromRow(
            $this->contentGateway->loadContentInfo($contentId)
        );
    }

    /**
     * Deletes raw content data.
     *
     * @param int $contentId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function removeRawContent($contentId): void
    {
        $mainLocationId = $this->loadContentInfo($contentId)->mainLocationId;
        // there can be no Locations for Draft Content items
        if (null !== $mainLocationId) {
            $this->locationGateway->removeElementFromTrash($mainLocationId);
        }

        foreach ($this->listVersions($contentId) as $versionInfo) {
            $this->fieldHandler->deleteFields($contentId, $versionInfo);
        }
        // Must be called before deleteRelations()
        $this->contentGateway->removeReverseFieldRelations($contentId);
        $this->contentGateway->deleteRelations($contentId);
        $this->contentGateway->deleteVersions($contentId);
        $this->contentGateway->deleteNames($contentId);
        $this->contentGateway->deleteContent($contentId);
    }

    /**
     * Returns the versions for $contentId.
     *
     * Result is returned with oldest version first (using version id as it has index and is auto increment).
     *
     * @param mixed $contentId
     * @param mixed|null $status Optional argument to filter versions by status, like {@see VersionInfo::STATUS_ARCHIVED}.
     * @param int $limit Limit for items returned, -1 means none.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo[]
     */
    public function listVersions(int $contentId, ?int $status = null, int $limit = -1): array
    {
        $rows = $this->contentGateway->listVersions($contentId, $status, $limit);
        if (empty($rows)) {
            return [];
        }

        $idVersionPairs = array_map(
            static function (array $row) use ($contentId): array {
                return [
                    'id' => $contentId,
                    'version' => $row['ezcontentobject_version_version'],
                ];
            },
            $rows
        );
        $nameRows = $this->contentGateway->loadVersionedNameData($idVersionPairs);

        return $this->contentMapper->extractVersionInfoListFromRows(
            $rows,
            $nameRows
        );
    }

    /**
     * Loads the data for the location identified by $locationId.
     *
     * @param int $locationId
     * @param string[]|null $translations If set, NotFound is thrown if content is not in given translation.
     * @param bool $useAlwaysAvailable Respect always available flag on content, where main language is valid translation fallback.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location
     */
    public function loadLocation(int $locationId, array $translations = null, bool $useAlwaysAvailable = true)
    {
        $data = $this->locationGateway->getBasicNodeData($locationId, $translations, $useAlwaysAvailable);

        return $this->locationMapper->createLocationFromRow($data);
    }

    /**
     * Removes all Locations under and including $locationId.
     *
     * Performs a recursive delete on the location identified by $locationId,
     * including all of its child locations. Content which is not referred to
     * by any other location is automatically removed. Content which looses its
     * main Location will get the first of its other Locations assigned as the
     * new main Location.
     *
     * @param mixed $locationId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @return bool
     */
    public function removeSubtree($locationId): void
    {
        $locationRow = $this->locationGateway->getBasicNodeData($locationId);
        $contentId = $locationRow['contentobject_id'];
        $mainLocationId = $locationRow['main_node_id'];

        $subLocations = $this->locationGateway->getChildren($locationId);
        foreach ($subLocations as $subLocation) {
            $this->removeSubtree($subLocation['node_id']);
        }

        if ($locationId == $mainLocationId) {
            if (1 == $this->locationGateway->countLocationsByContentId($contentId)) {
                $this->removeRawContent($contentId);
            } else {
                $newMainLocationRow = $this->locationGateway->getFallbackMainNodeData(
                    $contentId,
                    $locationId
                );

                $this->changeMainLocation(
                    $contentId,
                    $newMainLocationRow['node_id']
                );
            }
        }

        $this->locationGateway->removeLocation($locationId);
        $this->locationGateway->deleteNodeAssignment($contentId);
    }

    /**
     * Removes draft contents assigned to the given parent location and its descendant locations.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function deleteChildrenDrafts(int $locationId): void
    {
        $subLocations = $this->locationGateway->getChildren($locationId);
        foreach ($subLocations as $subLocation) {
            $this->deleteChildrenDrafts($subLocation['node_id']);
        }

        // Fetch child draft content ids
        $subtreeChildrenDraftIds = $this->locationGateway->getSubtreeChildrenDraftContentIds($locationId);

        foreach ($subtreeChildrenDraftIds as $contentId) {
            $this->removeRawContent($contentId);
        }
    }

    /**
     * Set section on all content objects in the subtree.
     *
     * @param mixed $locationId
     * @param mixed $sectionId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function setSectionForSubtree(int $locationId, int $sectionId): void
    {
        $nodeData = $this->locationGateway->getBasicNodeData($locationId);

        $this->locationGateway->setSectionForSubtree($nodeData['path_string'], $sectionId);
    }

    /**
     * Changes main location of content identified by given $contentId to location identified by given $locationId.
     *
     * Updates ezcontentobject_tree and eznode_assignment tables (eznode_assignment for content current version number).
     *
     * @param mixed $contentId
     * @param mixed $locationId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function changeMainLocation($contentId, $locationId): void
    {
        $parentLocationId = $this->loadLocation($locationId)->parentId;

        // Update ezcontentobject_tree and eznode_assignment tables
        $this->locationGateway->changeMainLocation(
            $contentId,
            $locationId,
            $this->loadContentInfo($contentId)->currentVersionNo,
            $parentLocationId
        );

        // Update subtree section to the one of the new main location parent location content
        $destinationContentId = $this->loadLocation($parentLocationId)->contentId;
        try {
            $sectionId = $this->loadContentInfo($destinationContentId)->sectionId;
        } catch (NotFoundException $e) {
            $sectionId = null;
        }

        if ($sectionId !== null) {
            $this->setSectionForSubtree(
                $locationId,
                $sectionId
            );
        }
    }
}

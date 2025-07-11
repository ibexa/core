<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Location;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as BaseLocationHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Trashed;
use Ibexa\Contracts\Core\Persistence\Content\Location\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\Handler as ContentHandler;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Mapper as LocationMapper;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Handler as ObjectStateHandler;
use Ibexa\Core\Persistence\Legacy\Content\TreeHandler;

/**
 * The Location Handler interface defines operations on Location elements in the storage engine.
 */
class Handler implements BaseLocationHandler
{
    /**
     * Gateway for handling location data.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Location\Gateway
     */
    protected $locationGateway;

    /**
     * Location locationMapper.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Location\Mapper
     */
    protected $locationMapper;

    /**
     * Content handler.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Handler
     */
    protected $contentHandler;

    /**
     * Object state handler.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\ObjectState\Handler
     */
    protected $objectStateHandler;

    /**
     * Tree handler.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\TreeHandler
     */
    protected $treeHandler;

    public function __construct(
        LocationGateway $locationGateway,
        LocationMapper $locationMapper,
        ContentHandler $contentHandler,
        ObjectStateHandler $objectStateHandler,
        TreeHandler $treeHandler
    ) {
        $this->locationGateway = $locationGateway;
        $this->locationMapper = $locationMapper;
        $this->contentHandler = $contentHandler;
        $this->objectStateHandler = $objectStateHandler;
        $this->treeHandler = $treeHandler;
    }

    /**
     * Returns parent path string for a path string.
     *
     * @param string $pathString
     *
     * @return string
     */
    protected function getParentPathString($pathString): string
    {
        return implode('/', array_slice(explode('/', $pathString), 0, -2)) . '/';
    }

    /**
     * {@inheritdoc}
     */
    public function load($locationId, array $translations = null, bool $useAlwaysAvailable = true)
    {
        return $this->treeHandler->loadLocation($locationId, $translations, $useAlwaysAvailable);
    }

    /**
     * {@inheritdoc}
     */
    public function loadList(array $locationIds, array $translations = null, bool $useAlwaysAvailable = true): iterable
    {
        $list = $this->locationGateway->getNodeDataList($locationIds, $translations, $useAlwaysAvailable);

        $locations = [];
        foreach ($list as $row) {
            $id = (int)$row['node_id'];
            if (!isset($locations[$id])) {
                $locations[$id] = $this->locationMapper->createLocationFromRow($row);
            }
        }

        return $locations;
    }

    /**
     * Loads the subtree ids of the location identified by $locationId.
     *
     * @param int $locationId
     *
     * @return array Location ids are in the index, Content ids in the value.
     */
    public function loadSubtreeIds($locationId)
    {
        return $this->locationGateway->getSubtreeNodeIdToContentIdMap($locationId);
    }

    /**
     * {@inheritdoc}
     */
    public function loadByRemoteId($remoteId, array $translations = null, bool $useAlwaysAvailable = true)
    {
        $data = $this->locationGateway->getBasicNodeDataByRemoteId($remoteId, $translations, $useAlwaysAvailable);

        return $this->locationMapper->createLocationFromRow($data);
    }

    /**
     * Loads all locations for $contentId, optionally limited to a sub tree
     * identified by $rootLocationId.
     *
     * @param int $contentId
     * @param int $rootLocationId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location[]
     */
    public function loadLocationsByContent($contentId, $rootLocationId = null)
    {
        $rows = $this->locationGateway->loadLocationDataByContent($contentId, $rootLocationId);

        return $this->locationMapper->createLocationsFromRows($rows);
    }

    /**
     * {@inheritdoc}
     */
    public function loadLocationsByTrashContent(int $contentId, ?int $rootLocationId = null): array
    {
        $rows = $this->locationGateway->loadLocationDataByTrashContent($contentId, $rootLocationId);

        return $this->locationMapper->createLocationsFromRows($rows, '', new Trashed());
    }

    public function loadParentLocationsForDraftContent($contentId)
    {
        $rows = $this->locationGateway->loadParentLocationsDataForDraftContent($contentId);

        return $this->locationMapper->createLocationsFromRows($rows);
    }

    /**
     * Returns an array of default content states with content state group id as key.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState[]
     */
    protected function getDefaultContentStates()
    {
        $defaultObjectStatesMap = [];

        foreach ($this->objectStateHandler->loadAllGroups() as $objectStateGroup) {
            foreach ($this->objectStateHandler->loadObjectStates($objectStateGroup->id) as $objectState) {
                // Only register the first object state which is the default one.
                $defaultObjectStatesMap[$objectStateGroup->id] = $objectState;
                break;
            }
        }

        return $defaultObjectStatesMap;
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content $content
     * @param \Ibexa\Contracts\Core\Persistence\Content\ObjectState[] $contentStates
     */
    protected function setContentStates(Content $content, array $contentStates)
    {
        foreach ($contentStates as $contentStateGroupId => $contentState) {
            $this->objectStateHandler->setContentState(
                $content->versionInfo->contentInfo->id,
                $contentStateGroupId,
                $contentState->id
            );
        }
    }

    /**
     * Copy location object identified by $sourceId, into destination identified by $destinationParentId.
     *
     * Performs a deep copy of the location identified by $sourceId and all of
     * its child locations, copying the most recent published content object
     * for each location to a new content object without any additional version
     * information. Relations are not copied. URLs are not touched at all.
     *
     * @todo Either move to async/batch or find ways toward optimizing away operations per object.
     * @todo Optionally retain dates and set creator
     *
     * @param mixed $sourceId
     * @param mixed $destinationParentId
     * @param int|null $newOwnerId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location the newly created Location.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function copySubtree($sourceId, $destinationParentId, $newOwnerId = null)
    {
        $children = $this->locationGateway->getSubtreeContent($sourceId);
        $destinationParentData = $this->locationGateway->getBasicNodeData($destinationParentId);
        $defaultObjectStates = $this->getDefaultContentStates();
        $contentMap = [];
        $locationMap = [
            $children[0]['parent_node_id'] => [
                'id' => $destinationParentId,
                'hidden' => (bool)$destinationParentData['is_hidden'],
                'invisible' => (bool)$destinationParentData['is_invisible'],
                'path_identification_string' => $destinationParentData['path_identification_string'],
            ],
        ];

        $locations = [];
        foreach ($children as $child) {
            $locations[$child['contentobject_id']][$child['node_id']] = true;
        }

        $time = time();
        $mainLocations = [];
        $mainLocationsUpdate = [];
        foreach ($children as $index => $child) {
            // Copy content
            if (!isset($contentMap[$child['contentobject_id']])) {
                $content = $this->contentHandler->copy(
                    (int)$child['contentobject_id'],
                    (int)$child['contentobject_version'],
                    $newOwnerId
                );

                $this->setContentStates($content, $defaultObjectStates);

                $content = $this->contentHandler->publish(
                    $content->versionInfo->contentInfo->id,
                    $content->versionInfo->contentInfo->currentVersionNo,
                    new MetadataUpdateStruct(
                        [
                            'publicationDate' => $time,
                            'modificationDate' => $time,
                        ]
                    )
                );

                $contentMap[$child['contentobject_id']] = $content->versionInfo->contentInfo->id;
            }

            $createStruct = $this->locationMapper->getLocationCreateStruct($child);
            $createStruct->contentId = $contentMap[$child['contentobject_id']];
            $parentData = $locationMap[$child['parent_node_id']];
            $createStruct->parentId = $parentData['id'];
            $createStruct->invisible = $createStruct->invisible
                || $createStruct->hidden
                || $parentData['hidden']
                || $parentData['invisible'];

            // Use content main location if already set, otherwise create location as main
            if (isset($mainLocations[$child['contentobject_id']])) {
                $createStruct->mainLocationId = $locationMap[$mainLocations[$child['contentobject_id']]]['id'];
            } else {
                $createStruct->mainLocationId = true;
                $mainLocations[$child['contentobject_id']] = $child['node_id'];

                // If needed mark for update
                if (
                    isset($locations[$child['contentobject_id']][$child['main_node_id']]) &&
                    count($locations[$child['contentobject_id']]) > 1 &&
                    $child['node_id'] !== $child['main_node_id']
                ) {
                    $mainLocationsUpdate[$child['contentobject_id']] = $child['main_node_id'];
                }
            }

            $newLocation = $this->create($createStruct);

            $locationMap[$child['node_id']] = [
                'id' => $newLocation->id,
                'hidden' => $newLocation->hidden,
                'invisible' => $newLocation->invisible,
            ];
            if ($index === 0) {
                $copiedSubtreeRootLocation = $newLocation;
            }
        }

        // Update main locations
        foreach ($mainLocationsUpdate as $contentId => $mainLocationId) {
            $this->changeMainLocation(
                $contentMap[$contentId],
                $locationMap[$mainLocationId]['id']
            );
        }

        $destinationParentSectionId = $this->getSectionId($destinationParentId);

        // potentially it may occur that the destination Location doesn't have a section (like in Location ID = 1),
        // therefore assigning any or empty section will be invalid here
        if ($destinationParentSectionId !== null) {
            $this->updateSubtreeSectionIfNecessary($copiedSubtreeRootLocation, $destinationParentSectionId);
        }

        return $copiedSubtreeRootLocation;
    }

    public function getSubtreeSize(string $path): int
    {
        return $this->locationGateway->getSubtreeSize($path);
    }

    /**
     * Retrieves section ID of the location's content.
     *
     * @param int $locationId
     *
     * @return int|null
     */
    private function getSectionId($locationId)
    {
        $location = $this->load($locationId);

        try {
            $locationContentInfo = $this->contentHandler->loadContentInfo($location->contentId);

            return $locationContentInfo->sectionId;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * If the location is the main location for its content, updates subtree section.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location $location
     * @param int $sectionId
     */
    private function updateSubtreeSectionIfNecessary(Location $location, $sectionId)
    {
        if ($this->isMainLocation($location)) {
            $this->setSectionForSubtree($location->id, $sectionId);
        }
    }

    /**
     * Checks if the location is the main location for its content.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location $location
     *
     * @return bool
     */
    private function isMainLocation(Location $location): bool
    {
        $locationContentInfo = $this->contentHandler->loadContentInfo($location->contentId);

        return $locationContentInfo->mainLocationId === $location->id;
    }

    /**
     * Moves location identified by $sourceId into new parent identified by $destinationParentId.
     *
     * Performs a full move of the location identified by $sourceId to a new
     * destination, identified by $destinationParentId. Relations do not need
     * to be updated, since they refer to Content. URLs are not touched.
     *
     * @param mixed $sourceId
     * @param mixed $destinationParentId
     *
     * @return bool
     */
    public function move($sourceId, $destinationParentId)
    {
        $sourceNodeData = $this->locationGateway->getBasicNodeData($sourceId);
        $destinationNodeData = $this->locationGateway->getBasicNodeData($destinationParentId);

        $this->locationGateway->moveSubtreeNodes(
            $sourceNodeData,
            $destinationNodeData
        );

        $this->locationGateway->updateNodeAssignment(
            $sourceNodeData['contentobject_id'],
            $sourceNodeData['parent_node_id'],
            $destinationParentId,
            Gateway::NODE_ASSIGNMENT_OP_CODE_MOVE
        );

        $sourceLocation = $this->load($sourceId);
        $destinationParentSectionId = $this->getSectionId($destinationParentId);

        // potentially it may occur that the destination Location doesn't have a section (like in Location ID = 1),
        // therefore assigning any or empty section will be invalid here
        if ($destinationParentSectionId !== null) {
            $this->updateSubtreeSectionIfNecessary($sourceLocation, $destinationParentSectionId);
        }
    }

    /**
     * Sets a location to be hidden, and it self + all children to invisible.
     *
     * @param mixed $id Location ID
     */
    public function hide($id)
    {
        $sourceNodeData = $this->locationGateway->getBasicNodeData($id);

        $this->locationGateway->hideSubtree($sourceNodeData['path_string']);
    }

    /**
     * Sets a location to be unhidden, and self + children to visible unless a parent is hiding the tree.
     * If not make sure only children down to first hidden node is marked visible.
     *
     * @param mixed $id
     */
    public function unHide($id)
    {
        $sourceNodeData = $this->locationGateway->getBasicNodeData($id);

        $this->locationGateway->unhideSubtree($sourceNodeData['path_string']);
    }

    /**
     * Sets a location + all children to invisible.
     *
     * @param int $id Location ID
     */
    public function setInvisible(int $id): void
    {
        $sourceNodeData = $this->locationGateway->getBasicNodeData($id);

        $this->locationGateway->setNodeWithChildrenInvisible($sourceNodeData['path_string']);
    }

    /**
     * Sets a location + all children to visible.
     *
     * @param int $id Location ID
     */
    public function setVisible(int $id): void
    {
        $sourceNodeData = $this->locationGateway->getBasicNodeData($id);

        $this->locationGateway->setNodeWithChildrenVisible($sourceNodeData['path_string']);
    }

    /**
     * Swaps the content object being pointed to by a location object.
     *
     * Make the location identified by $locationId1 refer to the Content
     * referred to by $locationId2 and vice versa.
     *
     * @param mixed $locationId1
     * @param mixed $locationId2
     *
     * @return bool
     */
    public function swap($locationId1, $locationId2)
    {
        $this->locationGateway->swap($locationId1, $locationId2);
    }

    /**
     * Updates an existing location.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Location\UpdateStruct $location
     * @param int $locationId
     */
    public function update(UpdateStruct $location, $locationId)
    {
        $this->locationGateway->update($location, $locationId);
    }

    public function create(CreateStruct $createStruct)
    {
        $parentNodeData = $this->locationGateway->getBasicNodeData($createStruct->parentId);
        $spiLocation = $this->locationGateway->create($createStruct, $parentNodeData);
        $this->locationGateway->createNodeAssignment(
            $createStruct,
            $parentNodeData['node_id'],
            LocationGateway::NODE_ASSIGNMENT_OP_CODE_CREATE_NOP
        );

        return $spiLocation;
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
     * @return bool
     */
    public function removeSubtree($locationId)
    {
        $this->treeHandler->removeSubtree($locationId);
    }

    public function deleteChildrenDrafts(int $locationId): void
    {
        $this->treeHandler->deleteChildrenDrafts($locationId);
    }

    /**
     * Set section on all content objects in the subtree.
     *
     * @param mixed $locationId
     * @param mixed $sectionId
     */
    public function setSectionForSubtree($locationId, $sectionId)
    {
        $this->treeHandler->setSectionForSubtree($locationId, $sectionId);
    }

    /**
     * Changes main location of content identified by given $contentId to location identified by given $locationId.
     *
     * Updates ibexa_content_tree and ibexa_node_assignment tables (ibexa_node_assignment for content current version number).
     *
     * @param mixed $contentId
     * @param mixed $locationId
     */
    public function changeMainLocation($contentId, $locationId)
    {
        $this->treeHandler->changeMainLocation($contentId, $locationId);
    }

    /**
     * Get the total number of all existing Locations. Can be combined with loadAllLocations.
     *
     * @return int
     */
    public function countAllLocations()
    {
        return $this->locationGateway->countAllLocations();
    }

    /**
     * Bulk-load all existing Locations, constrained by $limit and $offset to paginate results.
     *
     * @param int $offset
     * @param int $limit
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location[]
     */
    public function loadAllLocations($offset, $limit)
    {
        $rows = $this->locationGateway->loadAllLocationsData($offset, $limit);

        return $this->locationMapper->createLocationsFromRows($rows);
    }

    public function countLocationsByContent(int $contentId): int
    {
        return $this->locationGateway->countLocationsByContentId($contentId);
    }
}

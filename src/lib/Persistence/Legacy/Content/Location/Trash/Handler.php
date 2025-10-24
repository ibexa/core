<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Location\Trash;

use Ibexa\Contracts\Core\Persistence\Content\Location\Trash\Handler as BaseTrashHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Trash\TrashResult;
use Ibexa\Contracts\Core\Persistence\Content\Location\Trashed;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResultList;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\Handler as ContentHandler;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Handler as LocationHandler;
use Ibexa\Core\Persistence\Legacy\Content\Location\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\Location\Mapper as LocationMapper;

/**
 * The Location Handler interface defines operations on Location elements in the storage engine.
 */
class Handler implements BaseTrashHandler
{
    private const EMPTY_TRASH_BULK_SIZE = 100;

    /**
     * Location handler.
     *
     * @var LocationHandler
     */
    protected $locationHandler;

    /**
     * Gateway for handling location data.
     *
     * @var Gateway
     */
    protected $locationGateway;

    /**
     * Mapper for handling location data.
     *
     * @var Mapper
     */
    protected $locationMapper;

    /**
     * Content handler.
     *
     * @var ContentHandler
     */
    protected $contentHandler;

    public function __construct(
        LocationHandler $locationHandler,
        LocationGateway $locationGateway,
        LocationMapper $locationMapper,
        ContentHandler $contentHandler
    ) {
        $this->locationHandler = $locationHandler;
        $this->locationGateway = $locationGateway;
        $this->locationMapper = $locationMapper;
        $this->contentHandler = $contentHandler;
    }

    /**
     * Loads the data for the trashed location identified by $id.
     * $id is the same as original location (which has been previously trashed).
     *
     * @param int $id
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @return Trashed
     */
    public function loadTrashItem($id)
    {
        $data = $this->locationGateway->loadTrashByLocation($id);

        return $this->locationMapper->createLocationFromRow($data, null, new Trashed());
    }

    /**
     * Sends a subtree starting to $locationId to the trash
     * and returns a Trashed object corresponding to $locationId.
     *
     * Moves all locations in the subtree to the Trash. The associated content
     * objects are left untouched.
     *
     * @param mixed $locationId
     *
     * @todo Handle field types actions
     *
     * @return Trashed|null null if location was deleted, otherwise Trashed object
     */
    public function trashSubtree($locationId)
    {
        $locationRows = $this->locationGateway->getSubtreeContent($locationId);
        $isLocationRemoved = false;
        $removedLocationsContentMap = [];

        foreach ($locationRows as $locationRow) {
            if ($this->locationGateway->countLocationsByContentId($locationRow['contentobject_id']) == 1) {
                $this->locationGateway->trashLocation($locationRow['node_id']);
                $removedLocationsContentMap[(int)$locationRow['node_id']] = (int)$locationRow['contentobject_id'];
            } else {
                if ($locationRow['node_id'] == $locationId) {
                    $isLocationRemoved = true;
                }
                $this->locationGateway->removeLocation($locationRow['node_id']);

                if ($locationRow['node_id'] == $locationRow['main_node_id']) {
                    $newMainLocationRow = $this->locationGateway->getFallbackMainNodeData(
                        $locationRow['contentobject_id'],
                        $locationRow['node_id']
                    );

                    $this->locationHandler->changeMainLocation(
                        $locationRow['contentobject_id'],
                        $newMainLocationRow['node_id']
                    );
                }
            }
        }

        if ($isLocationRemoved === true) {
            return null;
        }

        $trashItem = $this->loadTrashItem($locationId);
        $trashItem->removedLocationContentIdMap = $removedLocationsContentMap;

        return $trashItem;
    }

    /**
     * Returns a trashed location to normal state.
     *
     * Recreates the originally trashed location in the new position.
     * If this is not possible (because the old location does not exist any more),
     * a ParentNotFound exception is thrown.
     *
     * Returns newly restored location Id.
     *
     * @param mixed $trashedId
     * @param mixed $newParentId
     *
     * @return int Newly restored location id
     *
     * @throws NotFoundException If $newParentId is invalid
     *
     * @todo Handle field types actions
     */
    public function recover(
        $trashedId,
        $newParentId
    ) {
        return $this->locationGateway->untrashLocation($trashedId, $newParentId)->id;
    }

    /**
     * {@inheritdoc}.
     */
    public function findTrashItems(
        ?CriterionInterface $criterion = null,
        $offset = 0,
        $limit = null,
        ?array $sort = null
    ) {
        $totalCount = $this->locationGateway->countTrashed($criterion);
        if ($totalCount === 0) {
            return new TrashResult();
        }

        $rows = $this->locationGateway->listTrashed($offset, $limit, $sort, $criterion);
        $items = [];

        foreach ($rows as $row) {
            $items[] = $this->locationMapper->createLocationFromRow($row, null, new Trashed());
        }

        return new TrashResult([
            'items' => $items,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function emptyTrash()
    {
        $resultList = new TrashItemDeleteResultList();
        do {
            $trashedItems = $this->findTrashItems(null, 0, self::EMPTY_TRASH_BULK_SIZE);
            foreach ($trashedItems as $item) {
                $resultList->items[] = $this->delete($item);
            }
        } while ($trashedItems->totalCount > self::EMPTY_TRASH_BULK_SIZE);

        $this->locationGateway->cleanupTrash();

        return $resultList;
    }

    /**
     * Removes a trashed location identified by $trashedLocationId from trash
     * Associated content has to be deleted.
     *
     * @param int $trashedId
     *
     * @return TrashItemDeleteResult
     */
    public function deleteTrashItem($trashedId)
    {
        return $this->delete($this->loadTrashItem($trashedId));
    }

    /**
     * Triggers delete operations for $trashItem.
     * If there is no more locations for corresponding content, then it will be deleted as well.
     *
     * @param Trashed $trashItem
     *
     * @return TrashItemDeleteResult
     */
    protected function delete(Trashed $trashItem)
    {
        $result = new TrashItemDeleteResult();
        $result->trashItemId = $trashItem->id;
        $result->contentId = $trashItem->contentId;

        $reverseRelations = $this->contentHandler->loadReverseRelations($trashItem->contentId);

        $this->locationGateway->removeElementFromTrash($trashItem->id);

        if ($this->locationGateway->countLocationsByContentId($trashItem->contentId) < 1) {
            $this->contentHandler->deleteContent($trashItem->contentId);
            $result->contentRemoved = true;
            $result->reverseRelationContentIds = array_column($reverseRelations, 'sourceContentId');
        }

        return $result;
    }
}

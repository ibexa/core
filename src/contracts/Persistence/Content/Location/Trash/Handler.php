<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content\Location\Trash;

use Ibexa\Contracts\Core\Persistence\Content\Location\Trashed;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResultList;

/**
 * The Trash Handler interface defines operations on Location elements in the storage engine.
 */
interface Handler
{
    /**
     * Loads the data for the trashed location identified by $id.
     * $id is the same as original location (which has been previously trashed).
     *
     * @param int $id
     *
     * @throws NotFoundException
     *
     * @return Trashed
     */
    public function loadTrashItem($id);

    /**
     * Sends a subtree starting to $locationId to the trash
     * and returns a Trashed object corresponding to $locationId.
     *
     * Moves all locations in the subtree to the Trash. The associated content
     * objects are left untouched.
     *
     * @param mixed $locationId
     *
     * @return Trashed|null null if location was deleted, otherwise Trashed object
     */
    public function trashSubtree($locationId);

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
     * @throws NotFoundException If $newParentId is invalid
     *
     * @return int Newly restored location id
     */
    public function recover(
        $trashedId,
        $newParentId
    );

    /**
     * Returns all trashed locations satisfying the $criterion (if provided), sorted with $sort (if any).
     *
     * If no criterion is provided (null), no filter is applied.
     *
     * TrashResult->totalCount will ignore limit and offset and representing the total amount of trashed items
     * matching the criterion.
     *
     * @param int $offset Offset to start listing from, 0 by default
     * @param int $limit Limit for the listing. Null by default (no limit)
     * @param SortClause[] $sort
     *
     * @return Trashed[]|TrashResult
     */
    public function findTrashItems(
        ?CriterionInterface $criterion = null,
        $offset = 0,
        $limit = null,
        ?array $sort = null
    );

    /**
     * Empties the trash
     * Everything contained in the trash must be removed.
     *
     * @return TrashItemDeleteResultList
     */
    public function emptyTrash();

    /**
     * Removes a trashed location identified by $trashedLocationId from trash
     * Associated content has to be deleted.
     *
     * @param int $trashedId
     *
     * @return TrashItemDeleteResult
     */
    public function deleteTrashItem($trashedId);
}

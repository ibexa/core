<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Content\Location;

use Ibexa\Contracts\Core\Persistence\Content\Location;

/**
 * The Location Handler interface defines operations on Location elements in the storage engine.
 */
interface Handler
{
    /**
     * Loads the data for the location identified by $locationId.
     *
     * @param string[]|null $translations If set, NotFound is thrown if content is not in given translation.
     * @param bool $useAlwaysAvailable Respect always available flag on content, where main language is valid translation fallback.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function load(int $locationId, ?array $translations = null, bool $useAlwaysAvailable = true): Location;

    /**
     * Return list of unique Locations, with location id as key.
     *
     * Missing items (NotFound) will be missing from the array and not cause an exception, it's up
     * to calling logic to determine if this should cause exception or not.
     *
     * @param int[] $locationIds
     * @param string[]|null $translations If set, only locations with content in given translations are returned.
     * @param bool $useAlwaysAvailable Respect always available flag on content, where main language is valid translation fallback.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location[]|iterable
     */
    public function loadList(array $locationIds, array $translations = null, bool $useAlwaysAvailable = true): iterable;

    /**
     * Loads the subtree ids of the location identified by $locationId.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @return int[] Location ids are in the index, Content ids in the value.
     */
    public function loadSubtreeIds(int $locationId): array;

    /**
     * Loads the data for the location identified by $remoteId.
     *
     * @param string[]|null $translations If set, NotFound is thrown if content is not in given translation.
     * @param bool $useAlwaysAvailable Respect always available flag on content, where main language is valid translation fallback.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function loadByRemoteId(string $remoteId, array $translations = null, bool $useAlwaysAvailable = true): Location;

    /**
     * Loads all locations for $contentId, optionally limited to a sub tree
     * identified by $rootLocationId.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location[]
     */
    public function loadLocationsByContent(int $contentId, ?int $rootLocationId = null): array;

    /**
     * Loads all locations for $contentId in trash, optionally limited to a sub tree
     * identified by $rootLocationId.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location[]
     */
    public function loadLocationsByTrashContent(int $contentId, ?int $rootLocationId = null): array;

    /**
     * Loads all parent Locations for unpublished Content by given $contentId.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location[]
     */
    public function loadParentLocationsForDraftContent(int $contentId): array;

    /**
     * Copy location object identified by $sourceId, into destination identified by $destinationParentId.
     *
     * Performs a deep copy of the location identified by $sourceId and all of
     * its child locations, copying the most recent published content object
     * for each location to a new content object without any additional version
     * information. Relations for published version are copied. URLs are not touched at all.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If $sourceId or $destinationParentId are invalid
     */
    public function copySubtree(int $sourceId, int $destinationParentId): Location;

    public function getSubtreeSize(string $path): int;

    /**
     * Moves location identified by $sourceId into new parent identified by $destinationParentId.
     *
     * Performs a full move of the location identified by $sourceId to a new
     * destination, identified by $destinationParentId. Relations do not need
     * to be updated, since they refer to Content. URLs are not touched.
     */
    public function move(int $sourceId, int $destinationParentId): void;

    /**
     * Sets a location to be hidden, and it self + all children to invisible.
     */
    public function hide(int $id): void;

    /**
     * Sets a location to be unhidden, and self + children to visible unless a parent is hiding the tree.
     * If not make sure only children down to first hidden node is marked visible.
     */
    public function unHide(int $id): void;

    /**
     * Sets a location + all children to invisible.
     */
    public function setInvisible(int $id): void;

    /**
     * Sets a location + all children to visible.
     */
    public function setVisible(int $id): void;

    /**
     * Swaps the content object being pointed to by a location object.
     *
     * Make the location identified by $locationId1 refer to the Content
     * referred to by $locationId2 and vice versa.
     */
    public function swap(int $locationId1, int $locationId2): void;

    /**
     * Updates an existing location.
     */
    public function update(UpdateStruct $location, int $locationId): void;

    /**
     * Creates a new location rooted at $location->parentId.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if parent Location does not exist
     */
    public function create(CreateStruct $location): Location;

    /**
     * Removes all Locations under and including $locationId.
     *
     * Performs a recursive delete on the location identified by $locationId,
     * including all of its child locations. Content which is not referred to
     * by any other location is automatically removed. Content which looses its
     * main Location will get the first of its other Locations assigned as the
     * new main Location.
     */
    public function removeSubtree(int $locationId): void;

    /**
     * Removes all draft contents that have no location assigned to them under the given parent location.
     */
    public function deleteChildrenDrafts(int $locationId): void;

    /**
     * Set section on all content objects in the subtree.
     * Only main locations will be updated.
     *
     * @todo This can be confusing (regarding permissions and main/multi location).
     * So method is for the time being not in PublicAPI so people can instead
     * write scripts using their own logic against the assignSectionToContent() api.
     */
    public function setSectionForSubtree(int $locationId, int $sectionId): void;

    /**
     * Changes main location of content identified by given $contentId to location identified by given $locationId.
     */
    public function changeMainLocation(int $contentId, int $locationId): void;

    /**
     * Get the total number of all existing Locations. Can be combined with loadAllLocations.
     */
    public function countAllLocations(): int;

    /**
     * Bulk-load all existing Locations, constrained by $limit and $offset to paginate results.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Location[]
     */
    public function loadAllLocations(int $offset, int $limit): array;

    /**
     * Counts locations for a given content represented by its id.
     */
    public function countLocationsByContent(int $contentId): int;
}

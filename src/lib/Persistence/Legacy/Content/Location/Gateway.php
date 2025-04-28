<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Location;

use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Location\UpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;

/**
 * Base class for location gateways.
 *
 * @internal For internal use by Persistence Handlers.
 */
abstract class Gateway
{
    public const string TRASH_TABLE = 'ezcontentobject_trash';

    /**
     * Constants for node assignment op codes.
     */
    public const int NODE_ASSIGNMENT_OP_CODE_NOP = 0;
    public const int NODE_ASSIGNMENT_OP_CODE_EXECUTE = 1;
    public const int NODE_ASSIGNMENT_OP_CODE_CREATE_NOP = 2;
    public const int NODE_ASSIGNMENT_OP_CODE_CREATE = 3;
    public const int NODE_ASSIGNMENT_OP_CODE_MOVE_NOP = 4;
    public const int NODE_ASSIGNMENT_OP_CODE_MOVE = 5;
    public const int NODE_ASSIGNMENT_OP_CODE_REMOVE_NOP = 6;
    public const int NODE_ASSIGNMENT_OP_CODE_REMOVE = 7;
    public const int NODE_ASSIGNMENT_OP_CODE_SET_NOP = 8;
    public const int NODE_ASSIGNMENT_OP_CODE_SET = 9;

    public const string CONTENT_TREE_TABLE = 'ezcontentobject_tree';
    public const string CONTENT_TREE_SEQ = 'ezcontentobject_tree_node_id_seq';

    /**
     * Returns an array with basic node data.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @param string[]|null $translations
     * @param bool $useAlwaysAvailable Respect always available flag on content when filtering on $translations.
     *
     * @return array<string, mixed>
     */
    abstract public function getBasicNodeData(
        int $nodeId,
        ?array $translations = null,
        bool $useAlwaysAvailable = true
    ): array;

    /**
     * Returns an array with node data for several locations.
     *
     * @param int[] $locationIds
     * @param string[]|null $translations
     * @param bool $useAlwaysAvailable Respect always available flag on content when filtering on $translations.
     *
     * @phpstan-return list<array<string,mixed>>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function getNodeDataList(
        array $locationIds,
        array $translations = null,
        bool $useAlwaysAvailable = true
    ): iterable;

    /**
     * Returns an array with basic node data for the node with $remoteId.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @param string[]|null $translations
     * @param bool $useAlwaysAvailable Respect always available flag on content when filtering on $translations.
     *
     * @return array<string, mixed>
     */
    abstract public function getBasicNodeDataByRemoteId(
        string $remoteId,
        ?array $translations = null,
        bool $useAlwaysAvailable = true
    ): array;

    /**
     * Loads data for all Locations for $contentId, optionally only in the
     * subtree starting at $rootLocationId.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadLocationDataByContent(
        int $contentId,
        ?int $rootLocationId = null
    ): array;

    /**
     * Loads data for all Locations for $contentId in trash, optionally only in the
     * subtree starting at $rootLocationId.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadLocationDataByTrashContent(int $contentId, ?int $rootLocationId = null): array;

    /**
     * Loads data for all parent Locations for unpublished Content by given $contentId.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadParentLocationsDataForDraftContent(int $contentId): array;

    /**
     * Find all content in the given subtree.
     *
     * @phpstan-return array<int, array<string, mixed>>
     */
    abstract public function getSubtreeContent(int $sourceId): array;

    /**
     * Find all content in the given subtree, but return only node ID to content ID map.
     *
     * @return array<int, int>
     */
    abstract public function getSubtreeNodeIdToContentIdMap(int $sourceId): array;

    /**
     * Finds draft contents created under the given parent location.
     *
     * @return array<int>
     */
    abstract public function getSubtreeChildrenDraftContentIds(int $sourceId): array;

    abstract public function getSubtreeSize(string $path): int;

    /**
     * Returns data for the first level children of the location identified by given $locationId.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function getChildren(int $locationId): array;

    /**
     * Update path strings to move nodes in the ezcontentobject_tree table.
     *
     * This query can likely be optimized to use some more advanced string
     * operations, which then depend on the respective database.
     *
     * @todo optimize
     *
     * @param array<string, mixed> $fromPathString
     * @param array<string, mixed> $toPathString
     */
    abstract public function moveSubtreeNodes(array $fromPathString, array $toPathString): void;

    /**
     * Update node assignment table.
     */
    abstract public function updateNodeAssignment(
        int $contentObjectId,
        int $oldParent,
        int $newParent,
        int $opcode
    ): void;

    /**
     * Create locations from node assignments.
     *
     * Convert existing node assignments into real locations.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if parent Location does not exist
     */
    abstract public function createLocationsFromNodeAssignments(
        int $contentId,
        int $versionNo
    ): void;

    /**
     * Updates all Locations of content identified with $contentId with $versionNo.
     */
    abstract public function updateLocationsContentVersionNo(int $contentId, int $versionNo): void;

    /**
     * Sets a location to be hidden, and itself + all children to invisible.
     */
    abstract public function hideSubtree(string $pathString): void;

    /**
     * Sets a location to be unhidden, and self + children to visible unless a parent is hiding the tree.
     * If not make sure only children down to first hidden node is marked visible.
     */
    abstract public function unHideSubtree(string $pathString): void;

    abstract public function setNodeWithChildrenInvisible(string $pathString): void;

    /**
     * Mark a Location and its children as visible unless a parent is hiding the tree.
     */
    abstract public function setNodeWithChildrenVisible(string $pathString): void;

    abstract public function setNodeHidden(string $pathString): void;

    /**
     * Mark a Location as not hidden.
     */
    abstract public function setNodeUnhidden(string $pathString): void;

    /**
     * Swaps the content object being pointed to by a location object.
     *
     * Make the location identified by $locationId1 refer to the Content
     * referred to by $locationId2 and vice versa.
     */
    abstract public function swap(int $locationId1, int $locationId2): bool;

    /**
     * Creates a new location in given $parentNode.
     *
     * @param array<string, mixed> $parentNode parent node raw data
     */
    abstract public function create(CreateStruct $createStruct, array $parentNode): Location;

    /**
     * Create an entry in the node assignment table.
     */
    abstract public function createNodeAssignment(
        CreateStruct $createStruct,
        int $parentNodeId,
        int $type = self::NODE_ASSIGNMENT_OP_CODE_CREATE_NOP
    ): void;

    /**
     * Deletes node assignment for given $contentId and $versionNo.
     *
     * If $versionNo is not passed all node assignments for given $contentId are deleted
     */
    abstract public function deleteNodeAssignment(int $contentId, ?int $versionNo = null): void;

    /**
     * Updates an existing location.
     *
     * Will not throw anything if location id is invalid or no entries are affected.
     */
    abstract public function update(UpdateStruct $location, int $locationId): void;

    /**
     * Updates path identification string for given $locationId.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function updatePathIdentificationString(
        int $locationId,
        int $parentLocationId,
        string $text
    ): void;

    /**
     * Deletes ezcontentobject_tree row for given $locationId (node_id).
     */
    abstract public function removeLocation(int $locationId): void;

    /**
     * Return data of the next in line node to be set as a new main node.
     *
     * This returns lowest node id for content identified by $contentId, and not of
     * the node identified by given $locationId (current main node).
     * Assumes that content has more than one location.
     *
     * @return array<string, mixed>
     */
    abstract public function getFallbackMainNodeData(int $contentId, int $locationId): array;

    /**
     * Sends a single location identified by given $locationId to the trash.
     *
     * The associated content object is left untouched.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function trashLocation(int $locationId): void;

    /**
     * Returns a trashed location to normal state.
     *
     * Recreates the originally trashed location in the new position. If no new
     * position has been specified, it will be tried to re-create the location
     * at the old position. If this is not possible ( because the old location
     * does not exist anymore) and exception is thrown.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function untrashLocation(int $locationId, ?int $newParentId = null): Location;

    /**
     * Loads trash data specified by location ID.
     *
     * @return array<string, mixed>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    abstract public function loadTrashByLocation(int $locationId): array;

    /**
     * Removes every entry in the trash.
     * Will NOT remove associated content objects nor attributes.
     *
     * Basically truncates ezcontentobject_trash table.
     */
    abstract public function cleanupTrash(): void;

    /**
     * List trashed items.
     *
     * @param int $offset
     * @param int|null $limit
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[] $sort
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface|null $criterion
     *
     * @return list<array<string, mixed>> entries from ezcontentobject_trash.
     */
    abstract public function listTrashed(
        int $offset,
        ?int $limit,
        array $sort = null,
        ?CriterionInterface $criterion = null
    ): array;

    /**
     * Count trashed items.
     */
    abstract public function countTrashed(?CriterionInterface $criterion = null): int;

    /**
     * Removes trashed element identified by $id from trash.
     * Will NOT remove associated content object nor attributes.
     *
     * @param int $id The trashed location Id
     */
    abstract public function removeElementFromTrash(int $id): void;

    /**
     * Set section on all content objects in the subtree.
     */
    abstract public function setSectionForSubtree(string $pathString, int $sectionId): bool;

    /**
     * Returns how many locations given content object identified by $contentId has.
     */
    abstract public function countLocationsByContentId(int $contentId): int;

    /**
     * Changes main location of content identified by given $contentId to location identified by given $locationId.
     *
     * Updates ezcontentobject_tree table for the given $contentId and eznode_assignment table for the given
     * $contentId, $parentLocationId and $versionNo
     *
     * @param int $versionNo version number, needed to update eznode_assignment table
     * @param int $parentLocationId parent location of location identified by $locationId, needed to update
     *        eznode_assignment table
     */
    abstract public function changeMainLocation(
        int $contentId,
        int $locationId,
        int $versionNo,
        int $parentLocationId
    ): void;

    /**
     * Get the total number of all Locations, except the Root node.
     *
     * @see loadAllLocationsData
     *
     * @return int
     */
    abstract public function countAllLocations(): int;

    /**
     * Load data of every Location, except the Root node.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadAllLocationsData(int $offset, int $limit): array;
}

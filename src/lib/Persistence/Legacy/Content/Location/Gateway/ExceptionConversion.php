<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Location\Gateway;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Location\UpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway;
use PDOException;

/**
 * @internal Internal exception conversion layer.
 */
final class ExceptionConversion extends Gateway
{
    /**
     * The wrapped gateway.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Location\Gateway
     */
    private $innerGateway;

    /**
     * Creates a new exception conversion gateway around $innerGateway.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\Location\Gateway $innerGateway
     */
    public function __construct(Gateway $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function getBasicNodeData(
        int $nodeId,
        array $translations = null,
        bool $useAlwaysAvailable = true
    ): array {
        try {
            return $this->innerGateway->getBasicNodeData($nodeId, $translations, $useAlwaysAvailable);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getNodeDataList(array $locationIds, array $translations = null, bool $useAlwaysAvailable = true): iterable
    {
        try {
            return $this->innerGateway->getNodeDataList($locationIds, $translations, $useAlwaysAvailable);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getBasicNodeDataByRemoteId(
        string $remoteId,
        array $translations = null,
        bool $useAlwaysAvailable = true
    ): array {
        try {
            return $this->innerGateway->getBasicNodeDataByRemoteId($remoteId, $translations, $useAlwaysAvailable);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadLocationDataByContent(int $contentId, ?int $rootLocationId = null): array
    {
        try {
            return $this->innerGateway->loadLocationDataByContent($contentId, $rootLocationId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadLocationDataByTrashContent(int $contentId, ?int $rootLocationId = null): array
    {
        try {
            return $this->innerGateway->loadLocationDataByTrashContent($contentId, $rootLocationId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadParentLocationsDataForDraftContent(int $contentId): array
    {
        try {
            return $this->innerGateway->loadParentLocationsDataForDraftContent($contentId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getSubtreeContent(int $sourceId): array
    {
        try {
            return $this->innerGateway->getSubtreeContent($sourceId);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getSubtreeNodeIdToContentIdMap(int $sourceId): array
    {
        try {
            return $this->innerGateway->getSubtreeNodeIdToContentIdMap($sourceId);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    /**
     * @return array<int>
     */
    public function getSubtreeChildrenDraftContentIds(int $sourceId): array
    {
        try {
            return $this->innerGateway->getSubtreeChildrenDraftContentIds($sourceId);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getSubtreeSize(string $path): int
    {
        try {
            return $this->innerGateway->getSubtreeSize($path);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getChildren(int $locationId): array
    {
        try {
            return $this->innerGateway->getChildren($locationId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function moveSubtreeNodes(array $fromPathString, array $toPathString): void
    {
        try {
            $this->innerGateway->moveSubtreeNodes($fromPathString, $toPathString);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function updateNodeAssignment(
        int $contentObjectId,
        int $oldParent,
        int $newParent,
        int $opcode
    ): void {
        try {
            $this->innerGateway->updateNodeAssignment($contentObjectId, $oldParent, $newParent, $opcode);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function createLocationsFromNodeAssignments(int $contentId, int $versionNo): void
    {
        try {
            $this->innerGateway->createLocationsFromNodeAssignments($contentId, $versionNo);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function updateLocationsContentVersionNo(int $contentId, int $versionNo): void
    {
        try {
            $this->innerGateway->updateLocationsContentVersionNo($contentId, $versionNo);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function hideSubtree(string $pathString): void
    {
        try {
            $this->innerGateway->hideSubtree($pathString);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function unHideSubtree(string $pathString): void
    {
        try {
            $this->innerGateway->unHideSubtree($pathString);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function setNodeWithChildrenInvisible(string $pathString): void
    {
        try {
            $this->innerGateway->setNodeWithChildrenInvisible($pathString);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function setNodeHidden(string $pathString): void
    {
        try {
            $this->innerGateway->setNodeHidden($pathString);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function setNodeWithChildrenVisible(string $pathString): void
    {
        try {
            $this->innerGateway->setNodeWithChildrenVisible($pathString);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function setNodeUnhidden(string $pathString): void
    {
        try {
            $this->innerGateway->setNodeUnhidden($pathString);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function swap(int $locationId1, int $locationId2): bool
    {
        try {
            return $this->innerGateway->swap($locationId1, $locationId2);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function create(CreateStruct $createStruct, array $parentNode): Location
    {
        try {
            return $this->innerGateway->create($createStruct, $parentNode);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function createNodeAssignment(
        CreateStruct $createStruct,
        int $parentNodeId,
        int $type = self::NODE_ASSIGNMENT_OP_CODE_CREATE_NOP
    ): void {
        try {
            $this->innerGateway->createNodeAssignment($createStruct, $parentNodeId, $type);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteNodeAssignment(int $contentId, ?int $versionNo = null): void
    {
        try {
            $this->innerGateway->deleteNodeAssignment($contentId, $versionNo);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function update(UpdateStruct $location, int $locationId): void
    {
        try {
            $this->innerGateway->update($location, $locationId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function updatePathIdentificationString(
        int $locationId,
        int $parentLocationId,
        string $text
    ): void {
        try {
            $this->innerGateway->updatePathIdentificationString($locationId, $parentLocationId, $text);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function removeLocation(int $locationId): void
    {
        try {
            $this->innerGateway->removeLocation($locationId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getFallbackMainNodeData(int $contentId, int $locationId): array
    {
        try {
            return $this->innerGateway->getFallbackMainNodeData($contentId, $locationId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function trashLocation(int $locationId): void
    {
        try {
            $this->innerGateway->trashLocation($locationId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function untrashLocation(int $locationId, ?int $newParentId = null): Location
    {
        try {
            return $this->innerGateway->untrashLocation($locationId, $newParentId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadTrashByLocation(int $locationId): array
    {
        try {
            return $this->innerGateway->loadTrashByLocation($locationId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function cleanupTrash(): void
    {
        try {
            $this->innerGateway->cleanupTrash();
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function listTrashed(
        int $offset,
        ?int $limit,
        array $sort = null,
        ?CriterionInterface $criterion = null
    ): array {
        try {
            return $this->innerGateway->listTrashed($offset, $limit, $sort, $criterion);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function countTrashed(?CriterionInterface $criterion = null): int
    {
        try {
            return $this->innerGateway->countTrashed($criterion);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function removeElementFromTrash(int $id): void
    {
        try {
            $this->innerGateway->removeElementFromTrash($id);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function setSectionForSubtree(string $pathString, int $sectionId): bool
    {
        try {
            return $this->innerGateway->setSectionForSubtree($pathString, $sectionId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function countLocationsByContentId(int $contentId): int
    {
        try {
            return $this->innerGateway->countLocationsByContentId($contentId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function changeMainLocation(
        int $contentId,
        int $locationId,
        int $versionNo,
        int $parentLocationId
    ): void {
        try {
            $this->innerGateway->changeMainLocation($contentId, $locationId, $versionNo, $parentLocationId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function countAllLocations(): int
    {
        try {
            return $this->innerGateway->countAllLocations();
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadAllLocationsData(int $offset, int $limit): array
    {
        try {
            return $this->innerGateway->loadAllLocationsData($offset, $limit);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}

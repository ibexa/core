<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\Content\Location\Trash\Handler as TrashHandlerInterface;
use Ibexa\Contracts\Core\Persistence\Content\Relation;
use Ibexa\Contracts\Core\Persistence\User\RoleAssignment;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;

class TrashHandler extends AbstractHandler implements TrashHandlerInterface
{
    private const EMPTY_TRASH_BULK_SIZE = 100;
    private const CONTENT_IDENTIFIER = 'content';
    private const LOCATION_PATH_IDENTIFIER = 'location_path';
    private const ROLE_ASSIGNMENT_ROLE_LIST_IDENTIFIER = 'role_assignment_role_list';

    /**
     * {@inheritdoc}
     */
    public function loadTrashItem($id)
    {
        $this->logger->logCall(__METHOD__, ['id' => $id]);

        return $this->persistenceHandler->trashHandler()->loadTrashItem($id);
    }

    /**
     * {@inheritdoc}
     */
    public function trashSubtree($locationId)
    {
        $this->logger->logCall(__METHOD__, ['locationId' => $locationId]);

        $contentId = $this->persistenceHandler->locationHandler()->load($locationId)->contentId;
        $roleAssignments = $this->persistenceHandler->userHandler()->loadRoleAssignmentsByGroupId($contentId);
        $limit = $this->persistenceHandler->contentHandler()->countRelations(
            $contentId
        );

        $reverseRelations = $this->persistenceHandler->contentHandler()->loadRelationList(
            $contentId,
            $limit
        );
        $return = $this->persistenceHandler->trashHandler()->trashSubtree($locationId);

        $relationTags = [];
        if (!empty($reverseRelations)) {
            $relationTags = array_map(function (Relation $relation) {
                return $this->cacheIdentifierGenerator->generateTag(
                    self::CONTENT_IDENTIFIER,
                    [$relation->destinationContentId]
                );
            }, $reverseRelations);
        }

        $roleAssignmentTags = array_map(function (RoleAssignment $roleAssignment): string {
            return $this->cacheIdentifierGenerator->generateTag(
                self::ROLE_ASSIGNMENT_ROLE_LIST_IDENTIFIER,
                [$roleAssignment->roleId]
            );
        }, $roleAssignments);

        $tags = array_merge(
            [
                $this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$contentId]),
                $this->cacheIdentifierGenerator->generateTag(self::LOCATION_PATH_IDENTIFIER, [$locationId]),
            ],
            $relationTags,
            $roleAssignmentTags
        );
        $this->cache->invalidateTags(array_values(array_unique($tags)));

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function recover($trashedId, $newParentId)
    {
        $this->logger->logCall(__METHOD__, ['id' => $trashedId, 'newParentId' => $newParentId]);

        $return = $this->persistenceHandler->trashHandler()->recover($trashedId, $newParentId);

        $contentId = $this->persistenceHandler->locationHandler()->load($return)->contentId;
        $roleAssignments = $this->persistenceHandler->userHandler()->loadRoleAssignmentsByGroupId($contentId);

        $limit = $this->persistenceHandler->contentHandler()->countRelations(
            $contentId
        );

        $reverseRelations = $this->persistenceHandler->contentHandler()->loadRelationList(
            $contentId,
            $limit
        );

        $relationTags = [];
        if (!empty($reverseRelations)) {
            $relationTags = array_map(function (Relation $relation) {
                return $this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$relation->destinationContentId]);
            }, $reverseRelations);
        }

        $roleAssignmentTags = array_map(function (RoleAssignment $roleAssignment): string {
            return $this->cacheIdentifierGenerator->generateTag(
                self::ROLE_ASSIGNMENT_ROLE_LIST_IDENTIFIER,
                [$roleAssignment->roleId]
            );
        }, $roleAssignments);

        $tags = array_merge(
            [
                $this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$contentId]),
                $this->cacheIdentifierGenerator->generateTag(self::LOCATION_PATH_IDENTIFIER, [$trashedId]),
            ],
            $relationTags,
            $roleAssignmentTags
        );
        $this->cache->invalidateTags(array_values(array_unique($tags)));

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function findTrashItems(?CriterionInterface $criterion = null, $offset = 0, $limit = null, ?array $sort = null)
    {
        $this->logger->logCall(__METHOD__, ['criterion' => $criterion ? get_class($criterion) : 'null']);

        return $this->persistenceHandler->trashHandler()->findTrashItems($criterion, $offset, $limit, $sort);
    }

    /**
     * {@inheritdoc}
     */
    public function emptyTrash()
    {
        $this->logger->logCall(__METHOD__, []);

        // We can not use the return value of emptyTrash method because, in the next step, we are not able
        // to fetch the reverse relations of deleted content.
        $tags = [];
        $offset = 0;
        do {
            $trashedItems = $this->persistenceHandler->trashHandler()->findTrashItems(null, $offset, self::EMPTY_TRASH_BULK_SIZE);
            foreach ($trashedItems as $trashedItem) {
                $reverseRelations = $this->persistenceHandler->contentHandler()->loadReverseRelations($trashedItem->contentId);

                foreach ($reverseRelations as $relation) {
                    $tags[$this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$relation->sourceContentId])] = true;
                }

                $tags[$this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$trashedItem->contentId])] = true;
                $tags[$this->cacheIdentifierGenerator->generateTag(self::LOCATION_PATH_IDENTIFIER, [$trashedItem->id])] = true;
            }
            $offset += self::EMPTY_TRASH_BULK_SIZE;
            // Once offset is larger than total count we can exit
        } while ($trashedItems->totalCount > $offset);

        $return = $this->persistenceHandler->trashHandler()->emptyTrash();

        if (!empty($tags)) {
            $this->cache->invalidateTags(array_keys($tags));
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTrashItem($trashedId)
    {
        $this->logger->logCall(__METHOD__, ['id' => $trashedId]);

        // We can not use the return value of deleteTrashItem method because, in the next step, we are not able
        // to fetch the reverse relations of deleted content.
        $trashed = $this->persistenceHandler->trashHandler()->loadTrashItem($trashedId);

        $reverseRelations = $this->persistenceHandler->contentHandler()->loadReverseRelations($trashed->contentId);

        $relationTags = array_map(function (Relation $relation) {
            return $this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$relation->sourceContentId]);
        }, $reverseRelations);

        $return = $this->persistenceHandler->trashHandler()->deleteTrashItem($trashedId);

        $tags = array_merge(
            [
                $this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$return->contentId]),
                $this->cacheIdentifierGenerator->generateTag(self::LOCATION_PATH_IDENTIFIER, [$trashedId]),
            ],
            $relationTags
        );
        $this->cache->invalidateTags(array_values(array_unique($tags)));

        return $return;
    }
}

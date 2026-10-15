<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Handler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway;

/**
 * Object State gateway implementation using the Doctrine database.
 *
 * @internal Gateway implementation is considered internal. Use Persistence Location Handler instead.
 *
 * @see Handler
 */
final class DoctrineDatabase extends Gateway
{
    public function __construct(
        private readonly Connection $connection,
        private readonly MaskGenerator $maskGenerator
    ) {}

    public function loadObjectStateData(int $stateId): array
    {
        $query = $this->createObjectStateFindQuery();
        $query->where(
            $query->expr()->eq(
                'state.id',
                $query->createPositionalParameter($stateId, ParameterType::INTEGER)
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadObjectStateDataByIdentifier(
        string $identifier,
        int $groupId
    ): array {
        $query = $this->createObjectStateFindQuery();
        $query->where(
            $query->expr()->and(
                $query->expr()->eq(
                    'state.identifier',
                    $query->createPositionalParameter($identifier, ParameterType::STRING)
                ),
                $query->expr()->eq(
                    'state.group_id',
                    $query->createPositionalParameter($groupId, ParameterType::INTEGER)
                )
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadObjectStateListData(int $groupId): array
    {
        $query = $this->createObjectStateFindQuery();
        $query->where(
            $query->expr()->eq(
                'state.group_id',
                $query->createPositionalParameter($groupId, ParameterType::INTEGER)
            )
        )->orderBy('state.priority', 'ASC');

        $statement = $query->executeQuery();

        $rows = [];
        while ($row = $statement->fetchAssociative()) {
            $rows[$row['ibexa_object_state_id']][] = $row;
        }

        return array_values($rows);
    }

    public function loadObjectStateGroupData(int $groupId): array
    {
        $query = $this->createObjectStateGroupFindQuery();
        $query->where(
            $query->expr()->eq(
                'state_group.id',
                $query->createPositionalParameter($groupId, ParameterType::INTEGER)
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadObjectStateGroupDataByIdentifier(string $identifier): array
    {
        $query = $this->createObjectStateGroupFindQuery();
        $query->where(
            $query->expr()->eq(
                'state_group.identifier',
                $query->createPositionalParameter($identifier, ParameterType::STRING)
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadObjectStateGroupListData(
        int $offset,
        int $limit
    ): array {
        $query = $this->createObjectStateGroupFindQuery();
        if ($limit > 0) {
            $query->setMaxResults($limit);
            $query->setFirstResult($offset);
        }

        $statement = $query->executeQuery();

        $rows = [];
        while ($row = $statement->fetchAssociative()) {
            $rows[$row['ibexa_object_state_group_id']][] = $row;
        }

        return array_values($rows);
    }

    /**
     * @throws Exception
     * @throws NotFoundException
     */
    public function insertObjectState(
        ObjectState $objectState,
        int $groupId
    ): void {
        $maxPriority = $this->getMaxPriorityForObjectStatesInGroup($groupId);

        $objectState->priority = $maxPriority === null ? 0 : $maxPriority + 1;
        $objectState->groupId = $groupId;

        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::OBJECT_STATE_TABLE)
            ->values(
                [
                    'group_id' => $query->createPositionalParameter(
                        $objectState->groupId,
                        ParameterType::INTEGER
                    ),
                    'default_language_id' => $query->createPositionalParameter(
                        $this->maskGenerator->generateLanguageIndicator(
                            $objectState->defaultLanguage,
                            false
                        ),
                        ParameterType::INTEGER
                    ),
                    'identifier' => $query->createPositionalParameter(
                        $objectState->identifier,
                        ParameterType::STRING
                    ),
                    'language_mask' => $query->createPositionalParameter(
                        $this->maskGenerator->generateLanguageMaskFromLanguageCodes(
                            $objectState->languageCodes,
                            true
                        ),
                        ParameterType::INTEGER
                    ),
                    'priority' => $query->createPositionalParameter(
                        $objectState->priority,
                        ParameterType::INTEGER
                    ),
                ]
            );

        $query->executeStatement();

        $objectState->id = (int)$this->connection->lastInsertId(self::OBJECT_STATE_TABLE_SEQ);

        $this->insertObjectStateTranslations($objectState);

        // If this is a first state in group, assign it to all content objects
        if ($maxPriority === null) {
            $this->connection->executeStatement(
                'INSERT INTO ' . Gateway::OBJECT_STATE_LINK_TABLE . ' (contentobject_id, contentobject_state_id) ' .
                'SELECT id, :object_state_id FROM ibexa_content',
                [
                    'object_state_id' => $objectState->id,
                ],
                [
                    'object_state_id' => ParameterType::INTEGER,
                ]
            );
        }
    }

    /**
     * @param string[] $languageCodes
     *
     * @throws NotFoundException
     */
    private function updateObjectStateCommonFields(
        string $tableName,
        int $id,
        string $identifier,
        string $defaultLanguageCode,
        array $languageCodes
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update($tableName)
            ->set(
                'default_language_id',
                $query->createPositionalParameter(
                    $this->maskGenerator->generateLanguageIndicator(
                        $defaultLanguageCode,
                        false
                    ),
                    ParameterType::INTEGER
                )
            )
            ->set(
                'identifier',
                $query->createPositionalParameter(
                    $identifier,
                    ParameterType::STRING
                )
            )
            ->set(
                'language_mask',
                $query->createPositionalParameter(
                    $this->maskGenerator->generateLanguageMaskFromLanguageCodes(
                        $languageCodes,
                        true
                    ),
                    ParameterType::INTEGER
                )
            )
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    public function updateObjectState(ObjectState $objectState): void
    {
        // First update the state
        $this->updateObjectStateCommonFields(
            self::OBJECT_STATE_TABLE,
            $objectState->id,
            $objectState->identifier,
            $objectState->defaultLanguage,
            $objectState->languageCodes
        );

        // And then refresh object state translations
        // by removing existing ones and adding new ones
        $this->deleteObjectStateTranslations($objectState->id);
        $this->insertObjectStateTranslations($objectState);
    }

    public function deleteObjectState(int $stateId): void
    {
        $this->deleteObjectStateTranslations($stateId);

        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::OBJECT_STATE_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($stateId, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    public function updateObjectStateLinks(
        int $oldStateId,
        int $newStateId
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::OBJECT_STATE_LINK_TABLE)
            ->set(
                'contentobject_state_id',
                $query->createPositionalParameter($newStateId, ParameterType::INTEGER)
            )
            ->where(
                $query->expr()->eq(
                    'contentobject_state_id',
                    $query->createPositionalParameter($oldStateId, ParameterType::INTEGER)
                )
            )
        ;

        $query->executeStatement();
    }

    /**
     * Change Content to object state assignment.
     */
    private function updateContentStateAssignment(
        int $contentId,
        int $stateId,
        int $assignedStateId
    ): void {
        // no action required if there's no change
        if ($stateId === $assignedStateId) {
            return;
        }

        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::OBJECT_STATE_LINK_TABLE)
            ->set(
                'contentobject_state_id',
                $query->createPositionalParameter($stateId, ParameterType::INTEGER)
            )
            ->where(
                $query->expr()->eq(
                    'contentobject_id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $query->expr()->eq(
                    'contentobject_state_id',
                    $query->createPositionalParameter($assignedStateId, ParameterType::INTEGER)
                )
            )
        ;

        $query->executeStatement();
    }

    public function deleteObjectStateLinks(int $stateId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::OBJECT_STATE_LINK_TABLE)
            ->where(
                $query->expr()->eq(
                    'contentobject_state_id',
                    $query->createPositionalParameter($stateId, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    public function insertObjectStateGroup(Group $objectStateGroup): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::OBJECT_STATE_GROUP_TABLE)
            ->values(
                [
                    'default_language_id' => $query->createPositionalParameter(
                        $this->maskGenerator->generateLanguageIndicator(
                            $objectStateGroup->defaultLanguage,
                            false
                        ),
                        ParameterType::INTEGER
                    ),
                    'identifier' => $query->createPositionalParameter(
                        $objectStateGroup->identifier,
                        ParameterType::STRING
                    ),
                    'language_mask' => $query->createPositionalParameter(
                        $this->maskGenerator->generateLanguageMaskFromLanguageCodes(
                            $objectStateGroup->languageCodes,
                            true
                        ),
                        ParameterType::INTEGER
                    ),
                ]
            )
        ;

        $query->executeStatement();

        $objectStateGroup->id = (int)$this->connection->lastInsertId(
            self::OBJECT_STATE_GROUP_TABLE_SEQ
        );

        $this->insertObjectStateGroupTranslations($objectStateGroup);
    }

    public function updateObjectStateGroup(Group $objectStateGroup): void
    {
        // First update the group
        $this->updateObjectStateCommonFields(
            self::OBJECT_STATE_GROUP_TABLE,
            $objectStateGroup->id,
            $objectStateGroup->identifier,
            $objectStateGroup->defaultLanguage,
            $objectStateGroup->languageCodes
        );

        // And then refresh group translations
        // by removing old ones and adding new ones
        $this->deleteObjectStateGroupTranslations($objectStateGroup->id);
        $this->insertObjectStateGroupTranslations($objectStateGroup);
    }

    public function deleteObjectStateGroup(int $groupId): void
    {
        $this->deleteObjectStateGroupTranslations($groupId);

        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::OBJECT_STATE_GROUP_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($groupId, ParameterType::INTEGER)
                )
            )
        ;

        $query->executeStatement();
    }

    public function setContentState(
        int $contentId,
        int $groupId,
        int $stateId
    ): void {
        // First find out if $contentId is related to existing states in $groupId
        $assignedStateId = $this->getContentStateId($contentId, $groupId);

        if (null !== $assignedStateId) {
            // We already have a state assigned to $contentId, update to new one
            $this->updateContentStateAssignment($contentId, $stateId, $assignedStateId);
        } else {
            // No state assigned to $contentId from specified group, create assignment
            $this->insertContentStateAssignment($contentId, $stateId);
        }
    }

    public function loadObjectStateDataForContent(
        int $contentId,
        int $stateGroupId
    ): array {
        $query = $this->createObjectStateFindQuery();
        $expr = $query->expr();
        $query
            ->innerJoin(
                'state',
                Gateway::OBJECT_STATE_LINK_TABLE,
                'link',
                'state.id = link.contentobject_state_id'
            )
            ->where(
                $expr->eq(
                    'state.group_id',
                    $query->createPositionalParameter($stateGroupId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'link.contentobject_id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function getContentCount(int $stateId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(contentobject_id)')
            ->from(self::OBJECT_STATE_LINK_TABLE)
            ->where(
                $query->expr()->eq(
                    'contentobject_state_id',
                    $query->createPositionalParameter($stateId, ParameterType::INTEGER)
                )
            );

        return (int)$query->executeQuery()->fetchOne();
    }

    public function updateObjectStatePriority(
        int $stateId,
        int $priority
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::OBJECT_STATE_TABLE)
            ->set('priority', $query->createPositionalParameter($priority, ParameterType::INTEGER))
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($stateId, ParameterType::INTEGER)
                )
            )
        ;

        $query->executeStatement();
    }

    /**
     * Create a generic query for fetching object state(s).
     */
    private function createObjectStateFindQuery(): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                // Object state
                'state.default_language_id AS ibexa_object_state_default_language_id',
                'state.group_id AS ibexa_object_state_group_id',
                'state.id AS ibexa_object_state_id',
                'state.identifier AS ibexa_object_state_identifier',
                'state.language_mask AS ibexa_object_state_language_mask',
                'state.priority AS ibexa_object_state_priority',
                // Object state language
                'lang.description AS ibexa_object_state_language_description',
                'lang.language_id AS ibexa_object_state_language_language_id',
                'lang.name AS ibexa_object_state_language_name'
            )
            ->from(self::OBJECT_STATE_TABLE, 'state')
            ->innerJoin(
                'state',
                self::OBJECT_STATE_LANGUAGE_TABLE,
                'lang',
                'state.id = lang.contentobject_state_id',
            );

        return $query;
    }

    /**
     * Create a generic query for fetching object state group(s).
     */
    private function createObjectStateGroupFindQuery(): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                // Object state group
                'state_group.default_language_id AS ibexa_object_state_group_default_language_id',
                'state_group.id AS ibexa_object_state_group_id',
                'state_group.identifier AS ibexa_object_state_group_identifier',
                'state_group.language_mask AS ibexa_object_state_group_language_mask',
                // Object state group language
                'state_group_lang.description AS ibexa_object_state_group_language_description',
                'state_group_lang.language_id AS ibexa_object_state_group_language_language_id',
                'state_group_lang.real_language_id AS ibexa_object_state_group_language_real_language_id',
                'state_group_lang.name AS ibexa_object_state_group_language_name'
            )
            ->from(self::OBJECT_STATE_GROUP_TABLE, 'state_group')
            ->innerJoin(
                'state_group',
                self::OBJECT_STATE_GROUP_LANGUAGE_TABLE,
                'state_group_lang',
                'state_group.id = state_group_lang.contentobject_state_group_id'
            );

        return $query;
    }

    /**
     * Insert object state group translations into database.
     *
     * @throws NotFoundException if Object State language does not exist
     */
    private function insertObjectStateTranslations(ObjectState $objectState): void
    {
        foreach ($objectState->languageCodes as $languageCode) {
            $query = $this->connection->createQueryBuilder();
            $query
                ->insert(self::OBJECT_STATE_LANGUAGE_TABLE)
                ->values(
                    [
                        'contentobject_state_id' => $query->createPositionalParameter(
                            $objectState->id,
                            ParameterType::INTEGER
                        ),
                        'description' => $query->createPositionalParameter(
                            $objectState->description[$languageCode],
                            ParameterType::STRING
                        ),
                        'name' => $query->createPositionalParameter(
                            $objectState->name[$languageCode],
                            ParameterType::STRING
                        ),
                        'language_id' => $query->createPositionalParameter(
                            $this->maskGenerator->generateLanguageIndicator(
                                $languageCode,
                                $languageCode === $objectState->defaultLanguage
                            ),
                            ParameterType::INTEGER
                        ),
                    ]
                );

            $query->executeStatement();
        }
    }

    /**
     * Deletes all translations of the $stateId state.
     *
     * @param mixed $stateId
     */
    private function deleteObjectStateTranslations(int $stateId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::OBJECT_STATE_LANGUAGE_TABLE)
            ->where(
                $query->expr()->eq(
                    'contentobject_state_id',
                    $query->createPositionalParameter($stateId, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    /**
     * Insert object state group translations into database.
     *
     * @throws NotFoundException if Object State Group language does not exist
     */
    private function insertObjectStateGroupTranslations(Group $objectStateGroup): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::OBJECT_STATE_GROUP_LANGUAGE_TABLE)
            ->values(
                [
                    'contentobject_state_group_id' => ':contentobject_state_group_id',
                    'description' => ':description',
                    'name' => ':name',
                    'language_id' => ':language_id',
                    'real_language_id' => ':real_language_id',
                ]
            )
        ;
        foreach ($objectStateGroup->languageCodes as $languageCode) {
            $languageId = $this->maskGenerator->generateLanguageIndicator(
                $languageCode,
                $languageCode === $objectStateGroup->defaultLanguage
            );
            $query
                ->setParameter('contentobject_state_group_id', $objectStateGroup->id, ParameterType::INTEGER)
                ->setParameter('description', $objectStateGroup->description[$languageCode], ParameterType::STRING)
                ->setParameter('name', $objectStateGroup->name[$languageCode], ParameterType::STRING)
                ->setParameter('language_id', $languageId, ParameterType::INTEGER)
                ->setParameter('real_language_id', $languageId & ~1, ParameterType::INTEGER);

            $query->executeStatement();
        }
    }

    /**
     * Delete all translations of the $groupId state group.
     */
    private function deleteObjectStateGroupTranslations(int $groupId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::OBJECT_STATE_GROUP_LANGUAGE_TABLE)
            ->where(
                $query->expr()->eq(
                    'contentobject_state_group_id',
                    $query->createPositionalParameter($groupId, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    private function getMaxPriorityForObjectStatesInGroup(int $groupId): ?int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('MAX(priority)')
            ->from(self::OBJECT_STATE_TABLE)
            ->where(
                $query->expr()->eq(
                    'group_id',
                    $query->createPositionalParameter($groupId, ParameterType::INTEGER)
                )
            );

        $priority = $query->executeQuery()->fetchOne();

        return null !== $priority ? (int)$priority : null;
    }

    private function getContentStateId(
        int $contentId,
        int $groupId
    ): ?int {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('state.id')
            ->from(self::OBJECT_STATE_TABLE, 'state')
            ->innerJoin(
                'state',
                self::OBJECT_STATE_LINK_TABLE,
                'link',
                'state.id = link.contentobject_state_id'
            )
            ->where(
                $query->expr()->eq(
                    'state.group_id',
                    $query->createPositionalParameter($groupId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $query->expr()->eq(
                    'link.contentobject_id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            );

        $stateId = $query->executeQuery()->fetchOne();

        return false !== $stateId ? (int)$stateId : null;
    }

    private function insertContentStateAssignment(
        int $contentId,
        int $stateId
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::OBJECT_STATE_LINK_TABLE)
            ->values(
                [
                    'contentobject_id' => $query->createPositionalParameter(
                        $contentId,
                        ParameterType::INTEGER
                    ),
                    'contentobject_state_id' => $query->createPositionalParameter(
                        $stateId,
                        ParameterType::INTEGER
                    ),
                ]
            );

        $query->executeStatement();
    }
}

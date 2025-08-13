<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType\Query;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway;

/**
 * @implements \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionHandlerInterface<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface>
 */
abstract class Base implements CriterionHandlerInterface
{
    /**
     * Inner join the `ezcontentclassgroup` table if not joined yet.
     */
    protected function joinContentTypeGroup(QueryBuilder $query): void
    {
        if (!$this->hasJoinedTable($query, Gateway::CONTENT_TYPE_GROUP_TABLE)) {
            $query->innerJoin(
                'g',
                Gateway::CONTENT_TYPE_GROUP_TABLE,
                'ctg',
                'g.contentclass_id = ctg.id'
            );
            $query->addSelect('ctg.id');
        }
    }

    /**
     * Inner join the `ezcontentclass_attribute` table if not joined yet.
     */
    protected function joinFieldDefinitions(QueryBuilder $query): void
    {
        if (!$this->hasJoinedTable($query, Gateway::FIELD_DEFINITION_TABLE)) {
            $expr = $query->expr();

            $query->leftJoin(
                'c',
                Gateway::FIELD_DEFINITION_TABLE,
                'a',
                (string)$expr->and(
                    'c.id = a.contentclass_id',
                    'c.version = a.version'
                )
            );
            $query->addSelect('a.id');
        }
    }

    /**
     * Inner join the `ezcontentclass_classgroup` table if not joined yet.
     */
    protected function joinContentTypeGroupAssignmentTable(QueryBuilder $query): void
    {
        if (!$this->hasJoinedTable($query, Gateway::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE)) {
            $expr = $query->expr();

            $query->leftJoin(
                'c',
                Gateway::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE,
                'g',
                (string)$expr->and(
                    'c.id = g.contentclass_id',
                    'c.version = g.contentclass_version',
                )
            );
            $query->addSelect('g.group_id');
        }
    }

    protected function hasJoinedTable(QueryBuilder $queryBuilder, string $tableName): bool
    {
        // find table name in a structure: ['fromAlias' => [['joinTable' => '<table_name>'], ...]]
        $joinedParts = $queryBuilder->getQueryPart('join');
        foreach ($joinedParts as $joinedTables) {
            foreach ($joinedTables as $join) {
                if ($join['joinTable'] === $tableName) {
                    return true;
                }
            }
        }

        return false;
    }
}

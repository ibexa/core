<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\URL\Gateway\DoctrineDatabase;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler;

abstract class Base implements CriterionHandler
{
    /**
     * Inner join `ibexa_url_content_link` table if not joined yet.
     */
    protected function joinContentObjectLink(QueryBuilder $query): void
    {
        if (!$this->hasJoinedTable($query, DoctrineDatabase::URL_LINK_TABLE)) {
            $query->innerJoin(
                'url',
                DoctrineDatabase::URL_LINK_TABLE,
                'u_lnk',
                'url.id = u_lnk.url_id'
            );
        }
    }

    /**
     * Inner join `ibexa_content` table if not joined yet.
     */
    protected function joinContentObject(QueryBuilder $query): void
    {
        if (!$this->hasJoinedTable($query, ContentGateway::CONTENT_ITEM_TABLE)) {
            $query->innerJoin(
                'f_def',
                ContentGateway::CONTENT_ITEM_TABLE,
                'c',
                'c.id = f_def.contentobject_id'
            );
        }
    }

    /**
     * Inner join `ibexa_content_field` table if not joined yet.
     */
    protected function joinContentObjectAttribute(QueryBuilder $query): void
    {
        if (!$this->hasJoinedTable($query, ContentGateway::CONTENT_FIELD_TABLE)) {
            $query->innerJoin(
                'u_lnk',
                ContentGateway::CONTENT_FIELD_TABLE,
                'f_def',
                $query->expr()->and(
                    'u_lnk.contentobject_attribute_id = f_def.id',
                    'u_lnk.contentobject_attribute_version = f_def.version'
                )
            );
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

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Core\Persistence\Doctrine\TracksJoinedTablesTrait;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\URL\Gateway\DoctrineDatabase;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler;

abstract class Base implements CriterionHandler
{
    use TracksJoinedTablesTrait;

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
            $this->markTableJoined($query, DoctrineDatabase::URL_LINK_TABLE);
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
            $this->markTableJoined($query, ContentGateway::CONTENT_ITEM_TABLE);
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
            $this->markTableJoined($query, ContentGateway::CONTENT_FIELD_TABLE);
        }
    }

    protected function hasJoinedTable(QueryBuilder $queryBuilder, string $tableName): bool
    {
        return $this->isTableJoined($queryBuilder, $tableName);
    }

    private function markTableJoined(QueryBuilder $queryBuilder, string $tableName): void
    {
        $this->markTableAsJoined($queryBuilder, $tableName);
    }
}

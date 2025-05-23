<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Gateway\DoctrineDatabase;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;

/**
 * @internal For internal use by the Content gateway.
 */
final class QueryBuilder
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create select query to query content name data.
     */
    public function createNamesQuery(): DoctrineQueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                'contentobject_id AS content_name_contentobject_id',
                'content_version AS content_name_content_version',
                'name AS content_name_name',
                'content_translation AS content_name_content_translation'
            )
            ->from(Gateway::CONTENT_NAME_TABLE);

        return $query;
    }

    /**
     * Create a select query for content relations.
     */
    public function createRelationFindQueryBuilder(): DoctrineQueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                'l.id AS content_link_id',
                'l.contentclassattribute_id AS content_link_contentclassattribute_id',
                'l.from_contentobject_id AS content_link_from_contentobject_id',
                'l.from_contentobject_version AS content_link_from_contentobject_version',
                'l.relation_type AS content_link_relation_type',
                'l.to_contentobject_id AS content_link_to_contentobject_id'
            )
            ->from(
                Gateway::CONTENT_RELATION_TABLE,
                'l'
            );

        return $query;
    }

    /**
     * Create an update query for setting Content item Version status.
     */
    public function getSetVersionStatusQuery(
        int $contentId,
        int $versionNo,
        int $versionStatus
    ): DoctrineQueryBuilder {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(Gateway::CONTENT_VERSION_TABLE)
            ->set('status', ':status')
            ->set('modified', ':modified')
            ->where('contentobject_id = :contentId')
            ->andWhere('version = :versionNo')
            ->setParameter('status', $versionStatus, ParameterType::INTEGER)
            ->setParameter('modified', time(), ParameterType::INTEGER)
            ->setParameter('contentId', $contentId, ParameterType::INTEGER)
            ->setParameter('versionNo', $versionNo, ParameterType::INTEGER);

        return $query;
    }

    /**
     * Create a select query to load Content Info data.
     *
     * @see \Ibexa\Core\Persistence\Legacy\Content\Gateway::loadContentInfo()
     * @see \Ibexa\Core\Persistence\Legacy\Content\Gateway::loadContentInfoList()
     * @see \Ibexa\Core\Persistence\Legacy\Content\Gateway::loadContentInfoByRemoteId()
     * @see \Ibexa\Core\Persistence\Legacy\Content\Gateway::loadContentInfoByLocationId()
     */
    public function createLoadContentInfoQueryBuilder(
        bool $joinMainLocation = true
    ): DoctrineQueryBuilder {
        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();

        $joinCondition = $expr->eq('c.id', 't.contentobject_id');
        if ($joinMainLocation) {
            // wrap join condition with AND operator and join by a Main Location
            $joinCondition = $expr->and(
                $joinCondition,
                $expr->eq('t.node_id', 't.main_node_id')
            );
        }

        $queryBuilder
            ->select('c.*', 't.main_node_id AS content_tree_main_node_id')
            ->from(Gateway::CONTENT_ITEM_TABLE, 'c')
            ->leftJoin(
                'c',
                LocationGateway::CONTENT_TREE_TABLE,
                't',
                $joinCondition
            );

        return $queryBuilder;
    }

    /**
     * Get query builder for content version objects, used for version loading w/o fields.
     *
     * Creates a select query with all necessary joins to fetch a complete
     * content object. Does not apply any WHERE conditions, and does not contain
     * name data as it will lead to large result set {@see createNamesQuery}.
     */
    public function createVersionInfoFindQueryBuilder(): DoctrineQueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();

        $query
            ->select(
                'v.id AS content_version_id',
                'v.version AS content_version_version',
                'v.modified AS content_version_modified',
                'v.creator_id AS content_version_creator_id',
                'v.created AS content_version_created',
                'v.status AS content_version_status',
                'v.contentobject_id AS content_version_contentobject_id',
                'v.initial_language_id AS content_version_initial_language_id',
                'v.language_mask AS content_version_language_mask',
                // Content main location
                't.main_node_id AS content_tree_main_node_id',
                // Content object
                'c.id AS content_id',
                'c.contentclass_id AS content_content_content_type_id',
                'c.section_id AS content_section_id',
                'c.owner_id AS content_owner_id',
                'c.remote_id AS content_remote_id',
                'c.current_version AS content_current_version',
                'c.initial_language_id AS content_initial_language_id',
                'c.modified AS content_modified',
                'c.published AS content_published',
                'c.status AS content_status',
                'c.name AS content_name',
                'c.language_mask AS content_language_mask',
                'c.is_hidden AS content_is_hidden'
            )
            ->from(Gateway::CONTENT_VERSION_TABLE, 'v')
            ->innerJoin(
                'v',
                Gateway::CONTENT_ITEM_TABLE,
                'c',
                $expr->eq('c.id', 'v.contentobject_id')
            )
            ->leftJoin(
                'v',
                LocationGateway::CONTENT_TREE_TABLE,
                't',
                $expr->and(
                    $expr->eq('t.contentobject_id', 'v.contentobject_id'),
                    $expr->eq('t.main_node_id', 't.node_id')
                )
            );

        return $query;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\Gateway\Location\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Ibexa\Contracts\Core\Persistence\Filter\CriterionVisitor;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Persistence\Filter\SortClauseVisitor;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Filter\Gateway\Gateway;

/**
 * @internal for internal use by Legacy Storage
 */
final readonly class DoctrineGateway implements Gateway
{
    public function __construct(
        private Connection $connection,
        private CriterionVisitor $criterionVisitor,
        private SortClauseVisitor $sortClauseVisitor
    ) {
    }

    public function count(FilteringCriterion $criterion): int
    {
        $query = $this->buildQuery($criterion);

        $query->select('COUNT(DISTINCT location.node_id)');

        return (int)$query->executeQuery()->fetch(FetchMode::COLUMN);
    }

    public function find(
        FilteringCriterion $criterion,
        array $sortClauses,
        int $limit,
        int $offset
    ): iterable {
        $query = $this->buildQuery($criterion);
        $this->sortClauseVisitor->visitSortClauses($query, $sortClauses);

        $query->setFirstResult($offset);
        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        $resultStatement = $query->executeQuery();

        while (false !== ($row = $resultStatement->fetch(FetchMode::ASSOCIATIVE))) {
            yield $row;
        }
    }

    private function buildQuery(FilteringCriterion $criterion): FilteringQueryBuilder
    {
        $queryBuilder = new FilteringQueryBuilder($this->connection);
        $queryBuilder
            ->select(
                'location.node_id AS location_node_id',
                'location.priority AS location_priority',
                'location.is_hidden AS location_is_hidden',
                'location.is_invisible AS location_is_invisible',
                'location.remote_id AS location_remote_id',
                'location.contentobject_id AS location_contentobject_id',
                'location.parent_node_id AS location_parent_node_id',
                'location.path_identification_string AS location_path_identification_string',
                'location.path_string AS location_path_string',
                'location.depth AS location_depth',
                'location.sort_field AS location_sort_field',
                'location.sort_order AS location_sort_order',
                'location.main_node_id AS content_main_location_id',
                'content.id AS content_id',
                'content.content_type_id AS content_type_id',
                'content.current_version AS content_current_version',
                'content.initial_language_id AS content_initial_language_id',
                'content.language_mask AS content_language_mask',
                'content.modified AS content_modified',
                'content.name AS content_name',
                'content.owner_id AS content_owner_id',
                'content.published AS content_published',
                'content.remote_id AS content_remote_id',
                'content.section_id AS content_section_id',
                'content.status AS content_status',
                'content.is_hidden AS content_is_hidden'
            )
            ->distinct()
            ->from(LocationGateway::CONTENT_TREE_TABLE, 'location')
            ->join(
                'location',
                ContentGateway::CONTENT_ITEM_TABLE,
                'content',
                'content.id = location.contentobject_id'
            )
            ->joinPublishedVersion()
        ;

        $constraint = $this->criterionVisitor->visitCriteria($queryBuilder, $criterion);
        if ('' !== $constraint) {
            $queryBuilder->where($constraint);
        }

        return $queryBuilder;
    }
}

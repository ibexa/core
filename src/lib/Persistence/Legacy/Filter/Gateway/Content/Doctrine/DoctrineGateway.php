<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\Gateway\Content\Doctrine;

use function array_filter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Filter\CriterionVisitor;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Persistence\Filter\SortClauseVisitor;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Filter\Gateway\Gateway;
use function iterator_to_array;
use function sprintf;
use Traversable;

/**
 * @internal for internal use by Legacy Storage
 */
final class DoctrineGateway implements Gateway
{
    public const array COLUMN_MAP = [
        // Content Info
        'content_id' => 'content.id',
        'content_type_id' => 'content.content_type_id',
        'content_current_version' => 'content.current_version',
        'content_initial_language_id' => 'content.initial_language_id',
        'content_language_mask' => 'content.language_mask',
        'content_modified' => 'content.modified',
        'content_name' => 'content.name',
        'content_owner_id' => 'content.owner_id',
        'content_published' => 'content.published',
        'content_remote_id' => 'content.remote_id',
        'content_section_id' => 'content.section_id',
        'content_status' => 'content.status',
        'content_is_hidden' => 'content.is_hidden',
        // Version Info
        'content_version_id' => 'version.id',
        'content_version_no' => 'version.version',
        'content_version_creator_id' => 'version.creator_id',
        'content_version_created' => 'version.created',
        'content_version_modified' => 'version.modified',
        'content_version_status' => 'version.status',
        'content_version_language_mask' => 'version.language_mask',
        'content_version_initial_language_id' => 'version.initial_language_id',
        // Main Location (nullable)
        'content_main_location_id' => 'main_location.main_node_id',
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly CriterionVisitor $criterionVisitor,
        private readonly SortClauseVisitor $sortClauseVisitor
    ) {
    }

    public function count(FilteringCriterion $criterion): int
    {
        $query = $this->buildQuery(
            ['COUNT(DISTINCT content.id)'],
            $criterion
        );

        return (int)$query->executeQuery()->fetch(FetchMode::COLUMN);
    }

    public function find(
        FilteringCriterion $criterion,
        array $sortClauses,
        int $limit,
        int $offset
    ): iterable {
        $query = $this->buildQuery(iterator_to_array($this->getColumns()), $criterion);
        $this->sortClauseVisitor->visitSortClauses($query, $sortClauses);

        // get additional data for the same query constraints
        $names = $this->bulkFetchVersionNames(clone $query);
        $fieldValues = $this->bulkFetchFieldValues(clone $query);

        // wrap query to avoid duplicate entries for multiple Locations
        $wrappedQuery = $this->wrapMainQuery($query);
        $wrappedQuery->setFirstResult($offset);
        if ($limit > 0) {
            $wrappedQuery->setMaxResults($limit);
        }

        $resultStatement = $wrappedQuery->executeQuery();
        while (false !== ($row = $resultStatement->fetch(FetchMode::ASSOCIATIVE))) {
            $contentId = (int)$row['content_id'];
            $versionNo = (int)$row['content_version_no'];
            $row['content_version_names'] = $this->extractVersionNames(
                $names,
                $contentId,
                $versionNo
            );
            $row['content_version_fields'] = $this->extractFieldValues(
                $fieldValues,
                $contentId,
                $versionNo
            );

            yield $row;
        }
    }

    private function buildQuery(
        array $columns,
        FilteringCriterion $criterion
    ): FilteringQueryBuilder {
        $queryBuilder = new FilteringQueryBuilder($this->connection);

        $expressionBuilder = $queryBuilder->expr();
        $queryBuilder
            ->select($columns)
            ->distinct()
            ->from(ContentGateway::CONTENT_ITEM_TABLE, 'content')
            ->joinPublishedVersion()
            ->leftJoin(
                'content',
                LocationGateway::CONTENT_TREE_TABLE,
                'main_location',
                $expressionBuilder->and(
                    'content.id = main_location.contentobject_id',
                    'main_location.main_node_id = main_location.node_id'
                )
            );

        $constraint = $this->criterionVisitor->visitCriteria($queryBuilder, $criterion);
        if ('' !== $constraint) {
            $queryBuilder->where($constraint);
        }

        return $queryBuilder;
    }

    /**
     * Return names as a map of <code>'<translation_language_code>' => '<name>'</code>.
     *
     * Process data fetched by {@see bulkFetchVersionNames}
     */
    private function extractVersionNames(array $names, int $contentId, int $versionNo): array
    {
        $rawVersionNames = $this->extractVersionData($names, $contentId, $versionNo);

        $names = [];
        foreach ($rawVersionNames as $nameRow) {
            $names[$nameRow['real_translation']] = $nameRow['name'];
        }

        return $names;
    }

    private function extractFieldValues(array $fieldValues, int $contentId, int $versionNo): array
    {
        return $this->extractVersionData($fieldValues, $contentId, $versionNo);
    }

    /**
     * Extract Version-specific data from bulk-loaded rows.
     */
    private function extractVersionData(array $rows, int $contentId, int $versionNo): array
    {
        return array_filter(
            $rows,
            static function (array $row) use ($contentId, $versionNo): bool {
                return (int)$row['content_id'] === $contentId
                    && (int)$row['version_no'] === $versionNo;
            }
        );
    }

    private function bulkFetchVersionNames(FilteringQueryBuilder $query): array
    {
        $query
            // completely reset SELECT part to get only needed data
            ->select(
                'content.id AS content_id',
                'version.version AS version_no',
                'content_name.name',
                'content_name.real_translation'
            )
            ->distinct()
            // join names table to pre-existing query
            ->joinOnce(
                'content',
                ContentGateway::CONTENT_NAME_TABLE,
                'content_name',
                (string)$query->expr()->and(
                    'content.id = content_name.contentobject_id',
                    'version.version = content_name.content_version',
                    'version.language_mask & content_name.language_id > 0'
                )
            )
            // reset not needed parts, keeping FROM, other JOINs, and WHERE constraints
            ->setMaxResults(null)
            ->setFirstResult(0)
            ->resetQueryPart('orderBy');

        return $query->executeQuery()->fetchAllAssociative();
    }

    private function bulkFetchFieldValues(FilteringQueryBuilder $query): array
    {
        $query
            // completely reset SELECT part to get only needed data
            ->select(
                'content_field.contentobject_id AS content_id',
                'content_field.version AS version_no',
                'content_field.id AS field_id',
                'content_field.content_type_field_definition_id AS field_definition_id',
                'content_field.data_type_string AS field_type',
                'content_field.language_code AS field_language_code',
                'content_field.data_float AS field_data_float',
                'content_field.data_int AS field_data_int',
                'content_field.data_text AS field_data_text',
                'content_field.sort_key_int AS field_sort_key_int',
                'content_field.sort_key_string AS field_sort_key_string'
            )
            ->distinct()
            ->joinOnce(
                'content',
                ContentGateway::CONTENT_FIELD_TABLE,
                'content_field',
                (string)$query->expr()->and(
                    'content.id = content_field.contentobject_id',
                    'version.version = content_field.version',
                    'version.language_mask & content_field.language_id = content_field.language_id'
                )
            )
            // reset not needed parts, keeping FROM, other JOINs, and WHERE constraints
            ->setMaxResults(null)
            ->setFirstResult(0)
            ->resetQueryPart('orderBy');

        return $query->executeQuery()->fetchAllAssociative();
    }

    private function getColumns(): Traversable
    {
        foreach (self::COLUMN_MAP as $columnAlias => $columnName) {
            yield "{$columnName} AS {$columnAlias}";
        }
    }

    /**
     * Wrap query to avoid duplicate entries for multiple Locations.
     */
    private function wrapMainQuery(FilteringQueryBuilder $query): QueryBuilder
    {
        $wrappedQuery = $this->connection->createQueryBuilder();
        $wrappedQuery
            ->select(array_keys(self::COLUMN_MAP))
            ->distinct()
            ->from(sprintf('(%s)', $query->getSQL()), 'wrapped')
            ->setParameters($query->getParameters(), $query->getParameterTypes());

        return $wrappedQuery;
    }
}

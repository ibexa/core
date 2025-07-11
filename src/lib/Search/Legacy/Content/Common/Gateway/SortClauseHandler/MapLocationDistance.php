<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\MapLocation\MapLocationStorage\Gateway\DoctrineStorage;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;

/**
 * Content locator gateway implementation using the DoctrineDatabase.
 */
class MapLocationDistance extends Field
{
    /**
     * Check if this sort clause handler accepts to handle the given sort clause.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause $sortClause
     *
     * @return bool
     */
    public function accept(SortClause $sortClause): bool
    {
        return $sortClause instanceof SortClause\MapLocationDistance;
    }

    public function applySelect(
        QueryBuilder $query,
        SortClause $sortClause,
        int $number
    ): array {
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target\MapLocationTarget $target */
        $target = $sortClause->targetData;
        $externalTable = $this->getSortTableName($number, DoctrineStorage::MAP_LOCATION_TABLE);

        // note: avoid using literal names for parameters to account for multiple visits of the same Criterion
        $latitudePlaceholder = $query->createNamedParameter($target->latitude);
        $longitudePlaceholder = $query->createNamedParameter($target->longitude);

        // note: can have literal name for all visits of this Criterion because it's constant
        $query->setParameter('longitude_correction', cos(deg2rad($target->latitude)) ** 2);

        // build: (latitude1 - latitude2)^2 + (longitude2 - longitude2)^2 * longitude_correction)
        $latitudeSubstrExpr = "({$externalTable}.latitude - {$latitudePlaceholder})";
        $longitudeSubstrExpr = "({$externalTable}.longitude - {$longitudePlaceholder})";
        $latitudeExpr = "{$latitudeSubstrExpr} * {$latitudeSubstrExpr}";
        $longitudeExpr = "{$longitudeSubstrExpr} * {$longitudeSubstrExpr} * :longitude_correction";
        $distanceExpression = "{$latitudeExpr} + {$longitudeExpr}";

        $query->addSelect(
            sprintf('%s AS %s', $distanceExpression, $column1 = $this->getSortColumnName($number))
        );

        return [$column1];
    }

    public function applyJoin(
        QueryBuilder $query,
        SortClause $sortClause,
        int $number,
        array $languageSettings
    ): void {
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target\FieldTarget $fieldTarget */
        $fieldTarget = $sortClause->targetData;
        $fieldMap = $this->contentTypeHandler->getSearchableFieldMap();

        if (!isset($fieldMap[$fieldTarget->typeIdentifier][$fieldTarget->fieldIdentifier]['field_definition_id'])) {
            throw new InvalidArgumentException(
                '$sortClause->targetData',
                'No searchable Fields found for the provided Sort Clause target ' .
                "'{$fieldTarget->fieldIdentifier}' on '{$fieldTarget->typeIdentifier}'."
            );
        }

        $fieldDefinitionId = $fieldMap[$fieldTarget->typeIdentifier][$fieldTarget->fieldIdentifier]['field_definition_id'];
        $table = $this->getSortTableName($number);
        $externalTable = $this->getSortTableName($number, DoctrineStorage::MAP_LOCATION_TABLE);

        $tableAlias = $this->connection->quoteIdentifier($table);
        $externalTableAlias = $this->connection->quoteIdentifier($externalTable);
        $query
            ->leftJoin(
                'c',
                ContentGateway::CONTENT_FIELD_TABLE,
                $tableAlias,
                $query->expr()->and(
                    $query->expr()->eq(
                        $query->createNamedParameter($fieldDefinitionId, ParameterType::INTEGER),
                        $tableAlias . '.content_type_field_definition_id'
                    ),
                    $query->expr()->eq(
                        $tableAlias . '.contentobject_id',
                        'c.id'
                    ),
                    $query->expr()->eq(
                        $tableAlias . '.version',
                        'c.current_version'
                    ),
                    $this->getFieldCondition($query, $languageSettings, $tableAlias)
                )
            )
            ->leftJoin(
                $tableAlias,
                DoctrineStorage::MAP_LOCATION_TABLE,
                $externalTableAlias,
                $query->expr()->and(
                    $query->expr()->eq(
                        $externalTableAlias . '.contentobject_version',
                        $tableAlias . '.version'
                    ),
                    $query->expr()->eq(
                        $externalTableAlias . '.contentobject_attribute_id',
                        $tableAlias . '.id'
                    )
                )
            );
    }
}

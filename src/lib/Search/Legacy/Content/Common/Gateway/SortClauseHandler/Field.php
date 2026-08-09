<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\LanguagePriorityConditionBuilder;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler;

/**
 * Content locator gateway implementation using the DoctrineDatabase.
 */
class Field extends SortClauseHandler
{
    /**
     * Language handler.
     *
     * @var \Ibexa\Contracts\Core\Persistence\Content\Language\Handler
     */
    protected $languageHandler;

    /**
     * Content type handler.
     *
     * @var \Ibexa\Contracts\Core\Persistence\Content\Type\Handler
     */
    protected $contentTypeHandler;

    private LanguagePriorityConditionBuilder $languagePriorityConditionBuilder;

    public function __construct(
        Connection $connection,
        LanguageHandler $languageHandler,
        ContentTypeHandler $contentTypeHandler,
        LanguagePriorityConditionBuilder $languagePriorityConditionBuilder
    ) {
        parent::__construct($connection);

        $this->languageHandler = $languageHandler;
        $this->contentTypeHandler = $contentTypeHandler;
        $this->languagePriorityConditionBuilder = $languagePriorityConditionBuilder;
    }

    /**
     * Check if this sort clause handler accepts to handle the given sort clause.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause $sortClause
     *
     * @return bool
     */
    public function accept(SortClause $sortClause)
    {
        return $sortClause instanceof SortClause\Field;
    }

    /**
     * Apply selects to the query.
     *
     * Returns the name of the (aliased) column, which information should be
     * used for sorting.
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause $sortClause
     * @param int $number
     *
     * @return array
     */
    public function applySelect(
        QueryBuilder $query,
        SortClause $sortClause,
        int $number
    ): array {
        $query
            ->addSelect(
                sprintf(
                    '%s AS %s',
                    $query->expr()->isNotNull(
                        $this->getSortTableName($number) . '.sort_key_int'
                    ),
                    $column1 = $this->getSortColumnName($number . '_null')
                ),
                sprintf(
                    '%s AS %s',
                    $query->expr()->isNotNull(
                        $this->getSortTableName($number) . '.sort_key_string'
                    ),
                    $column2 = $this->getSortColumnName($number . '_bis_null')
                ),
                sprintf(
                    '%s AS %s',
                    $this->getSortTableName($number) . '.sort_key_int',
                    $column3 = $this->getSortColumnName($number)
                ),
                sprintf(
                    '%s AS %s',
                    $this->getSortTableName($number) . '.sort_key_string',
                    $column4 = $this->getSortColumnName($number . '_bis')
                )
            );

        return [$column1, $column2, $column3, $column4];
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

        $tableAlias = $this->connection->quoteIdentifier($table);
        $query
            ->leftJoin(
                'c',
                Gateway::CONTENT_FIELD_TABLE,
                $tableAlias,
                $query->expr()->and(
                    $query->expr()->eq(
                        $query->createNamedParameter(
                            $fieldDefinitionId,
                            ParameterType::INTEGER
                        ),
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
                    $this->getFieldCondition($query, $languageSettings, $table)
                )
            );
    }

    protected function getFieldCondition(
        QueryBuilder $query,
        array $languageSettings,
        $fieldTableName
    ) {
        return $this->languagePriorityConditionBuilder->buildCondition(
            $query,
            $languageSettings,
            $fieldTableName . '.language_id'
        );
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Core\Persistence\Doctrine\JoinedTablesTracker;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\LanguagePriorityConditionBuilder;

/**
 * Base criterion handler for field criteria.
 */
abstract class FieldBase extends CriterionHandler
{
    /**
     * Content type handler.
     *
     * @var \Ibexa\Contracts\Core\Persistence\Content\Type\Handler
     */
    protected $contentTypeHandler;

    /**
     * Language handler.
     *
     * @var \Ibexa\Contracts\Core\Persistence\Content\Language\Handler
     */
    protected $languageHandler;

    private LanguagePriorityConditionBuilder $languagePriorityConditionBuilder;

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct(
        Connection $connection,
        ContentTypeHandler $contentTypeHandler,
        LanguageHandler $languageHandler,
        JoinedTablesTracker $joinedTablesTracker,
        LanguagePriorityConditionBuilder $languagePriorityConditionBuilder
    ) {
        parent::__construct($connection, $joinedTablesTracker);

        $this->contentTypeHandler = $contentTypeHandler;
        $this->languageHandler = $languageHandler;
        $this->languagePriorityConditionBuilder = $languagePriorityConditionBuilder;
    }

    /**
     * Returns a field language join condition for the given $languageSettings.
     *
     * @param array $languageSettings
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    protected function getFieldCondition(QueryBuilder $query, array $languageSettings): string
    {
        return $this->languagePriorityConditionBuilder->buildCondition(
            $query,
            $languageSettings,
            'f_def.language_id'
        );
    }

    /**
     * @param array $languageSettings
     * @param array $fieldWhereExpressions
     * @param array $fieldsInformation
     *
     * @return string
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    protected function getInExpressionWithFieldConditions(
        QueryBuilder $query,
        QueryBuilder $subSelect,
        array $languageSettings,
        array $fieldWhereExpressions,
        array $fieldsInformation
    ): string {
        if (empty($fieldWhereExpressions)) {
            throw new NotImplementedException(
                sprintf(
                    'The following Field Types are not searchable in the Legacy search engine: %s',
                    implode(', ', array_keys($fieldsInformation))
                )
            );
        }

        $expr = $subSelect->expr();
        $subSelect->where(
            $expr->and(
                'f_def.version = c.current_version',
                $expr->or(...$fieldWhereExpressions),
                // pass main Query Builder to set query parameters
                $this->getFieldCondition($query, $languageSettings)
            )
        );

        return $query->expr()->in(
            'c.id',
            $subSelect->getSQL()
        );
    }
}

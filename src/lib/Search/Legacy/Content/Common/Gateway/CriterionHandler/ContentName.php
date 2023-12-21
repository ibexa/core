<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Core\Persistence\TransformationProcessor;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

final class ContentName extends CriterionHandler
{
    private const CONTENTOBJECT_NAME_ALIAS = 'ezc_n';
    private const CONTENTOBJECT_ALIAS = 'c';

    private TransformationProcessor $transformationProcessor;

    public function __construct(
        Connection $connection,
        TransformationProcessor $transformationProcessor
    ) {
        parent::__construct($connection);

        $this->transformationProcessor = $transformationProcessor;
    }

    public function accept(Criterion $criterion): bool
    {
        return $criterion instanceof Criterion\ContentName
            && $criterion->operator === Criterion\Operator::LIKE;
    }

    /**
     * @param array{
     *     languages: array<string>,
     *     useAlwaysAvailable: bool,
     *  } $languageSettings
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        Criterion $criterion,
        array $languageSettings
    ): string {
        $subQuery = $this->connection->createQueryBuilder();
        $subQuery
            ->select('contentobject_id')
            ->distinct()
            ->from('ezcontentobject_name', self::CONTENTOBJECT_NAME_ALIAS)
            ->innerJoin(
                self::CONTENTOBJECT_NAME_ALIAS,
                'ezcontentobject',
                self::CONTENTOBJECT_ALIAS,
                $this->getInnerJoinCondition()
            )
            ->andWhere(
                $queryBuilder->expr()->like(
                    $this->toLowerCase(self::CONTENTOBJECT_NAME_ALIAS . '.name'),
                    $queryBuilder->createNamedParameter(
                        $this->prepareValue($criterion)
                    )
                )
            );

        if (!empty($languageSettings['languages'])) {
            $this->addLanguageConditionToSubQuery(
                $subQuery,
                $queryBuilder,
                $languageSettings['languages']
            );
        }

        return $queryBuilder->expr()->in(
            self::CONTENTOBJECT_ALIAS . '.id',
            $subQuery->getSQL()
        );
    }

    private function getInnerJoinCondition(): string
    {
        return sprintf(
            '(%s = %s AND %s = %s)',
            self::CONTENTOBJECT_NAME_ALIAS . '.contentobject_id',
            self::CONTENTOBJECT_ALIAS . '.id',
            self::CONTENTOBJECT_NAME_ALIAS . '.content_version',
            self::CONTENTOBJECT_ALIAS . '.current_version',
        );
    }

    /**
     * @param array<string> $languages
     */
    private function addLanguageConditionToSubQuery(
        QueryBuilder $subQuery,
        QueryBuilder $queryBuilder,
        array $languages
    ): void {
        $subQuery
            ->andWhere(
                $queryBuilder->expr()->in(
                    $this->toLowerCase(self::CONTENTOBJECT_NAME_ALIAS . '.content_translation'),
                    $this->toLowerCase(
                        $queryBuilder->createNamedParameter(
                            $languages,
                            Connection::PARAM_STR_ARRAY
                        )
                    ),
                )
            );
    }

    private function prepareValue(Criterion $criterion): string
    {
        /** @var string $value */
        $value = $criterion->value;

        return str_replace(
            '*',
            '%',
            addcslashes(
                $this->transformationProcessor->transformByGroup(
                    $value,
                    'lowercase'
                ),
                '%_'
            )
        );
    }

    private function toLowerCase(string $value): string
    {
        return sprintf(
            'LOWER(%s)',
            $value
        );
    }
}

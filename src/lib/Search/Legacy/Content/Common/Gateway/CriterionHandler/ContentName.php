<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

/**
 * @internal
 */
final class ContentName extends CriterionHandler
{
    private const CONTENTOBJECT_NAME_ALIAS = 'ezc_n';
    private const CONTENTOBJECT_ALIAS = 'c';

    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\ContentName
            && $criterion->operator === Criterion\Operator::LIKE;
    }

    /**
     * @param array{
     *     languages: array<string>,
     *     useAlwaysAvailable: bool,
     *  } $languageSettings
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentName $criterion
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ): string {
        $subQuery = $this->connection->createQueryBuilder();
        $subQuery
            ->select('1')
            ->from(Gateway::CONTENT_NAME_TABLE, self::CONTENTOBJECT_NAME_ALIAS)
            ->andWhere(
                $queryBuilder->expr()->eq(
                    self::CONTENTOBJECT_NAME_ALIAS . '.contentobject_id',
                    self::CONTENTOBJECT_ALIAS . '.id'
                ),
                $queryBuilder->expr()->eq(
                    self::CONTENTOBJECT_NAME_ALIAS . '.content_version',
                    self::CONTENTOBJECT_ALIAS . '.current_version'
                ),
                $queryBuilder->expr()->like(
                    $this->toLowerCase(self::CONTENTOBJECT_NAME_ALIAS . '.name'),
                    $this->toLowerCase(
                        $queryBuilder->createNamedParameter(
                            $this->prepareValue($criterion)
                        )
                    )
                )
            );

        return sprintf(
            'EXISTS (%s)',
            $subQuery->getSQL()
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
                $value,
                '%_'
            )
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function toLowerCase(string $value): string
    {
        return $this->connection->getDatabasePlatform()->getLowerExpression($value);
    }
}

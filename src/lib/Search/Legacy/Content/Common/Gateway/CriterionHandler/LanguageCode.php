<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Doctrine\JoinedTablesTracker;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

/**
 * LanguageCode criterion handler.
 */
class LanguageCode extends CriterionHandler
{
    /** @var \Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator */
    private $maskGenerator;

    public function __construct(Connection $connection, MaskGenerator $maskGenerator, JoinedTablesTracker $joinedTablesTracker)
    {
        parent::__construct($connection, $joinedTablesTracker);

        $this->maskGenerator = $maskGenerator;
    }

    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\LanguageCode;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LanguageCode $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        /* @var $criterion \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LanguageCode */
        $expr = $queryBuilder->expr();
        $mask = $this->maskGenerator->generateLanguageMaskFromLanguageCodes($criterion->value);
        $languageIds = $this->maskGenerator->extractLanguageIdsFromMask($mask);

        $translationSubQuery = $this->connection->createQueryBuilder();
        $translationSubQuery
            ->select('1')
            ->from('ibexa_content_translation', 'ct')
            ->where(
                $translationSubQuery->expr()->and(
                    'ct.content_id = c.id',
                    $translationSubQuery->expr()->in(
                        'ct.language_id',
                        $queryBuilder->createNamedParameter($languageIds, ArrayParameterType::INTEGER)
                    )
                )
            );

        $condition = sprintf('EXISTS (%s)', $translationSubQuery->getSQL());

        if ($criterion->matchAlwaysAvailable) {
            return $expr->or($condition, $expr->eq('c.always_available', 1));
        }

        return $condition;
    }
}

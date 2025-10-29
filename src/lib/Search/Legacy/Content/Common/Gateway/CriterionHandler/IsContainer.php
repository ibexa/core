<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway as ContentTypeGateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

final class IsContainer extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\IsContainer;
    }

    /**
     * @phpstan-param array{languages: string[]} $languageSettings
     *
     * @param Criterion\IsContainer $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        /** @var array{bool} $criterionValue */
        $criterionValue = $criterion->value;
        $isContainer = reset($criterionValue);

        $subSelect = $this->connection->createQueryBuilder();
        $subSelect
            ->select(
                'id'
            )->from(
                ContentTypeGateway::CONTENT_TYPE_TABLE
            )->where(
                $queryBuilder->expr()->eq(
                    'is_container',
                    $queryBuilder->createNamedParameter((int)$isContainer, ParameterType::INTEGER)
                )
            );

        return $queryBuilder->expr()->in(
            'c.content_type_id',
            $subSelect->getSQL()
        );
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Content;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LanguageCode;
use Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway;

/**
 * Content Language Code Criterion visitor query builder.
 *
 * @see \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LanguageCode
 *
 * @internal for internal use by Repository Filtering
 */
final class LanguageCodeQueryBuilder implements CriterionQueryBuilder
{
    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof LanguageCode;
    }

    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): ?string {
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LanguageCode $criterion */
        $queryBuilder
            ->joinOnce(
                'version',
                'ibexa_content_version_translation',
                'version_translation',
                'version_translation.content_version_id = version.id'
            )
            ->joinOnce(
                'version_translation',
                Gateway::CONTENT_LANGUAGE_TABLE,
                'language',
                'language.id = version_translation.language_id'
            );

        // at this point $criterion->value is guaranteed to be an array
        $expr = $queryBuilder->expr()->in(
            'language.locale',
            $queryBuilder->createNamedParameter(
                $criterion->value,
                ArrayParameterType::STRING
            )
        );

        if ($criterion->matchAlwaysAvailable) {
            $expr = (string)$queryBuilder->expr()->or(
                $expr,
                $queryBuilder->expr()->eq(
                    'version.always_available',
                    $queryBuilder->createNamedParameter(true, ParameterType::BOOLEAN)
                )
            );
        }

        return $expr;
    }
}

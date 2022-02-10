<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection;

/**
 * "Enum" for the Symfony Service tag names provided by the Extension.
 */
class ServiceTags
{
    /**
     * Auto-configured tag name for
     * {@see \Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder}.
     */
    public const FILTERING_CRITERION_QUERY_BUILDER = 'ibexa.filter.criterion.query.builder';

    /**
     * Auto-configured tag name for
     * {@see \Ibexa\Contracts\Core\Repository\Values\Filter\SortClauseQueryBuilder}.
     */
    public const FILTERING_SORT_CLAUSE_QUERY_BUILDER = 'ibexa.filter.sort_clause.query.builder';
}

class_alias(ServiceTags::class, 'eZ\Bundle\EzPublishCoreBundle\DependencyInjection\ServiceTags');

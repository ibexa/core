<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Content\Type;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

/**
 * @template TCriterion of \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface
 */
interface CriterionHandlerInterface
{
    /**
     * @phpstan-param TCriterion $criterion
     */
    public function supports(CriterionInterface $criterion): bool;

    /**
     * @phpstan-param TCriterion $criterion
     *
     * @return string|CompositeExpression
     */
    public function apply(
        CriterionVisitor $criterionVisitor,
        QueryBuilder $qb,
        CriterionInterface $criterion
    );
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use function Ibexa\PolyfillPhp82\iterator_to_array;

final class CriterionVisitor
{
    /**
     * @var array<\Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionQueryBuilder\CriterionQueryBuilderInterface>
     */
    private array $criterionQueryBuilders;

    public function __construct(iterable $criterionQueryBuilders)
    {
        $this->criterionQueryBuilders = iterator_to_array($criterionQueryBuilders);
    }

    /**
     * @return \Doctrine\Common\Collections\Expr\CompositeExpression|string
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException if there's no builder for a criterion
     */
    public function visitCriteria(
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion
    ) {
        foreach ($this->criterionQueryBuilders as $criterionQueryBuilder) {
            if ($criterionQueryBuilder->supports($criterion)) {
                return $criterionQueryBuilder->buildQueryConstraint(
                    $this,
                    $queryBuilder,
                    $criterion
                );
            }
        }

        throw new NotImplementedException(
            sprintf(
                'There is no Criterion Query Builder for %s Criterion',
                get_class($criterion)
            )
        );
    }
}

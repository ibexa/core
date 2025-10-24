<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Type\CriterionHandlerInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;

final class CriterionVisitor
{
    /** @var iterable<CriterionHandlerInterface<CriterionInterface>> */
    private iterable $criterionHandlers;

    /**
     * @param iterable<CriterionHandlerInterface<CriterionInterface>> $criterionHandlers
     */
    public function __construct(iterable $criterionHandlers)
    {
        $this->criterionHandlers = $criterionHandlers;
    }

    /**
     * @return CompositeExpression|string
     *
     * @throws NotImplementedException if there's no builder for a criterion
     */
    public function visitCriteria(
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion
    ) {
        foreach ($this->criterionHandlers as $criterionHandler) {
            if ($criterionHandler->supports($criterion)) {
                return $criterionHandler->apply(
                    $this,
                    $queryBuilder,
                    $criterion
                );
            }
        }

        throw new NotImplementedException(
            sprintf(
                'There is no Criterion Handler for %s Criterion',
                get_class($criterion)
            )
        );
    }
}

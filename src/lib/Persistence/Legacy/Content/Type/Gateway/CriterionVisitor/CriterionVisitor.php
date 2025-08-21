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
     * @var array<int, \Ibexa\Contracts\Core\Persistence\Content\Type\CriterionHandlerInterface<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface>>
     */
    private array $criterionHandlers;

    /**
     * @param iterable<\Ibexa\Contracts\Core\Persistence\Content\Type\CriterionHandlerInterface<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface>> $criterionHandlers
     */
    public function __construct(iterable $criterionHandlers)
    {
        $this->criterionHandlers = iterator_to_array($criterionHandlers);
    }

    /**
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression|string
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException if there's no builder for a criterion
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

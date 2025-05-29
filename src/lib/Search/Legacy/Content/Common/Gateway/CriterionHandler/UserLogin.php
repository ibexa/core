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
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\User\Gateway;
use Ibexa\Core\Persistence\TransformationProcessor;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

class UserLogin extends CriterionHandler
{
    /** @var \Ibexa\Core\Persistence\TransformationProcessor */
    private $transformationProcessor;

    public function __construct(
        Connection $connection,
        TransformationProcessor $transformationProcessor
    ) {
        parent::__construct($connection);

        $this->transformationProcessor = $transformationProcessor;
    }

    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\UserLogin;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\UserLogin $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $expr = $queryBuilder->expr();
        if (Criterion\Operator::LIKE === $criterion->operator) {
            $expression = $expr->like(
                't1.login',
                $queryBuilder->createNamedParameter(
                    str_replace(
                        '*',
                        '%',
                        addcslashes(
                            $this->transformationProcessor->transformByGroup(
                                $criterion->value,
                                'lowercase'
                            ),
                            '%_'
                        )
                    )
                )
            );
        } else {
            $value = (array)$criterion->value;
            $expression = $expr->in(
                't1.login',
                $queryBuilder->createNamedParameter($value, Connection::PARAM_STR_ARRAY)
            );
        }

        $subSelect = $this->connection->createQueryBuilder();
        $subSelect
            ->select('t1.contentobject_id')
            ->from(Gateway::USER_TABLE, 't1')
            ->where($expression);

        return $expr->in(
            'c.id',
            $subSelect->getSQL()
        );
    }
}

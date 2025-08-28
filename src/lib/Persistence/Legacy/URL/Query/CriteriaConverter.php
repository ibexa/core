<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Persistence\Legacy\URL\Query;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;
use Traversable;

class CriteriaConverter
{
    /**
     * Criterion handlers.
     *
     * @var iterable<\Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler>
     */
    protected iterable $handlers;

    /**
     * Construct from an optional array of Criterion handlers.
     *
     * @param iterable<\Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler> $handlers
     */
    public function __construct(iterable $handlers = [])
    {
        $this->handlers = $handlers;
    }

    /**
     * @deprecated The "%s" method is deprecated. Use a service definition tag "ibexa.storage.legacy.url.criterion.handler" instead.
     */
    public function addHandler(CriterionHandler $handler)
    {
        trigger_deprecation(
            'ibexa/core',
            '4.6.24',
            'The "%s" method is deprecated. Use a service definition tag ("%s") instead.',
            __METHOD__,
            implode('", "', [
                'ibexa.storage.legacy.url.criterion.handler',
            ]),
        );

        if ($this->handlers instanceof Traversable) {
            $this->handlers = iterator_to_array($this->handlers);
        }

        $this->handlers[] = $handler;
    }

    /**
     * Generic converter of criteria into query fragments.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException if Criterion is not applicable to its target
     *
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression|string
     */
    public function convertCriteria(QueryBuilder $queryBuilder, Criterion $criterion)
    {
        foreach ($this->handlers as $handler) {
            if ($handler->accept($criterion)) {
                return $handler->handle($this, $queryBuilder, $criterion);
            }
        }

        throw new NotImplementedException(
            'No visitor available for: ' . get_class($criterion)
        );
    }
}

class_alias(CriteriaConverter::class, 'eZ\Publish\Core\Persistence\Legacy\URL\Query\CriteriaConverter');

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Traversable;

/**
 * Content locator gateway implementation using the DoctrineDatabase.
 */
class CriteriaConverter
{
    /**
     * Criterion handlers.
     *
     * @var \Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler[]
     */
    protected iterable $handlers;

    /**
     * Construct from an optional array of Criterion handlers.
     *
     * @param \Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler[] $handlers
     */
    public function __construct(iterable $handlers = [])
    {
        $this->handlers = $handlers;
    }

    /**
     * @deprecated The "%s" method is deprecated. Use a service definition tag instead (one of
     *      "ibexa.search.legacy.gateway.criterion_handler.content",
     *      "ibexa.search.legacy.gateway.criterion_handler.location",
     *      "ibexa.search.legacy.trash.gateway.criterion.handler").
     */
    public function addHandler(CriterionHandler $handler)
    {
        trigger_deprecation(
            'ibexa/core',
            '4.6.24',
            'The "%s" method is deprecated. Use a service definition tag instead (one of "%s").',
            __METHOD__,
            implode('", "', [
                'ibexa.search.legacy.gateway.criterion_handler.content',
                'ibexa.search.legacy.gateway.criterion_handler.location',
                'ibexa.search.legacy.trash.gateway.criterion.handler',
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
     * @param array $languageSettings
     *
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression|string
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function convertCriteria(
        QueryBuilder $query,
        Criterion $criterion,
        array $languageSettings
    ) {
        foreach ($this->handlers as $handler) {
            if ($handler->accept($criterion)) {
                return $handler->handle($this, $query, $criterion, $languageSettings);
            }
        }

        throw new NotImplementedException(
            'No visitor available for: ' . get_class($criterion) . ' with operator ' . $criterion->operator
        );
    }
}

class_alias(CriteriaConverter::class, 'eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter');

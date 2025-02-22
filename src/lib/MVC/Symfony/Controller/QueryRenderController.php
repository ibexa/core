<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Controller;

use Ibexa\Core\MVC\Symfony\View\QueryView;
use Ibexa\Core\Pagination\Pagerfanta\AdapterFactory\SearchHitAdapterFactoryInterface;
use Ibexa\Core\Pagination\Pagerfanta\Pagerfanta;
use Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter;
use Ibexa\Core\Query\QueryFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Controller used internally by ez_query_*_render and ez_query_*_render_* functions.
 *
 * @internal
 *
 * @phpstan-type TOptionsArray array<string, mixed>
 */
final class QueryRenderController
{
    private QueryFactoryInterface $queryFactory;

    private SearchHitAdapterFactoryInterface $searchHitAdapterFactory;

    public function __construct(
        QueryFactoryInterface $queryFactory,
        SearchHitAdapterFactoryInterface $searchHitAdapterFactory
    ) {
        $this->queryFactory = $queryFactory;
        $this->searchHitAdapterFactory = $searchHitAdapterFactory;
    }

    /**
     * @phpstan-param TOptionsArray $options
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function renderQuery(Request $request, array $options): QueryView
    {
        $options = $this->resolveOptions($options);

        $results = new Pagerfanta($this->getAdapter($options));
        if ($options['pagination']['enabled']) {
            $currentPage = $request->get($options['pagination']['page_param'], 1);

            $results->setAllowOutOfRangePages(true);
            $results->setMaxPerPage($options['pagination']['limit']);
            $results->setCurrentPage($currentPage);
        }

        return $this->createQueryView(
            $options['template'],
            $options['query']['assign_results_to'],
            $results
        );
    }

    /**
     * @phpstan-param TOptionsArray $options
     *
     * @phpstan-return TOptionsArray
     */
    private function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();

        $resolver->setDefault('query', static function (OptionsResolver $resolver): void {
            $resolver->setDefaults([
                'parameters' => [],
                'assign_results_to' => 'items',
            ]);

            $resolver->setRequired(['query_type']);
            $resolver->setAllowedTypes('query_type', 'string');
            $resolver->setAllowedTypes('parameters', 'array');
            $resolver->setAllowedTypes('assign_results_to', 'string');
        });

        $resolver->setDefault('pagination', static function (OptionsResolver $resolver): void {
            $resolver->setDefaults([
                'enabled' => true,
                'limit' => 10,
                'page_param' => 'page',
            ]);

            $resolver->setAllowedTypes('enabled', 'boolean');
            $resolver->setAllowedTypes('limit', 'int');
            $resolver->setAllowedTypes('page_param', 'string');
        });

        $resolver->setRequired('template');
        $resolver->setAllowedTypes('template', 'string');
        $resolver->setAllowedTypes('query', 'array');

        return $resolver->resolve($options);
    }

    /**
     * @phpstan-param TOptionsArray $options
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function getAdapter(array $options): SearchResultAdapter
    {
        $query = $this->queryFactory->create(
            $options['query']['query_type'],
            $options['query']['parameters']
        );

        if ($options['pagination']['enabled']) {
            return $this->searchHitAdapterFactory->createAdapter($query);
        }

        return $this->searchHitAdapterFactory->createFixedAdapter($query);
    }

    private function createQueryView(string $template, string $assignResultsTo, iterable $results): QueryView
    {
        $view = new QueryView();
        $view->setTemplateIdentifier($template);
        $view->addParameters([
            $assignResultsTo => $results,
        ]);

        return $view;
    }
}

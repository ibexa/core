<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Controller;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\MVC\Symfony\Controller\QueryRenderController;
use Ibexa\Core\MVC\Symfony\View\QueryView;
use Ibexa\Core\Pagination\Pagerfanta\AdapterFactory\SearchHitAdapterFactoryInterface;
use Ibexa\Core\Pagination\Pagerfanta\Pagerfanta;
use Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter;
use Ibexa\Core\Query\QueryFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Controller\QueryRenderController
 *
 * @phpstan-import-type TOptionsArray from \Ibexa\Core\MVC\Symfony\Controller\QueryRenderController
 *
 * @template TSearchHitValueObject of \Ibexa\Contracts\Core\Repository\Values\ValueObject
 */
final class QueryRenderControllerTest extends TestCase
{
    private const int EXAMPLE_CURRENT_PAGE = 3;
    private const int EXAMPLE_MAX_PER_PAGE = 100;

    /** @phpstan-var TOptionsArray */
    private const array MIN_OPTIONS = [
        'query' => [
            'query_type' => 'ExampleQuery',
        ],
        'template' => 'example.html.twig',
    ];

    /** @phpstan-var TOptionsArray */
    private const array ALL_OPTIONS = [
        'query' => [
            'query_type' => 'ExampleQuery',
            'parameters' => [
                'foo' => 'foo',
                'bar' => 'bar',
                'baz' => 'baz',
            ],
            'assign_results_to' => 'results',
        ],
        'template' => 'example.html.twig',
        'pagination' => [
            'enabled' => true,
            'limit' => self::EXAMPLE_MAX_PER_PAGE,
            'page_param' => 'p',
        ],
    ];

    private QueryFactoryInterface & MockObject $queryFactory;

    private SearchHitAdapterFactoryInterface & MockObject $searchHitAdapterFactory;

    private QueryRenderController $controller;

    protected function setUp(): void
    {
        $this->queryFactory = $this->createMock(QueryFactoryInterface::class);
        $this->searchHitAdapterFactory = $this->createMock(SearchHitAdapterFactoryInterface::class);

        $this->controller = new QueryRenderController(
            $this->queryFactory,
            $this->searchHitAdapterFactory
        );
    }

    public function testRenderQueryWithMinOptions(): void
    {
        $adapter = $this->configureMocks(self::MIN_OPTIONS);

        $items = new Pagerfanta($adapter);
        $items->setAllowOutOfRangePages(true);

        $this->assertRenderQueryResult(
            new QueryView('example.html.twig', [
                'items' => $items,
            ]),
            self::MIN_OPTIONS
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testRenderQueryWithAllOptions(): void
    {
        $adapter = $this->configureMocks(self::ALL_OPTIONS);

        $items = new Pagerfanta($adapter);
        $items->setAllowOutOfRangePages(true);
        $items->setCurrentPage(self::EXAMPLE_CURRENT_PAGE);
        $items->setMaxPerPage(self::EXAMPLE_MAX_PER_PAGE);

        $this->assertRenderQueryResult(
            new QueryView('example.html.twig', [
                'results' => $items,
            ]),
            self::ALL_OPTIONS,
            new Request(['p' => self::EXAMPLE_CURRENT_PAGE])
        );
    }

    /**
     * @phpstan-param TOptionsArray $options
     *
     * @template TItem
     *
     * @phpstan-return \Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter<TItem>
     */
    private function configureMocks(array $options): SearchResultAdapter
    {
        $query = new Query();

        $this->queryFactory
            ->method('create')
            ->with(
                $options['query']['query_type'],
                $options['query']['parameters'] ?? []
            )
            ->willReturn($query);

        $adapter = $this->createMock(SearchResultAdapter::class);

        $this->searchHitAdapterFactory
            ->method('createAdapter')
            ->with($query)
            ->willReturn($adapter);

        return $adapter;
    }

    /**
     * @phpstan-param TOptionsArray $options
     */
    private function assertRenderQueryResult(
        QueryView $expectedView,
        array $options,
        ?Request $request = null
    ): void {
        self::assertEquals(
            $expectedView,
            $this->controller->renderQuery(
                $request ?? new Request(),
                $options
            )
        );
    }
}

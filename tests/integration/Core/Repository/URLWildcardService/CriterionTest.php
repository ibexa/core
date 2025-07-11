<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\URLWildcardService;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\SearchResult;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\URLWildcardQuery;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion as CriterionURL;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Test case criterion for URLWildcard.
 *
 * @covers \Ibexa\Contracts\Core\Repository\URLWildcardService
 *
 * @group url-wildcard
 */
class CriterionTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $repository = $this->getRepository();
        $urlWildcardService = $repository->getURLWildcardService();

        foreach ($this->getUrlWildcards() as $urlWildcard) {
            $urlWildcardService->create($urlWildcard['sourceUrl'], $urlWildcard['destinationUrl'], $urlWildcard['forward']);
        }
    }

    protected function findUrlWildcards(
        URLWildcardQuery $query,
        ?int $expectedTotalCount
    ): SearchResult {
        $repository = $this->getRepository();
        $searchResult = $repository->getURLWildcardService()->findUrlWildcards($query);

        self::assertSame($expectedTotalCount, $searchResult->totalCount);
        self::assertCount($expectedTotalCount, $searchResult->items);

        return $searchResult;
    }

    private function getUrlWildcards(bool $isAbsolute = false): array
    {
        $prefix = $isAbsolute ? '/' : '';

        return [
            [
                'sourceUrl' => $prefix . 'test',
                'destinationUrl' => $prefix . 'content-test',
                'forward' => true,
            ],
            [
                'sourceUrl' => $prefix . 'test test',
                'destinationUrl' => $prefix . 'content test',
                'forward' => true,
            ],
            [
                'sourceUrl' => $prefix . 'ibexa-dxp',
                'destinationUrl' => $prefix . 'ibexa-1-2-3',
                'forward' => true,
            ],
            [
                'sourceUrl' => $prefix . 'nice-url-seo',
                'destinationUrl' => $prefix . '1/2/3/4',
                'forward' => false,
            ],
            [
                'sourceUrl' => $prefix . 'no-forward test url',
                'destinationUrl' => $prefix . 'no/forward test url',
                'forward' => false,
            ],
            [
                'sourceUrl' => $prefix . 'Twitter',
                'destinationUrl' => $prefix . 'a/b/c',
                'forward' => false,
            ],
            [
                'sourceUrl' => $prefix . 'facebook',
                'destinationUrl' => $prefix . '2/3/facebook',
                'forward' => true,
            ],
        ];
    }

    public function testMatchAll(): void
    {
        $query = new URLWildcardQuery();
        $query->filter = new Criterion\MatchAll();

        $expectedWildcardUrls = $this->getUrlWildcards(true);
        $searchResult = $this->findUrlWildcards($query, count($expectedWildcardUrls));

        foreach ($searchResult->items as $item) {
            $wildcard = [
                'sourceUrl' => $item->sourceUrl,
                'destinationUrl' => $item->destinationUrl,
                'forward' => $item->forward,
            ];

            self::assertContains($wildcard, $expectedWildcardUrls);
        }
    }

    public function testMatchNone(): void
    {
        $query = new URLWildcardQuery();
        $query->filter = new Criterion\MatchNone();

        $this->findUrlWildcards($query, 0);
    }

    public function testSourceUrl(): void
    {
        $expectedWildcardUrls = [
            '/test',
            '/test test',
            '/no-forward test url',
        ];

        $query = new URLWildcardQuery();
        $query->filter = new Criterion\SourceUrl('test');

        $searchResult = $this->findUrlWildcards($query, count($expectedWildcardUrls));
        $this->checkWildcardUrl($searchResult->items, $expectedWildcardUrls);
    }

    public function testSourceUrlWithSpace(): void
    {
        $expectedWildcardUrls = [
            '/test test',
            '/no-forward test url',
        ];

        $query = new URLWildcardQuery();
        $query->filter = new Criterion\SourceUrl(' test');

        $searchResult = $this->findUrlWildcards($query, count($expectedWildcardUrls));
        $this->checkWildcardUrl($searchResult->items, $expectedWildcardUrls);
    }

    public function testDestinationUrl(): void
    {
        $expectedWildcardUrls = [
            '/content-test',
            '/content test',
            '/no/forward test url',
        ];

        $query = new URLWildcardQuery();
        $query->filter = new Criterion\DestinationUrl('test');

        $searchResult = $this->findUrlWildcards($query, count($expectedWildcardUrls));
        $this->checkWildcardUrl($searchResult->items, $expectedWildcardUrls, false);
    }

    public function testDestinationUrlWithSpace(): void
    {
        $expectedWildcardUrls = [
            '/content test',
            '/no/forward test url',
        ];

        $query = new URLWildcardQuery();
        $query->filter = new Criterion\DestinationUrl(' test');

        $searchResult = $this->findUrlWildcards($query, count($expectedWildcardUrls));
        $this->checkWildcardUrl($searchResult->items, $expectedWildcardUrls, false);
    }

    public function testTypeForward(): void
    {
        $expectedWildcardUrls = [
            '/test',
            '/test test',
            '/ibexa-dxp',
            '/facebook',
        ];

        $query = new URLWildcardQuery();
        $query->filter = new Criterion\Type(true);

        $searchResult = $this->findUrlWildcards($query, count($expectedWildcardUrls));
        $this->checkWildcardUrl($searchResult->items, $expectedWildcardUrls);
    }

    public function testTypeNoForward(): void
    {
        $expectedWildcardUrls = [
            '/nice-url-seo',
            '/no-forward test url',
            '/Twitter',
        ];

        $query = new URLWildcardQuery();
        $query->filter = new Criterion\Type(false);

        $searchResult = $this->findUrlWildcards($query, count($expectedWildcardUrls));
        $this->checkWildcardUrl($searchResult->items, $expectedWildcardUrls);
    }

    public function testSourceAndDestination(): void
    {
        $search = 'test';
        $expectedWildcardUrlsSource = [
            '/test',
            '/test test',
            '/no-forward test url',
        ];

        $expectedWildcardUrlsDestination = [
            '/content-test',
            '/content test',
            '/no/forward test url',
        ];

        $query = new URLWildcardQuery();
        $query->filter = new Criterion\LogicalAnd([
            new Criterion\SourceUrl($search),
            new Criterion\DestinationUrl($search),
        ]);

        $searchResult = $this->findUrlWildcards($query, count($expectedWildcardUrlsSource));

        $this->checkWildcardUrl($searchResult->items, $expectedWildcardUrlsSource);
        $this->checkWildcardUrl($searchResult->items, $expectedWildcardUrlsDestination, false);
    }

    public function testLogicalInvalidCriterion(): void
    {
        $this->expectException(InvalidCriterionArgumentException::class);
        $this->expectExceptionMessage(
            'You provided Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion\VisibleOnly ' .
            "at index '1', but only instances of " .
            "'Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion' are accepted"
        );
        $query = new URLWildcardQuery();
        $query->filter = new Criterion\LogicalAnd([
            new Criterion\SourceUrl('test'),
            new CriterionURL\VisibleOnly(),
        ]);
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard[] $items
     * @param string[] $expectedWildcardUrls
     */
    protected function checkWildcardUrl(array $items, array $expectedWildcardUrls, bool $sourceUrl = true): void
    {
        foreach ($items as $item) {
            if ($sourceUrl) {
                self::assertContains($item->sourceUrl, $expectedWildcardUrls);
            } else {
                self::assertContains($item->destinationUrl, $expectedWildcardUrls);
            }
        }
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\EmbeddingQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query as BaseQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Embedding;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\ContentName;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Spellcheck;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmbeddingQueryBuilderTest extends TestCase
{
    public function testBuilderSetsAllowedProperties(): void
    {
        $embedding = $this->createMock(Embedding::class);
        $aggregations = [$this->createMock(Aggregation::class),  $this->createMock(Aggregation::class)];

        $builder = EmbeddingQueryBuilder::create()
            ->withEmbedding($embedding)
            ->setLimit(10)
            ->setOffset(5)
            ->setPerformCount(true)
            ->setAggregations($aggregations);

        $query = $builder->build();

        $this->assertSame(
            $embedding,
            $query->getEmbedding(),
            'Embedding should be set by builder'
        );

        $this->assertEquals(10, $query->getLimit(), 'Limit should be set by builder');
        $this->assertEquals(5, $query->getOffset(), 'Offset should be set by builder');
        $this->assertTrue($query->getPerformCount(), 'PerformCount flag should be true');

        $aggregations = $query->getAggregations();
        $this->assertIsArray($aggregations, 'Aggregations must be array');
        $this->assertCount(2, $aggregations, 'Two aggregations added');
    }

    public function testIsValidReturnsTrueForCleanQuery(): void
    {
        $query = EmbeddingQueryBuilder::create()
            ->withEmbedding($this->createMock(Embedding::class))
            ->build();

        $this->assertTrue($query->isValid());
    }

    public function testSettingSortClausesThenIsValidThrows(): void
    {
        $query = EmbeddingQueryBuilder::create()
            ->withEmbedding($this->createMock(Embedding::class))
            ->build();

        // bypass setter via array-append magic
        $query->sortClauses[] = new ContentName(BaseQuery::SORT_ASC);
        $query->query = $this->createMock(Criterion::class);
        $query->facetBuilders = [$this->createMock(FacetBuilder::class)];
        $query->spellcheck = new Spellcheck('foo');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('EmbeddingQuery may not set [query, sortClauses, facetBuilders, spellcheck].');

        $query->isValid();
    }
}

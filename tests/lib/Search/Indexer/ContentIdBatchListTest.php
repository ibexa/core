<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Indexer;

use ArrayIterator;
use Generator;
use Ibexa\Core\Search\Indexer\ContentIdBatchList;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;
use Traversable;

/**
 * @covers \Ibexa\Core\Search\Indexer\ContentIdBatchList
 */
final class ContentIdBatchListTest extends TestCase
{
    /**
     * @return iterable<string, array{iterable<int, array<int>>, int, array<int, array<int>>}>
     */
    public function getDataForTestGetIterator(): iterable
    {
        yield 'generator' => [
            $this->buildGenerator(),
            5,
            [
                [1, 2, 3],
                [4, 5],
            ],
        ];

        yield 'array' => [
            [
                [1, 2],
                [3, 4],
                [5],
            ],
            5,
            [
                [1, 2],
                [3, 4],
                [5],
            ],
        ];

        yield 'Traversable object' => [
            $this->buildTraversableObject(),
            5,
            [
                [1, 2, 3, 4],
                [5],
            ],
        ];

        yield 'empty generator' => [
            $this->buildEmptyGenerator(),
            0,
            [],
        ];
    }

    /**
     * @dataProvider getDataForTestGetIterator
     *
     * @param iterable<int, array<int>> $list
     * @param array<int, array<int>> $expectedBatches
     */
    public function testGetIterator(
        iterable $list,
        int $totalCount,
        array $expectedBatches
    ): void {
        $contentIdBatchList = new ContentIdBatchList($list, $totalCount);
        $unpackedActualBatches = [];
        foreach ($contentIdBatchList as $index => $items) {
            $unpackedActualBatches[$index] = $items;
        }
        self::assertSame($expectedBatches, $unpackedActualBatches);
    }

    public function testGetCount(): void
    {
        $contentIdBatchList = new ContentIdBatchList([[1, 2, 3]], 3);
        self::assertSame(3, $contentIdBatchList->getCount());
    }

    private function buildGenerator(): Generator
    {
        yield [1, 2, 3];
        yield [4, 5];
    }

    private function buildEmptyGenerator(): Generator
    {
        yield from [];
    }

    /**
     * @return Traversable<int, array<int>>
     */
    private function buildTraversableObject(): Traversable
    {
        return new class() implements IteratorAggregate {
            /**
             * @return ArrayIterator<int, array{int, int, int, int}|array{int}>
             */
            public function getIterator(): ArrayIterator
            {
                return new ArrayIterator(
                    [
                        [1, 2, 3, 4],
                        [5],
                    ]
                );
            }
        };
    }
}

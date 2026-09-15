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

#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Core\Search\Indexer\ContentIdBatchList::class)]
final class ContentIdBatchListTest extends TestCase
{
    /**
     * @return iterable<string, array{iterable<int, array<int>>, int, array<int, array<int>>}>
     */
    public static function getDataForTestGetIterator(): iterable
    {
        yield 'generator' => [
            self::buildGenerator(),
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
            self::buildTraversableObject(),
            5,
            [
                [1, 2, 3, 4],
                [5],
            ],
        ];

        yield 'empty generator' => [
            self::buildEmptyGenerator(),
            0,
            [],
        ];
    }

    /**
     * @param iterable<int, array<int>> $list
     * @param array<int, array<int>> $expectedBatches
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getDataForTestGetIterator')]
    public function testGetIterator(iterable $list, int $totalCount, array $expectedBatches): void
    {
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

    private static function buildGenerator(): Generator
    {
        yield [1, 2, 3];
        yield [4, 5];
    }

    private static function buildEmptyGenerator(): \Generator
    {
        yield from [];
    }

    /**
     * @return \Traversable<int, array<int>>
     */
    private static function buildTraversableObject(): Traversable
    {
        return new class() implements IteratorAggregate {
            /**
             * @return \ArrayIterator<int, array{int, int, int, int}|array{int}>
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

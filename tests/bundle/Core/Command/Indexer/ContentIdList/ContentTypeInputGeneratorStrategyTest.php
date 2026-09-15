<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Command\Indexer\ContentIdList;

use Ibexa\Bundle\Core\Command\Indexer\ContentIdList\ContentTypeInputGeneratorStrategy;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentList;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo as CoreVersionInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Bundle\Core\Command\Indexer\ContentIdList\ContentTypeInputGeneratorStrategy::class)]
final class ContentTypeInputGeneratorStrategyTest extends TestCase
{
    /**
     * @param array<int, int[]> $expectedBatches
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getDataForTestGetGenerator')]
    public function testGetGenerator(ContentList $contentList, int $batchSize, array $expectedBatches): void
    {
        $contentServiceMock = $this->createMock(ContentService::class);
        $contentServiceMock->method('find')->willReturn($contentList);

        $repositoryMock = $this->createMock(Repository::class);
        $repositoryMock
            ->method('sudo')
            ->willReturnCallback(
                static fn (callable $callback) => $callback()
            );

        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->method('getOption')->with('content-type')->willReturn(uniqid('type', true));

        $strategy = new ContentTypeInputGeneratorStrategy(
            $contentServiceMock,
            $repositoryMock
        );

        self::assertSame(
            $expectedBatches,
            iterator_to_array($strategy->getBatchList($inputMock, $batchSize))
        );
    }

    /**
     * @return iterable<string, array{\Ibexa\Contracts\Core\Repository\Values\Content\ContentList, int, array<int, int[]>}>
     */
    public static function getDataForTestGetGenerator(): iterable
    {
        yield 'iteration count = 3, items = 10' => [
            self::generateContentList(10),
            3,
            [
                [1, 2, 3],
                [4, 5, 6],
                [7, 8, 9],
                [10],
            ],
        ];

        yield 'iteration count = 6, items = 6' => [
            self::generateContentList(6),
            6,
            [
                [1, 2, 3, 4, 5, 6],
            ],
        ];

        yield 'iteration count = 2, items = 4' => [
            self::generateContentList(4),
            2,
            [
                [1, 2],
                [3, 4],
            ],
        ];

        yield 'iteration count = 10, items = 5' => [
            self::generateContentList(5),
            10,
            [
                [1, 2, 3, 4, 5],
            ],
        ];

        yield 'iteration count = 5, items = 0' => [
            self::generateContentList(0),
            5,
            [],
        ];
    }

    private static function generateContentList(int $totalCount): ContentList
    {
        $contentItems = [];
        for ($i = 0; $i < $totalCount; ++$i) {
            $contentItems[] = self::createContentItemWithId($i + 1);
        }

        return new ContentList($totalCount, $contentItems);
    }

    private static function createContentItemWithId(int $id): Content
    {
        return new Content([
            'versionInfo' => new CoreVersionInfo([
                'contentInfo' => new ContentInfo(['id' => $id]),
            ]),
        ]);
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Command\Indexer\ContentIdList;

use Ibexa\Bundle\Core\Command\Indexer\ContentIdList\ContentTypeInputGeneratorStrategy;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentList;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\Content\Content;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @covers \Ibexa\Bundle\Core\Command\Indexer\ContentIdList\ContentTypeInputGeneratorStrategy
 */
final class ContentTypeInputGeneratorStrategyTest extends TestCase
{
    /**
     * @dataProvider getDataForTestGetGenerator
     *
     * @param array<int, int[]> $expectedBatches
     */
    public function testGetGenerator(
        ContentList $contentList,
        int $batchSize,
        array $expectedBatches
    ): void {
        $contentServiceMock = $this->createMock(ContentService::class);
        $contentServiceMock->method('find')->willReturn($contentList);

        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->method('getOption')->with('content-type')->willReturn(uniqid('type', true));

        $strategy = new ContentTypeInputGeneratorStrategy($contentServiceMock);

        self::assertSame(
            $expectedBatches,
            iterator_to_array($strategy->getBatchList($inputMock, $batchSize))
        );
    }

    /**
     * @return iterable<string, array{ContentList, int, array<int, int[]>}>
     */
    public function getDataForTestGetGenerator(): iterable
    {
        yield 'iteration count = 3, items = 10' => [
            $this->generateContentList(10),
            3,
            [
                [1, 2, 3],
                [4, 5, 6],
                [7, 8, 9],
                [10],
            ],
        ];

        yield 'iteration count = 6, items = 6' => [
            $this->generateContentList(6),
            6,
            [
                [1, 2, 3, 4, 5, 6],
            ],
        ];

        yield 'iteration count = 2, items = 4' => [
            $this->generateContentList(4),
            2,
            [
                [1, 2],
                [3, 4],
            ],
        ];

        yield 'iteration count = 10, items = 5' => [
            $this->generateContentList(5),
            10,
            [
                [1, 2, 3, 4, 5],
            ],
        ];

        yield 'iteration count = 5, items = 0' => [
            $this->generateContentList(0),
            5,
            [],
        ];
    }

    private function generateContentList(int $totalCount): ContentList
    {
        $contentItems = [];
        for ($i = 0; $i < $totalCount; ++$i) {
            $contentItems[] = $this->createContentItemWithIdMock($i + 1);
        }

        return new ContentList($totalCount, $contentItems);
    }

    private function createContentItemWithIdMock(int $id): Content
    {
        $contentItem = $this->createMock(Content::class);
        $contentInfoMock = $this->createMock(ContentInfo::class);
        $contentInfoMock->method('getId')->willReturn($id);
        $versionInfoMock = $this->createMock(VersionInfo::class);
        $versionInfoMock->method('getContentInfo')->willReturn($contentInfoMock);
        $contentItem->method('getVersionInfo')->willReturn($versionInfoMock);

        return $contentItem;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command\Indexer\ContentIdList;

use Generator;
use Ibexa\Bundle\Core\Command\Indexer\ContentIdListGeneratorStrategyInterface;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentList;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Core\Search\Indexer\ContentIdBatchList;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 */
final class ContentTypeInputGeneratorStrategy implements ContentIdListGeneratorStrategyInterface
{
    private ContentService $contentService;

    public function __construct(ContentService $contentService)
    {
        $this->contentService = $contentService;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function getBatchList(InputInterface $input, int $batchSize): ContentIdBatchList
    {
        $contentList = $this->getContentList($input->getOption('content-type'));

        return new ContentIdBatchList(
            $this->buildGenerator($contentList, $batchSize),
            $contentList->getTotalCount(),
        );
    }

    private function buildGenerator(ContentList $contentList, int $batchSize): Generator
    {
        $contentIds = [];
        foreach ($contentList as $content) {
            $contentIds[] = $content->getVersionInfo()->getContentInfo()->getId();
            if (count($contentIds) >= $batchSize) {
                yield $contentIds;
                $contentIds = [];
            }
        }
        if (!empty($contentIds)) {
            yield $contentIds;
        }
    }

    public function shouldPurgeIndex(InputInterface $input): bool
    {
        return false;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function getContentList(string $contentTypeIdentifier): ContentList
    {
        $filter = new Filter();
        $filter
            ->withCriterion(
                new Query\Criterion\ContentTypeIdentifier($contentTypeIdentifier)
            )
        ;

        return $this->contentService->find($filter);
    }
}

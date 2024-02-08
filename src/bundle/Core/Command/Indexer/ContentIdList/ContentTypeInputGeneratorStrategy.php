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
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 */
final class ContentTypeInputGeneratorStrategy implements ContentIdListGeneratorStrategyInterface
{
    private ContentService $contentService;

    /** @var array<string, \Ibexa\Contracts\Core\Repository\Values\Content\ContentList> */
    private static array $inMemoryContentListCache = [];

    public function __construct(ContentService $contentService)
    {
        $this->contentService = $contentService;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function getGenerator(InputInterface $input, int $iterationCount): Generator
    {
        $contentList = $this->getContentList($input->getOption('content-type'));
        $contentIds = [];
        foreach ($contentList as $content) {
            $contentIds[] = $content->getVersionInfo()->getContentInfo()->getId();
            if (count($contentIds) >= $iterationCount) {
                yield $contentIds;
                $contentIds = [];
            }
        }
        if (!empty($contentIds)) {
            yield $contentIds;
        }
    }

    public function shouldPurgeIndex(): bool
    {
        return false;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function getCount(InputInterface $input): int
    {
        return $this->getContentList($input->getOption('content-type'))->getTotalCount();
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function getContentList(string $contentTypeIdentifier): ContentList
    {
        if (isset(self::$inMemoryContentListCache[$contentTypeIdentifier])) {
            return self::$inMemoryContentListCache[$contentTypeIdentifier];
        }

        $filter = new Filter();
        $filter
            ->withCriterion(
                new Query\Criterion\ContentTypeIdentifier($contentTypeIdentifier)
            )
        ;

        self::$inMemoryContentListCache[$contentTypeIdentifier] = $this->contentService->find($filter);

        return self::$inMemoryContentListCache[$contentTypeIdentifier];
    }
}

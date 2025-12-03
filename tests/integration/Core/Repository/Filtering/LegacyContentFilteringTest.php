<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory;
use Ibexa\Tests\Core\Repository\Filtering\TestContentProvider;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;
use Ibexa\Tests\Integration\Core\Repository\Filtering\Fixtures\LegacyLocationSortClause;
use function array_map;
use function iterator_to_array;

/**
 * Integration BC check for legacy location sort clauses wired through the container.
 *
 * @group repository
 */
final class LegacyContentFilteringTest extends BaseTest
{
    private TestContentProvider $contentProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contentProvider = new TestContentProvider($this->getRepository(), $this);
    }

    public function testLegacyLocationSortClause(): void
    {
        $parentFolder = $this->contentProvider->createSharedContentStructure();

        $filter = (new Filter())
            ->withCriterion(
                new Criterion\ParentLocationId($parentFolder->getContentInfo()->getMainLocationId())
            )
            ->andWithCriterion(
                new Criterion\ContentTypeIdentifier('folder')
            )
            ->withSortClause(new LegacyLocationSortClause(Query::SORT_ASC));

        $list = $this->getRepository()->getContentService()->find($filter, []);

        self::assertCount(2, $list);
        $remoteIds = array_map(
            static fn ($content): string => $content->getContentInfo()->remoteId,
            iterator_to_array($list)
        );
        self::assertSame(
            [
                TestContentProvider::CONTENT_REMOTE_IDS['folder1'],
                TestContentProvider::CONTENT_REMOTE_IDS['folder2'],
            ],
            $remoteIds
        );
    }

    protected function getSetupFactory(): SetupFactory
    {
        return new LegacyFilteringSetupFactory();
    }
}

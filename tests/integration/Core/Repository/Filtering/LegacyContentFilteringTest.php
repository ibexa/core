<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering;

use function array_map;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Tests\Integration\Core\Repository\Filtering\Fixtures\LegacyLocationSortClause;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;
use function iterator_to_array;

/**
 * Integration BC check for legacy location sort clauses wired through the container.
 *
 * @group repository
 */
final class LegacyContentFilteringTest extends RepositoryTestCase
{
    public function testLegacyLocationSortClause(): void
    {
        $parentFolder = $this->createFolderWithRemoteId('legacy-parent', 'Legacy Parent');
        $folder1 = $this->createFolderWithRemoteId(
            'legacy-folder-1',
            'Legacy Folder 1',
            (int)$parentFolder->getContentInfo()->getMainLocationId()
        );
        $folder2 = $this->createFolderWithRemoteId(
            'legacy-folder-2',
            'Legacy Folder 2',
            (int)$parentFolder->getContentInfo()->getMainLocationId()
        );

        $filter = (new Filter())
            ->withCriterion(
                new Criterion\ParentLocationId((int)$parentFolder->getContentInfo()->getMainLocationId())
            )
            ->andWithCriterion(
                new Criterion\ContentTypeIdentifier('folder')
            )
            ->withSortClause(new LegacyLocationSortClause(Query::SORT_ASC));

        $contentService = self::getContentService();
        $list = $contentService->find($filter, []);

        self::assertCount(2, $list);
        $remoteIds = array_map(
            static fn ($content): string => $content->getContentInfo()->remoteId,
            iterator_to_array($list)
        );
        self::assertSame(
            [
                $folder1->getContentInfo()->remoteId,
                $folder2->getContentInfo()->remoteId,
            ],
            $remoteIds
        );
    }

    protected static function getKernelClass(): string
    {
        return LegacyTestKernel::class;
    }

    private function createFolderWithRemoteId(
        string $remoteId,
        string $name,
        int $parentLocationId = self::CONTENT_TREE_ROOT_ID
    ): Content {
        $contentService = self::getContentService();
        $contentTypeService = self::getContentTypeService();
        $locationService = self::getLocationService();

        /** @var \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType $folderType */
        $folderType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $createStruct = $contentService->newContentCreateStruct($folderType, 'eng-GB');
        $createStruct->setField('name', $name, 'eng-GB');
        $createStruct->remoteId = $remoteId;

        $locationCreateStruct = $locationService->newLocationCreateStruct($parentLocationId);

        $draft = $contentService->createContent(
            $createStruct,
            [$locationCreateStruct]
        );

        return $contentService->publishVersion($draft->versionInfo);
    }
}

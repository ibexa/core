<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;
use PHPUnit\Framework\Assert;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentService
 */
final class DeleteContentTest extends RepositoryTestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testDeleteContentDeletesChildrenDrafts(): void
    {
        $contentService = self::getContentService();

        [$folder, $draft1, $draft2, $draft3, $draftSecondDepth] = $this->prepareContentStructure();

        $contentService->deleteContent($folder->getContentInfo());

        $contentInfos = $contentService->loadContentInfoList([
            $draft1->getId(),
            $draft2->getId(),
            $draft3->getId(),
            $draftSecondDepth->getId(),
        ]);

        self::assertEmpty($contentInfos);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testTrashLocationDeletesChildrenDrafts(): void
    {
        $trashService = self::getTrashService();
        $contentService = self::getContentService();

        [$folder, $draft1, $draft2, $draft3, $draftSecondDepth] = $this->prepareContentStructure();

        $folderMainLocationId = $folder->getVersionInfo()->getContentInfo()->getMainLocationId();
        Assert::assertIsNumeric($folderMainLocationId);

        $locationToTrash = self::getLocationService()->loadLocation($folderMainLocationId);

        $trashService->trash($locationToTrash);

        $contentInfos = $contentService->loadContentInfoList([
            $draft1->getId(),
            $draft2->getId(),
            $draft3->getId(),
            $draftSecondDepth->getId(),
        ]);

        self::assertEmpty($contentInfos);
    }

    /**
     * @return array<Content>
     */
    private function prepareContentStructure(): array
    {
        $folder = $this->createFolder(['eng-GB' => 'Folder'], 2);
        $folderMainLocationId = $folder->getVersionInfo()->getContentInfo()->getMainLocationId();
        Assert::assertIsNumeric($folderMainLocationId);

        $childFolder = $this->createFolder(
            ['eng-GB' => 'Child folder'],
            $folderMainLocationId,
        );
        $childFolderMainLocationId = $childFolder->getVersionInfo()->getContentInfo()->getMainLocationId();
        Assert::assertIsNumeric($childFolderMainLocationId);

        $secondDepthChildFolder = $this->createFolder(
            ['eng-GB' => 'Second depth folder'],
            $childFolderMainLocationId,
        );
        $secondDepthChildFolderLocationId = $secondDepthChildFolder
            ->getVersionInfo()
            ->getContentInfo()
            ->getMainLocationId()
        ;
        Assert::assertIsNumeric($secondDepthChildFolderLocationId);

        $draft1 = $this->createFolderDraft(['eng-GB' => 'Folder draft 1'], $folderMainLocationId);
        $draft2 = $this->createFolderDraft(['eng-GB' => 'Folder draft 2'], $childFolderMainLocationId);
        $draft3 = $this->createFolderDraft(['eng-GB' => 'Folder draft 3'], $childFolderMainLocationId);
        $draftSecondDepth = $this->createFolderDraft(
            ['eng-GB' => 'Folder draft 4'],
            $secondDepthChildFolderLocationId,
        );

        return [
            $folder,
            $draft1,
            $draft2,
            $draft3,
            $draftSecondDepth,
        ];
    }
}

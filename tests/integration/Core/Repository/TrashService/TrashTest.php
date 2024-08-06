<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\TrashService;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;
use PHPUnit\Framework\Assert;

/**
 * @covers \Ibexa\Contracts\Core\Repository\TrashService
 */
final class TrashTest extends RepositoryTestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testTrashLocationDeletesChildrenDrafts(): void
    {
        $trashService = self::getTrashService();
        $contentService = self::getContentService();

        $folder = $this->createFolder(['eng-GB' => 'Folder'], 2);
        $folderMainLocationId = $folder->getVersionInfo()->getContentInfo()->getMainLocationId();

        Assert::assertIsNumeric($folderMainLocationId);

        $childFolder = $this->createFolder(
            ['eng-GB' => 'Child folder'],
            $folderMainLocationId,
        );
        $childFolderMainLocationId = $childFolder->getVersionInfo()->getContentInfo()->getMainLocationId();

        Assert::assertIsNumeric($childFolderMainLocationId);

        $draft1 = $this->createFolderDraft(['eng-GB' => 'Folder draft 1'], $folderMainLocationId);
        $draft2 = $this->createFolderDraft(['eng-GB' => 'Folder draft 2'], $childFolderMainLocationId);
        $draft3 = $this->createFolderDraft(['eng-GB' => 'Folder draft 3'], $childFolderMainLocationId);

        $locationToTrash = $this->getLocationService()->loadLocation($folderMainLocationId);

        $trashService->trash($locationToTrash);

        $this->expectException(NotFoundException::class);
        $contentService->loadContentInfo($draft1->getId());

        $this->expectException(NotFoundException::class);
        $contentService->loadContentInfo($draft2->getId());

        $this->expectException(NotFoundException::class);
        $contentService->loadContentInfo($draft3->getId());
    }
}

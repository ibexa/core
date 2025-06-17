<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Validation\ValidationFailedException;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentService
 */
final class CreateContentInNonContainerTest extends RepositoryTestCase
{
    public function testCreateContentInNonContainerTest(): void
    {
        $contentService = self::getContentService();
        $contentTypeService = self::getContentTypeService();
        $locationService = self::getLocationService();

        $blogPostType = $contentTypeService->loadContentTypeByIdentifier('blog_post');
        $commentType = $contentTypeService->loadContentTypeByIdentifier('comment');

        $commentCreateStruct = $contentService->newContentCreateStruct($commentType, 'eng-GB');
        $commentCreateStruct->setField('subject', 'Test comment');
        $commentCreateStruct->setField('author', 'Test Author');
        $commentCreateStruct->setField('message', 'Test Message');

        $parentLocationId = 2;
        $commentLocationCreateStruct = $locationService->newLocationCreateStruct($parentLocationId);

        $commentDraft = $contentService->createContent($commentCreateStruct, [$commentLocationCreateStruct]);
        $comment = $contentService->publishVersion($commentDraft->getVersionInfo());

        $commentLocationId = $comment->getContentInfo()->getMainLocationId();
        if ($commentLocationId === null) {
            self::fail('Comment does not have a main location.');
        }

        $commentLocation = $locationService->loadLocation($commentLocationId);

        $blogPostCreateStruct = $contentService->newContentCreateStruct($blogPostType, 'eng-GB');
        $blogPostCreateStruct->setField('title', 'Test Blog Post');
        $blogPostLocationCreateStruct = $locationService->newLocationCreateStruct($commentLocation->id);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage("Argument '\$locationCreateStructs->' is invalid: Location with Comment is not a container content type.");

        $contentService->createContent($blogPostCreateStruct, [$blogPostLocationCreateStruct]);
    }
}

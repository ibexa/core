<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentService
 */
final class CreateContentTest extends RepositoryTestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testContentCreationUnderNonContainerFails(): void
    {
        $contentTypeService = self::getContentTypeService();

        $groupCreate = $contentTypeService->newContentTypeGroupCreateStruct(
            'new-group'
        );
        $contentTypeService->createContentTypeGroup($groupCreate);

        $group = $contentTypeService->loadContentTypeGroupByIdentifier('new-group');
        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct('content_type_draft');
        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->names = [
            'eng-GB' => 'content_type_draft',
        ];
        $contentTypeCreateStruct->isContainer = false;

        $fieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('name', 'ibexa_string');
        $fieldCreate->names = ['eng-GB' => 'value'];
        $fieldCreate->fieldGroup = 'main';
        $fieldCreate->position = 1;

        $contentTypeCreateStruct->addFieldDefinition($fieldCreate);

        $contentTypeDraft = $contentTypeService->createContentType($contentTypeCreateStruct, [$group]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $container = $this->createAndPublishContent();
        $locationId = $container->getContentInfo()->getMainLocationId();

        self::assertIsInt($locationId);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "the 'contentType' property must be a content type instance that is a container.",
        );

        $this->createFolder(
            ['eng-GB' => 'some_name'],
            $locationId,
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    private function createAndPublishContent(): Content
    {
        $contentService = self::getContentService();
        $contentTypeService = self::getContentTypeService();
        $locationService = self::getLocationService();

        $folderType = $contentTypeService->loadContentTypeByIdentifier('content_type_draft');
        $mainLanguageCode = 'eng-GB';
        $contentCreateStruct = $contentService->newContentCreateStruct($folderType, $mainLanguageCode);
        $contentCreateStruct->setField('name', 'Some name', 'eng-GB');

        $draft = $contentService->createContent(
            $contentCreateStruct,
            [
                $locationService->newLocationCreateStruct(2),
            ]
        );

        return $contentService->publishVersion($draft->getVersionInfo());
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft
 */
final class CreateContentDraftInNewLanguageTest extends RepositoryTestCase
{
    private const NEW_LANGUAGE_CODE = 'ger-DE';

    public function testCreateContentDraftInNewLanguageWithEmptyNonTranslatableImage(): void
    {
        $content = $this->publishContentWithEmptyNonTranslatableImage();
        $language = $this->getIbexaTestCore()->getLanguageService()->loadLanguage(self::NEW_LANGUAGE_CODE);

        $draft = $this->getIbexaTestCore()->getContentService()->createContentDraft(
            $content->getContentInfo(),
            null,
            null,
            $language
        );

        self::assertSame(self::NEW_LANGUAGE_CODE, $draft->getVersionInfo()->initialLanguageCode);

        $imageValue = $draft->getFieldValue('image');
        self::assertInstanceOf(ImageValue::class, $imageValue);
        self::assertNull($imageValue->id);
    }

    private function publishContentWithEmptyNonTranslatableImage(): Content
    {
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();
        $contentService = $this->getIbexaTestCore()->getContentService();

        $typeCreateStruct = $contentTypeService->newContentTypeCreateStruct('empty_non_translatable_image');
        $typeCreateStruct->mainLanguageCode = 'eng-GB';
        $typeCreateStruct->names = ['eng-GB' => 'Empty non-translatable image'];
        $typeCreateStruct->nameSchema = '<name>';

        $nameFieldCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct('name', 'ezstring');
        $nameFieldCreateStruct->position = 1;
        $nameFieldCreateStruct->isTranslatable = true;
        $typeCreateStruct->addFieldDefinition($nameFieldCreateStruct);

        $imageFieldCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct('image', 'ezimage');
        $imageFieldCreateStruct->position = 2;
        $imageFieldCreateStruct->isTranslatable = false;
        $imageFieldCreateStruct->isRequired = false;
        $typeCreateStruct->addFieldDefinition($imageFieldCreateStruct);

        $contentTypeDraft = $contentTypeService->createContentType(
            $typeCreateStruct,
            [$contentTypeService->loadContentTypeGroupByIdentifier('Content')]
        );
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $contentCreateStruct->setField('name', 'Content with an empty image');

        return $contentService->publishVersion(
            $contentService->createContent($contentCreateStruct)->getVersionInfo()
        );
    }
}

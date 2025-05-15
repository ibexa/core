<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Persistence\Legacy;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

class FileHandlerTest extends RepositoryTestCase
{
    public function testUpdateFields(): void
    {
        $contentService = self::getContentService();
        $contentTypeService = self::getContentTypeService();

        // Create new ContentType
        $fieldDefCreateStruct = $this->createFieldDefinitionStruct('name', 'Name', true);

        $contentTypeCreateStruct = $this->createTypeCreateStruct();
        $contentTypeCreateStruct->addFieldDefinition($fieldDefCreateStruct);

        $contentType = $contentTypeService->createContentType($contentTypeCreateStruct, [
            $contentTypeService->loadContentTypeGroupByIdentifier('Content'),
        ]);

        $contentTypeService->publishContentTypeDraft($contentType);


        // Create content, with two translations
        $content = $this->createNewContent('Some Content', ['eng-GB', 'ger-DE']);

        // Create draft in each translation
        $content = $contentService->loadContent($content->getId(), ['eng-GB']);
        $engUpdateStruct = $this->createUpdateStruct($content, '', ['eng-GB']);
        $engDraft = $this->createContentDraft($content, 'eng-GB');
        $engDraft = $this->updateContent($engDraft, $engUpdateStruct);


        // Create new non-translatable field
        $contentType = $contentTypeService->loadContentTypeByIdentifier('multi_lang_drafts');
        $contentTypeDraft = $contentTypeService->createContentTypeDraft($contentType);
        $fieldDefCreateStruct = $this->createFieldDefinitionStruct('non_trans_field', 'Non translatable field', false);
        $contentTypeService->addFieldDefinition($contentTypeDraft, $fieldDefCreateStruct);

        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        // Update eng-GB draft
        $engUpdateStruct->setField('non_trans_field', '', 'eng-GB');
        $this->updateContent($engDraft, $engUpdateStruct);
    }

    private function createFieldDefinitionStruct(string $identifier, string $name, bool $isTranslatable): FieldDefinitionCreateStruct
    {
        $contentTypeService = self::getContentTypeService();

        $fieldDefCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct(
            $identifier,
            'ezstring'
        );

        $fieldDefCreateStruct->names = ['eng-GB' => $name];
        $fieldDefCreateStruct->descriptions = [
            'eng-GB' => '',
        ];
        $fieldDefCreateStruct->isTranslatable = $isTranslatable;

        return $fieldDefCreateStruct;
    }

    private function createTypeCreateStruct(): ContentTypeCreateStruct
    {
        $contentTypeService = self::getContentTypeService();
        $typeCreateStruct = $contentTypeService->newContentTypeCreateStruct('multi_lang_drafts');
        $typeCreateStruct->mainLanguageCode = 'eng-GB';
        $typeCreateStruct->names = ['eng-GB' => 'Multi lang drafts'];

        return $typeCreateStruct;
    }

    protected function createNewContent(string $name, array $languages = ['eng-GB'], int $parentLocationId = 2): Content
    {
        $contentTypeService = self::getContentTypeService();
        $contentService = self::getContentService();
        $locationService = self::getLocationService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('multi_lang_drafts');
        $createStruct = $contentService->newContentCreateStruct($contentType, $languages[0]);

        foreach ($languages as $language) {
            $createStruct->setField('name', "[$language]" . $name, $language);
        }
        $locationCreateStruct = $locationService->newLocationCreateStruct($parentLocationId);

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);

        return $contentService->publishVersion($draft->versionInfo);
    }

    protected function createUpdateStruct(Content $content, string $translatedName, array $languages)
    {
        $contentService = self::getContentService();

        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->initialLanguageCode = $languages[0];

        if ($translatedName === '') {
            $translatedNameOrg = $content->getName();
        } else {
            $translatedNameOrg = $translatedName;
        }

        foreach ($languages as $language) {
            $translatedName = "[$language]" . $translatedNameOrg;

            $updateStruct->setField('name', $translatedName, $language);
        }

        return $updateStruct;
    }

    protected function createContentDraft(Content $content, string $languageCode): Content
    {
        $contentService = self::getContentService();
        $contentLanguageService = self::getLanguageService();

        $language = $contentLanguageService->loadLanguage($languageCode);
        $draft = $contentService->createContentDraft($content->contentInfo, null, null, $language);

        return $draft;
    }

    protected function updateContent(Content $draft, ContentUpdateStruct $updateStruct/*, array $languages*/): Content
    {
        $contentService = self::getContentService();

        // At this point, $updateStruct is correct. However, when it reaches Ibexa\Core\Persistence\Legacy\Content::updateFields(),
        // it will have an additional field (in ger-DE). This does not happen when running the same code in
        // a controller inside the application (you then need to replace 'self::get*Service()' with DI).
        // It seems like for instance decoration Ibexa\Core\Event\ContentService is not used inside tests, maybe other decorations to?
        $updatedDraft = $contentService->updateContent($draft->versionInfo, $updateStruct);

        return $updatedDraft;
    }
}

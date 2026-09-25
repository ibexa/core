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

final class ContentUpdateAfterAddingFieldDefinitionTest extends RepositoryTestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testUpdateFields(): void
    {
        $contentService = $this->getIbexaTestCore()->getContentService();
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();

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

        // Create draft in language with higher id ( later in the $contentLanguageService->loadLanguages() list than 'eng-GB' )
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
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();

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
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();
        $typeCreateStruct = $contentTypeService->newContentTypeCreateStruct('multi_lang_drafts');
        $typeCreateStruct->mainLanguageCode = 'eng-GB';
        $typeCreateStruct->names = ['eng-GB' => 'Multi lang drafts'];

        return $typeCreateStruct;
    }

    /**
     * @param string[] $languages
     */
    protected function createNewContent(string $name, array $languages = ['eng-GB'], int $parentLocationId = 2): Content
    {
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();
        $contentService = $this->getIbexaTestCore()->getContentService();
        $locationService = $this->getIbexaTestCore()->getLocationService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('multi_lang_drafts');
        $createStruct = $contentService->newContentCreateStruct($contentType, $languages[0]);

        foreach ($languages as $language) {
            $createStruct->setField('name', "[$language]" . $name, $language);
        }
        $locationCreateStruct = $locationService->newLocationCreateStruct($parentLocationId);

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);

        return $contentService->publishVersion($draft->versionInfo);
    }

    /**
     * @param string[] $languages
     */
    protected function createUpdateStruct(Content $content, string $translatedName, array $languages): ContentUpdateStruct
    {
        $contentService = $this->getIbexaTestCore()->getContentService();

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
        $contentLanguageService = $this->getIbexaTestCore()->getLanguageService();

        $language = $contentLanguageService->loadLanguage($languageCode);

        return $this->getIbexaTestCore()->getContentService()->createContentDraft($content->contentInfo, null, null, $language);
    }

    protected function updateContent(Content $draft, ContentUpdateStruct $updateStruct): Content
    {
        return $this->getIbexaTestCore()->getContentService()->updateContent($draft->versionInfo, $updateStruct);
    }
}

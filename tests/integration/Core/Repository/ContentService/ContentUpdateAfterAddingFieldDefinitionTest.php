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
        $content = $this->createNewContent('Some Content', ['eng-US', 'ger-DE']);

        // Create draft in language with higher id ( later in the $contentLanguageService->loadLanguages() list than 'eng-US' )
        $content = $contentService->loadContent($content->getId(), ['eng-US']);
        $engUpdateStruct = $this->createUpdateStruct($content, '', ['eng-US']);
        $engDraft = $this->createContentDraft($content, 'eng-US');
        $engDraft = $this->updateContent($engDraft, $engUpdateStruct);

        // Create new non-translatable field
        $contentType = $contentTypeService->loadContentTypeByIdentifier('multi_lang_drafts');
        $contentTypeDraft = $contentTypeService->createContentTypeDraft($contentType);
        $fieldDefCreateStruct = $this->createFieldDefinitionStruct('non_trans_field', 'Non translatable field', false);
        $contentTypeService->addFieldDefinition($contentTypeDraft, $fieldDefCreateStruct);

        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        // Update eng-US draft
        $engUpdateStruct->setField('non_trans_field', '', 'eng-US');
        $this->updateContent($engDraft, $engUpdateStruct);
    }

    private function createFieldDefinitionStruct(string $identifier, string $name, bool $isTranslatable): FieldDefinitionCreateStruct
    {
        $contentTypeService = self::getContentTypeService();

        $fieldDefCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct(
            $identifier,
            'ezstring'
        );

        $fieldDefCreateStruct->names = ['eng-US' => $name];
        $fieldDefCreateStruct->descriptions = [
            'eng-US' => '',
        ];
        $fieldDefCreateStruct->isTranslatable = $isTranslatable;

        return $fieldDefCreateStruct;
    }

    private function createTypeCreateStruct(): ContentTypeCreateStruct
    {
        $contentTypeService = self::getContentTypeService();
        $typeCreateStruct = $contentTypeService->newContentTypeCreateStruct('multi_lang_drafts');
        $typeCreateStruct->mainLanguageCode = 'eng-US';
        $typeCreateStruct->names = ['eng-US' => 'Multi lang drafts'];

        return $typeCreateStruct;
    }

    /**
     * @param string[] $languages
     */
    protected function createNewContent(string $name, array $languages = ['eng-US'], int $parentLocationId = 2): Content
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

    /**
     * @param string[] $languages
     */
    protected function createUpdateStruct(Content $content, string $translatedName, array $languages): ContentUpdateStruct
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
        $contentLanguageService = self::getLanguageService();

        $language = $contentLanguageService->loadLanguage($languageCode);

        return self::getContentService()->createContentDraft($content->contentInfo, null, null, $language);
    }

    protected function updateContent(Content $draft, ContentUpdateStruct $updateStruct): Content
    {
        return self::getContentService()->updateContent($draft->versionInfo, $updateStruct);
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\LanguageService;
use Ibexa\Core\Repository\Values\Content\ContentUpdateStruct;

class ProofOfConceptTest extends BaseContentServiceTest
{
    private const ENG_US = 'eng-US';
    private const ENG_GB = 'eng-GB';
    private const GER_DE = 'ger-DE';

    private ContentService $contentService;

    private ContentTypeService $contentTypeService;

    private LanguageService $languageService;

    public function setUp(): void
    {
        parent::setUp();

        $this->contentService = $this->getRepository()->getContentService();
        $this->contentTypeService = $this->getRepository()->getContentTypeService();
        $this->languageService = $this->getRepository()->getContentLanguageService();
    }

    public function testProofOfConcept()
    {
        $contentType = $this->contentTypeService->loadContentTypeByIdentifier('folder');

        // Create content
        $contentCreateStruct = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $contentCreateStruct->setField('name', 'PoC', self::ENG_US);
        $contentCreateStruct->setField('short_name', 'PoC', self::ENG_US);
        $contentDraft = $this->contentService->createContent($contentCreateStruct);
        $content = $this->contentService->publishVersion($contentDraft->getVersionInfo());

        // Update content type definition
        $contentTypeDraft = $this->contentTypeService->createContentTypeDraft($contentType);
        $fieldDefCreateStruct = $this->contentTypeService->newFieldDefinitionCreateStruct(
            'poc_field',
            'ezstring'
        );
        $fieldDefCreateStruct->names = [self::ENG_US => 'PoC Field'];
        $fieldDefCreateStruct->descriptions = [self::ENG_US => 'PoC Field'];
        $fieldDefCreateStruct->fieldGroup = 'content';
        $fieldDefCreateStruct->position = 2;
        $fieldDefCreateStruct->isTranslatable = true;
        $fieldDefCreateStruct->isRequired = true;
        $fieldDefCreateStruct->isInfoCollector = false;
        $fieldDefCreateStruct->validatorConfiguration = [
            'StringLengthValidator' => [
                'minStringLength' => 0,
                'maxStringLength' => 0,
            ],
        ];
        $fieldDefCreateStruct->fieldSettings = [];
        $fieldDefCreateStruct->isSearchable = true;
        $fieldDefCreateStruct->defaultValue = 'Default PoC Value';

        $this->contentTypeService->addFieldDefinition($contentTypeDraft, $fieldDefCreateStruct);
        $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $this->contentTypeService->loadContentType($contentTypeDraft->id);

        // Translate content
        $contentDraft = $this->contentService->createContentDraft($content->contentInfo);
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        $contentUpdateStruct->initialLanguageCode = self::ENG_GB;
        $contentUpdateStruct->fields = $contentDraft->getFields();
        $contentUpdateStruct->setField('name', 'PoC GB', self::ENG_GB);
        $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
        $content = $this->contentService->publishVersion($contentDraft->versionInfo);
    }
}

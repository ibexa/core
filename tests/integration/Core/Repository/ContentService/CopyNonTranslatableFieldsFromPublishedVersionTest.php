<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use DateTime;
use Ibexa\Contracts\Core\Repository\Exceptions\Exception;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;
use Ibexa\Core\Repository\Values\Content\ContentUpdateStruct;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentService
 */
final class CopyNonTranslatableFieldsFromPublishedVersionTest extends RepositoryTestCase
{
    private const GER_DE = 'ger-DE';
    private const ENG_GB = 'eng-GB';
    private const CONTENT_TYPE_IDENTIFIER = 'nontranslatable';
    private const TEXT_LINE_FIELD_TYPE_IDENTIFIER = 'ezstring';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createNonTranslatableContentType();
    }

    /**
     * @throws Exception
     */
    public function testCopyNonTranslatableFieldsFromPublishedVersionToDraft(): void
    {
        $contentService = $this->getIbexaTestCore()->getContentService();

        // Creating start content in eng-GB language
        $publishedContent = $this->publishEngContent();

        // Creating a draft in ger-DE language with the only field updated being 'title'
        $gerContent = $this->updateDraft(
            $contentService->createContentDraft($publishedContent->contentInfo),
            self::GER_DE,
            ['title' => 'Folder GER']
        );

        // Updating non-translatable field in eng-GB language (allowed) and publishing it
        $expectedBodyValue = 'Non-translatable value';
        $engContent = $this->updateDraft(
            $contentService->createContentDraft($publishedContent->contentInfo),
            self::ENG_GB,
            ['title' => 'Title v2', 'body' => $expectedBodyValue]
        );
        $contentService->publishVersion($engContent->getVersionInfo());

        // Publishing ger-DE draft with the empty non-translatable field
        $contentService->publishVersion($gerContent->getVersionInfo());

        // Loading main content
        $mainPublishedContent = $contentService->loadContent($engContent->id);
        $bodyField = $mainPublishedContent->getField('body');
        $bodyFieldValue = $bodyField !== null ? $bodyField->getValue() : null;

        self::assertSame($expectedBodyValue, $bodyFieldValue->text);
    }

    /**
     * @throws Exception
     */
    public function testCopyNonTranslatableFieldsTwoParallelDrafts(): void
    {
        $contentService = $this->getIbexaTestCore()->getContentService();

        // Creating start content in eng-GB language
        $publishedContent = $this->publishEngContent();

        // Creating two drafts at the same time
        $usDraft = $contentService->createContentDraft($publishedContent->contentInfo);
        $gerDraft = $contentService->createContentDraft($publishedContent->contentInfo);

        // Publishing the draft in eng-GB language
        $usContent = $this->updateDraft(
            $usDraft,
            self::ENG_GB,
            ['title' => 'Title v2', 'body' => 'Nontranslatable body v2']
        );
        $contentService->publishVersion($usContent->getVersionInfo());

        // Publishing the draft in ger-DE language
        $gerContent = $this->updateDraft($gerDraft, self::GER_DE, ['title' => 'Title ger']);
        $contentService->publishVersion($gerContent->getVersionInfo());

        // Loading main content
        $mainPublishedContent = $contentService->loadContent($gerContent->id);
        $bodyFieldValue = $mainPublishedContent->getField('body')->getValue();

        self::assertSame('Nontranslatable body v2', $bodyFieldValue->text);
    }

    /**
     * @throws Exception
     */
    public function testPublishingTranslationDoesNotRevertNameOfTranslationPublishedFromParallelDraft(): void
    {
        $contentService = $this->getIbexaTestCore()->getContentService();

        $publishedContent = $this->publishEngContent();
        $gerContent = $this->updateDraft(
            $contentService->createContentDraft($publishedContent->contentInfo),
            self::GER_DE,
            ['title' => 'Title ger']
        );
        $publishedContent = $contentService->publishVersion($gerContent->getVersionInfo());

        $engDraft = $contentService->createContentDraft($publishedContent->contentInfo);
        $gerDraft = $contentService->createContentDraft($publishedContent->contentInfo);

        $engContent = $this->updateDraft($engDraft, self::ENG_GB, ['title' => 'Title eng v2']);
        $contentService->publishVersion($engContent->getVersionInfo(), [self::ENG_GB]);

        $gerContent = $this->updateDraft($gerDraft, self::GER_DE, ['title' => 'Title ger v2']);
        $publishedGerContent = $contentService->publishVersion(
            $gerContent->getVersionInfo(),
            [self::GER_DE]
        );

        $names = $publishedGerContent->getVersionInfo()->getNames();

        self::assertSame('Title eng v2', $names[self::ENG_GB]);
        self::assertSame('Title ger v2', $names[self::GER_DE]);
    }

    /**
     * @throws Exception
     */
    public function testCopyNonTranslatableFieldsOverridesNonMainLanguageDrafts(): void
    {
        $contentService = $this->getIbexaTestCore()->getContentService();

        // Creating start content in eng-GB language
        $publishedContent = $this->publishEngContent();

        // Creating a draft in ger-DE language with the only field updated being 'title'
        $gerContent = $this->updateDraft(
            $contentService->createContentDraft($publishedContent->contentInfo),
            self::GER_DE,
            ['title' => 'Folder GER']
        );
        $publishedContent = $contentService->publishVersion($gerContent->getVersionInfo());

        // Updating non-translatable field in eng-GB language (allowed) and publishing it
        $expectedBodyValue = 'Non-translatable value';
        $engContent = $this->updateDraft(
            $contentService->createContentDraft($publishedContent->contentInfo),
            self::ENG_GB,
            ['title' => 'Title v2', 'body' => $expectedBodyValue]
        );
        $contentService->publishVersion($engContent->getVersionInfo());

        // Loading content in ger-DE language
        $mainPublishedContent = $contentService->loadContent($engContent->id, ['ger-DE']);
        $bodyField = $mainPublishedContent->getField('body');
        $bodyFieldValue = $bodyField !== null ? $bodyField->getValue() : null;

        self::assertSame($expectedBodyValue, $bodyFieldValue->text);
    }

    /**
     * @throws Exception
     */
    private function publishEngContent(): Content
    {
        return $this->getIbexaTestCore()->getContentService()->publishVersion(
            $this->createEngDraft()->getVersionInfo()
        );
    }

    /**
     * @param array<string, string> $fieldValues
     *
     * @throws Exception
     */
    private function updateDraft(
        Content $draft,
        string $languageCode,
        array $fieldValues
    ): Content {
        $contentUpdateStruct = new ContentUpdateStruct(['initialLanguageCode' => $languageCode]);

        foreach ($fieldValues as $fieldIdentifier => $value) {
            $contentUpdateStruct->setField($fieldIdentifier, $value, $languageCode);
        }

        return $this->getIbexaTestCore()->getContentService()->updateContent(
            $draft->getVersionInfo(),
            $contentUpdateStruct
        );
    }

    private function createEngDraft(): Content
    {
        $contentService = $this->getIbexaTestCore()->getContentService();
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();
        $locationService = $this->getIbexaTestCore()->getLocationService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::CONTENT_TYPE_IDENTIFIER);
        $mainLanguageCode = self::ENG_GB;
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, $mainLanguageCode);
        $contentCreateStruct->setField('title', 'Test title');
        $contentCreateStruct->setField('body', 'Test body');

        return $contentService->createContent(
            $contentCreateStruct,
            [
                $locationService->newLocationCreateStruct(2),
            ]
        );
    }

    private function createNonTranslatableContentType(): void
    {
        $permissionResolver = $this->getIbexaTestCore()->getPermissionResolver();
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();

        $typeCreate = $contentTypeService->newContentTypeCreateStruct(self::CONTENT_TYPE_IDENTIFIER);

        $typeCreate->mainLanguageCode = 'eng-US';
        $typeCreate->remoteId = '1234567890abcdef';
        $typeCreate->urlAliasSchema = '<title>';
        $typeCreate->nameSchema = '<title>';
        $typeCreate->names = [
            'eng-US' => 'Non-translatable content type',
        ];
        $typeCreate->descriptions = [
            'eng-US' => '',
        ];
        $typeCreate->creatorId = $permissionResolver->getCurrentUserReference()->getUserId();
        $typeCreate->creationDate = new DateTime();

        $fieldDefinitionPosition = 1;
        $typeCreate->addFieldDefinition(
            $this->buildFieldDefinitionCreateStructForNonTranslatableContentType(
                $fieldDefinitionPosition,
                'title',
                ['eng-US' => 'Title'],
                true,
                true,
                'default title'
            )
        );

        $typeCreate->addFieldDefinition(
            $this->buildFieldDefinitionCreateStructForNonTranslatableContentType(
                ++$fieldDefinitionPosition,
                'body',
                ['eng-US' => 'Body'],
                false,
                false
            )
        );

        $contentTypeDraft = $contentTypeService->createContentType(
            $typeCreate,
            [$contentTypeService->loadContentTypeGroupByIdentifier('Media')],
        );
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
    }

    /**
     * @param array<string, string> $names
     */
    private function buildFieldDefinitionCreateStructForNonTranslatableContentType(
        int $position,
        string $fieldIdentifier,
        array $names,
        bool $isTranslatable,
        bool $isRequired,
        ?string $defaultValue = null
    ): FieldDefinitionCreateStruct {
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();

        $fieldDefinitionCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct(
            $fieldIdentifier,
            self::TEXT_LINE_FIELD_TYPE_IDENTIFIER
        );

        $fieldDefinitionCreateStruct->names = $names;
        $fieldDefinitionCreateStruct->descriptions = $names;
        $fieldDefinitionCreateStruct->fieldGroup = 'content';
        $fieldDefinitionCreateStruct->position = $position;
        $fieldDefinitionCreateStruct->isTranslatable = $isTranslatable;
        $fieldDefinitionCreateStruct->isRequired = $isRequired;
        $fieldDefinitionCreateStruct->isInfoCollector = false;
        $fieldDefinitionCreateStruct->validatorConfiguration = [
            'StringLengthValidator' => [
                'minStringLength' => 0,
                'maxStringLength' => 0,
            ],
        ];
        $fieldDefinitionCreateStruct->fieldSettings = [];
        $fieldDefinitionCreateStruct->isSearchable = true;
        $fieldDefinitionCreateStruct->defaultValue = $defaultValue;

        return $fieldDefinitionCreateStruct;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use DateTime;
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
    private const ENG_US = 'eng-US';
    private const CONTENT_TYPE_IDENTIFIER = 'nontranslatable';
    private const TEXT_LINE_FIELD_TYPE_IDENTIFIER = 'ibexa_string';

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCopyNonTranslatableFieldsFromPublishedVersionToDraft(): void
    {
        $this->createNonTranslatableContentType();

        $contentService = self::getContentService();

        // Creating start content in eng-US language
        $contentDraft = $this->createEngDraft();
        $publishedContent = $contentService->publishVersion($contentDraft->getVersionInfo());

        // Creating a draft in ger-DE language with the only field updated being 'title'
        $gerDraft = $contentService->createContentDraft($publishedContent->contentInfo);

        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::GER_DE,
            'fields' => $contentDraft->getFields(),
        ]);

        $contentUpdateStruct->setField('title', 'Folder GER', self::GER_DE);
        $gerContent = $contentService->updateContent($gerDraft->getVersionInfo(), $contentUpdateStruct);

        // Updating non-translatable field in eng-US language (allowed) and publishing it
        $engContent = $contentService->createContentDraft($publishedContent->contentInfo);

        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::ENG_US,
            'fields' => $contentDraft->getFields(),
        ]);

        $expectedBodyValue = 'Non-translatable value';
        $contentUpdateStruct->setField('title', 'Title v2', self::ENG_US);
        $contentUpdateStruct->setField('body', $expectedBodyValue, self::ENG_US);

        $engContent = $contentService->updateContent($engContent->getVersionInfo(), $contentUpdateStruct);
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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCopyNonTranslatableFieldsTwoParallelDrafts(): void
    {
        $this->createNonTranslatableContentType();

        $contentService = self::getContentService();

        // Creating start content in eng-US language
        $contentDraft = $this->createEngDraft();
        $publishedContent = $contentService->publishVersion($contentDraft->getVersionInfo());

        // Creating two drafts at the same time
        $usDraft = $contentService->createContentDraft($publishedContent->contentInfo);
        $gerDraft = $contentService->createContentDraft($publishedContent->contentInfo);

        // Publishing the draft in eng-US language
        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::ENG_US,
            'fields' => $usDraft->getFields(),
        ]);
        $contentUpdateStruct->setField('title', 'Title v2', self::ENG_US);
        $contentUpdateStruct->setField('body', 'Nontranslatable body v2', self::ENG_US);
        $usContent = $contentService->updateContent($usDraft->getVersionInfo(), $contentUpdateStruct);
        $contentService->publishVersion($usContent->getVersionInfo());

        // Publishing the draft in ger-DE language
        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::GER_DE,
            'fields' => $gerDraft->getFields(),
        ]);
        $contentUpdateStruct->setField('title', 'Title ger', self::GER_DE);
        $gerContent = $contentService->updateContent($gerDraft->getVersionInfo(), $contentUpdateStruct);
        $contentService->publishVersion($gerContent->getVersionInfo());

        // Loading main content
        $mainPublishedContent = $contentService->loadContent($gerContent->id);
        $bodyFieldValue = $mainPublishedContent->getField('body')->getValue();

        self::assertSame('Nontranslatable body v2', $bodyFieldValue->text);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCopyNonTranslatableFieldsOverridesNonMainLanguageDrafts(): void
    {
        $this->createNonTranslatableContentType();

        $contentService = self::getContentService();

        // Creating start content in eng-US language
        $contentDraft = $this->createEngDraft();
        $publishedContent = $contentService->publishVersion($contentDraft->getVersionInfo());

        // Creating a draft in ger-DE language with the only field updated being 'title'
        $gerDraft = $contentService->createContentDraft($publishedContent->contentInfo);

        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::GER_DE,
            'fields' => $contentDraft->getFields(),
        ]);

        $contentUpdateStruct->setField('title', 'Folder GER', self::GER_DE);
        $gerContent = $contentService->updateContent($gerDraft->getVersionInfo(), $contentUpdateStruct);
        $publishedContent = $contentService->publishVersion($gerContent->getVersionInfo());

        // Updating non-translatable field in eng-US language (allowed) and publishing it
        $engContent = $contentService->createContentDraft($publishedContent->contentInfo);

        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::ENG_US,
            'fields' => $contentDraft->getFields(),
        ]);

        $expectedBodyValue = 'Non-translatable value';
        $contentUpdateStruct->setField('title', 'Title v2', self::ENG_US);
        $contentUpdateStruct->setField('body', $expectedBodyValue, self::ENG_US);

        $engContent = $contentService->updateContent($engContent->getVersionInfo(), $contentUpdateStruct);
        $contentService->publishVersion($engContent->getVersionInfo());

        // Loading content in ger-DE language
        $mainPublishedContent = $contentService->loadContent($engContent->id, ['ger-DE']);
        $bodyField = $mainPublishedContent->getField('body');
        $bodyFieldValue = $bodyField !== null ? $bodyField->getValue() : null;

        self::assertSame($expectedBodyValue, $bodyFieldValue->text);
    }

    private function createEngDraft(): Content
    {
        $contentService = self::getContentService();
        $contentTypeService = self::getContentTypeService();
        $locationService = self::getLocationService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::CONTENT_TYPE_IDENTIFIER);
        $mainLanguageCode = self::ENG_US;
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
        $permissionResolver = self::getPermissionResolver();
        $contentTypeService = self::getContentTypeService();

        $typeCreate = $contentTypeService->newContentTypeCreateStruct(self::CONTENT_TYPE_IDENTIFIER);

        $typeCreate->mainLanguageCode = 'eng-GB';
        $typeCreate->remoteId = '1234567890abcdef';
        $typeCreate->urlAliasSchema = '<title>';
        $typeCreate->nameSchema = '<title>';
        $typeCreate->names = [
            'eng-GB' => 'Non-translatable content type',
        ];
        $typeCreate->descriptions = [
            'eng-GB' => '',
        ];
        $typeCreate->creatorId = $permissionResolver->getCurrentUserReference()->getUserId();
        $typeCreate->creationDate = new DateTime();

        $fieldDefinitionPosition = 1;
        $typeCreate->addFieldDefinition(
            $this->buildFieldDefinitionCreateStructForNonTranslatableContentType(
                $fieldDefinitionPosition,
                'title',
                ['eng-GB' => 'Title'],
                true,
                true,
                'default title'
            )
        );

        $typeCreate->addFieldDefinition(
            $this->buildFieldDefinitionCreateStructForNonTranslatableContentType(
                ++$fieldDefinitionPosition,
                'body',
                ['eng-GB' => 'Body'],
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
        $contentTypeService = self::getContentTypeService();

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

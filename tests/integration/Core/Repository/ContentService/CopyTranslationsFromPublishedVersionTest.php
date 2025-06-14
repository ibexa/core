<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use DateTime;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;
use Ibexa\Core\FieldType\TextLine;
use Ibexa\Core\Repository\Values\Content\ContentUpdateStruct;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\ContentService
 */
final class CopyTranslationsFromPublishedVersionTest extends RepositoryTestCase
{
    private const ENG_LANGUAGE_CODE = 'eng-GB';
    private const GER_LANGUAGE_CODE = 'ger-DE';
    private const US_LANGUAGE_CODE = 'eng-US';
    private const CONTENT_TYPE_IDENTIFIER = 'custom';

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCopyTranslationsFromPublishedVersionCopiesEmptyValues(): void
    {
        $this->createContentType();

        $contentService = self::getContentService();
        $contentTypeService = self::getContentTypeService();
        $locationService = self::getLocationService();

        // Creating and publishing content in eng-GB language
        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::CONTENT_TYPE_IDENTIFIER);
        $mainLanguageCode = self::ENG_LANGUAGE_CODE;
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, $mainLanguageCode);
        $contentCreateStruct->setField('title', 'Test title');

        $contentDraft = $contentService->createContent(
            $contentCreateStruct,
            [
                $locationService->newLocationCreateStruct(2),
            ],
        );
        $publishedContent = $contentService->publishVersion($contentDraft->getVersionInfo());

        // Creating a draft and publishing in ger-DE language with empty 'title' field
        $gerDraft = $contentService->createContentDraft($publishedContent->contentInfo);
        $usDraft = $contentService->createContentDraft($publishedContent->contentInfo);

        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::GER_LANGUAGE_CODE,
        ]);
        $contentUpdateStruct->setField('title', null);
        $gerContent = $contentService->updateContent($gerDraft->getVersionInfo(), $contentUpdateStruct);
        $contentService->publishVersion($gerContent->getVersionInfo(), [self::GER_LANGUAGE_CODE]);

        // Creating a draft and publishing in eng-US language with empty 'title' field
        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::US_LANGUAGE_CODE,
        ]);
        $contentUpdateStruct->setField('title', null);
        $usContent = $contentService->updateContent($usDraft->getVersionInfo(), $contentUpdateStruct);
        $publishedUsContent = $contentService->publishVersion($usContent->getVersionInfo(), [self::US_LANGUAGE_CODE]);

        $usFieldInUsContent = $publishedUsContent->getField('title', self::US_LANGUAGE_CODE);
        self::assertInstanceOf(Field::class, $usFieldInUsContent);

        $usFieldValueInUsContent = $usFieldInUsContent->getValue();
        self::assertInstanceOf(TextLine\Value::class, $usFieldValueInUsContent);
        self::assertSame('', $usFieldValueInUsContent->text);

        $gerFieldInUsContent = $publishedUsContent->getField('title', self::GER_LANGUAGE_CODE);
        self::assertInstanceOf(Field::class, $gerFieldInUsContent);

        $gerFieldValueInUsContent = $gerFieldInUsContent->getValue();
        self::assertInstanceOf(TextLine\Value::class, $gerFieldValueInUsContent);
        self::assertSame('', $gerFieldValueInUsContent->text);
    }

    private function createContentType(): void
    {
        $permissionResolver = self::getPermissionResolver();
        $contentTypeService = self::getContentTypeService();

        $typeCreate = $contentTypeService->newContentTypeCreateStruct(self::CONTENT_TYPE_IDENTIFIER);

        $typeCreate->mainLanguageCode = 'eng-GB';
        $typeCreate->remoteId = '1234567890abcdef';
        $typeCreate->urlAliasSchema = '<title>';
        $typeCreate->nameSchema = '<title>';
        $typeCreate->names = [
            self::ENG_LANGUAGE_CODE => 'Some content type',
        ];
        $typeCreate->descriptions = [
            self::ENG_LANGUAGE_CODE => '',
        ];
        $typeCreate->creatorId = $permissionResolver->getCurrentUserReference()->getUserId();
        $typeCreate->creationDate = new DateTime();

        $typeCreate->addFieldDefinition(
            new FieldDefinitionCreateStruct(
                [
                    'fieldTypeIdentifier' => 'ibexa_string',
                    'identifier' => 'title',
                    'names' => ['eng-GB' => 'Title'],
                    'isRequired' => false,
                    'isTranslatable' => true,
                ],
            )
        );

        $contentTypeDraft = $contentTypeService->createContentType(
            $typeCreate,
            [$contentTypeService->loadContentTypeGroupByIdentifier('Content')],
        );
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
    }
}

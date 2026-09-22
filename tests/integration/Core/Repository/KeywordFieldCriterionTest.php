<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\FieldType\Keyword\Value as KeywordValue;
use Ibexa\Tests\Integration\Core\RepositorySearchTestCase;

final class KeywordFieldCriterionTest extends RepositorySearchTestCase
{
    private const CONTENT_TYPE_IDENTIFIER = 'keyword_criterion_test';
    private const FIELD_IDENTIFIER = 'tags';
    private const TEST_TAG = 'test-tag-1';

    private ContentType $contentType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contentType = $this->createKeywordContentType();
    }

    public function testFindsContentWithKeywordInCurrentVersion(): void
    {
        $this->publishContentWithKeyword(self::TEST_TAG);
        $this->refreshSearch();

        self::assertSame(1, $this->countByKeyword(self::TEST_TAG));
    }

    public function testIgnoresKeywordFromArchivedVersion(): void
    {
        $published = $this->publishContentWithKeyword(self::TEST_TAG);
        $this->removeKeywordAndPublish($published);
        $this->refreshSearch();

        self::assertSame(0, $this->countByKeyword(self::TEST_TAG));
    }

    private function createKeywordContentType(): ContentType
    {
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();

        $typeStruct = $contentTypeService->newContentTypeCreateStruct(self::CONTENT_TYPE_IDENTIFIER);
        $typeStruct->mainLanguageCode = 'eng-GB';
        $typeStruct->names = ['eng-GB' => 'Keyword criterion test'];

        $fieldDef = $contentTypeService->newFieldDefinitionCreateStruct(self::FIELD_IDENTIFIER, 'ibexa_keyword');
        $fieldDef->names = ['eng-GB' => 'Tags'];
        $fieldDef->isSearchable = true;
        $typeStruct->addFieldDefinition($fieldDef);

        $draft = $contentTypeService->createContentType(
            $typeStruct,
            [$contentTypeService->loadContentTypeGroupByIdentifier('Content')]
        );
        $contentTypeService->publishContentTypeDraft($draft);

        return $contentTypeService->loadContentTypeByIdentifier(self::CONTENT_TYPE_IDENTIFIER);
    }

    private function publishContentWithKeyword(string $keyword): Content
    {
        $contentService = $this->getIbexaTestCore()->getContentService();

        $createStruct = $contentService->newContentCreateStruct($this->contentType, 'eng-GB');
        $createStruct->setField(self::FIELD_IDENTIFIER, new KeywordValue([$keyword]));

        return $contentService->publishVersion(
            $contentService->createContent(
                $createStruct,
                [$this->getIbexaTestCore()->getLocationService()->newLocationCreateStruct(2)]
            )->getVersionInfo()
        );
    }

    private function removeKeywordAndPublish(Content $content): void
    {
        $contentService = $this->getIbexaTestCore()->getContentService();

        $draft = $contentService->createContentDraft($content->getContentInfo());
        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->setField(self::FIELD_IDENTIFIER, new KeywordValue([]));
        $contentService->updateContent($draft->getVersionInfo(), $updateStruct);
        $contentService->publishVersion($draft->getVersionInfo());
    }

    private function countByKeyword(string $keyword): ?int
    {
        $query = new Query([
            'filter' => new Criterion\Field(self::FIELD_IDENTIFIER, Criterion\Operator::EQ, $keyword),
        ]);

        return $this->getIbexaTestCore()->getSearchService()->findContent($query)->totalCount;
    }
}

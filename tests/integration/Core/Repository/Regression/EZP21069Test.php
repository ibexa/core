<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Test case for issue EZP-21069.
 *
 * Issue EZP-21069
 *
 *     Search Service : when using the field criterion the query checks object attributes for all versions,
 *     it should use only attributes of the current version
 *
 *     Steps to reproduce :
 *     1 - Create a simple article with title : "foo"
 *     2 - Make a search with a field criterion : field.title = "foo", the new article is in the results
 *     3 - Change the name of your article from "foo" to "bar", your article is part of the result again, it should not
 *     4 - In the admin interface, delete the first version of the article, the article is no longer a part of the results
 */
class EZP21069Test extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $repository = $this->getRepository();

        // Loaded services
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();
        $urlAliasService = $repository->getURLAliasService();

        // Create Folder News
        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );
        $contentCreateStruct->setField('name', 'TheOriginalNews');
        $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        // Update folder
        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('name', 'TheUpdatedNews');

        $contentService->publishVersion(
            $contentService->updateContent(
                $contentService->createContentDraft(
                    $locationService->loadLocation(
                        $urlAliasService->lookup('/TheOriginalNews', 'eng-GB')->destination
                    )->getContentInfo()
                )->versionInfo,
                $contentUpdateStruct
            )->versionInfo
        );

        // Create an draft
        $contentDraftStruct = $contentService->newContentUpdateStruct();
        $contentDraftStruct->setField('name', 'TheDraftNews');

        $contentService->updateContent(
            $contentService->createContentDraft(
                $locationService->loadLocation(
                    $urlAliasService->lookup('/TheUpdatedNews', 'eng-GB')->destination
                )->getContentInfo()
            )->versionInfo,
            $contentDraftStruct
        );

        $this->refreshSearch($repository);
    }

    public function testSearchOnPreviousAttributeContentGivesNoResult()
    {
        $query = new Query();
        $query->filter = new Field('name', Operator::EQ, 'TheOriginalNews');
        $results = $this->getRepository()->getSearchService()->findContent($query);

        self::assertEquals(0, $results->totalCount);
        self::assertEmpty($results->searchHits);
    }

    public function testSearchOnCurrentAttributeContentGivesOnesResult()
    {
        $query = new Query();
        $query->filter = new Field('name', Operator::EQ, 'TheUpdatedNews');
        $results = $this->getRepository()->getSearchService()->findContent($query);

        self::assertEquals(1, $results->totalCount);
        self::assertCount(1, $results->searchHits);
    }

    public function testSearchOnDraftAttributeContentGivesNoResult()
    {
        $query = new Query();
        $query->filter = new Field('name', Operator::EQ, 'TheDraftNews');
        $results = $this->getRepository()->getSearchService()->findContent($query);

        self::assertEquals(0, $results->totalCount);
        self::assertEmpty($results->searchHits);
    }
}

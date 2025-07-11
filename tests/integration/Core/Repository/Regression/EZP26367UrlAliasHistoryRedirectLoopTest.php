<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Issue https://issues.ibexa.co/browse/EZP-26367.
 *
 * @group regression
 * @group ezp26367
 * @group cache
 * @group cache-invalidation
 * @group cache-spi
 */
class EZP26367UrlAliasHistoryRedirectLoopTest extends BaseTestCase
{
    public function testReverseLookupReturnsHistoryAlias()
    {
        $contentService = $this->getRepository()->getContentService();
        $contentTypeService = $this->getRepository()->getContentTypeService();
        $locationService = $this->getRepository()->getLocationService();
        $urlAliasService = $this->getRepository()->getURLAliasService();

        // Create container for articles

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $locationCreateStruct = $locationService->newLocationCreateStruct(2);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');

        $contentCreateStruct->setField('name', 'Articles');
        $draft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $folder = $contentService->publishVersion($draft->versionInfo);

        // Create one article in the container

        $contentType = $contentTypeService->loadContentTypeByIdentifier('article');
        $locationCreateStruct = $locationService->newLocationCreateStruct(
            $folder->contentInfo->mainLocationId
        );
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');

        $contentCreateStruct->setField('title', 'Article');
        $draft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $article = $contentService->publishVersion($draft->versionInfo);

        // Rename article container

        $draft = $contentService->createContentDraft($folder->contentInfo);
        $contentUpdateStruct = $contentService->newContentUpdateStruct();

        $contentUpdateStruct->setField('name', 'Articles-UPDATED');
        $draft = $contentService->updateContent($draft->versionInfo, $contentUpdateStruct);
        $contentService->publishVersion($draft->versionInfo);

        $historyPath = '/Articles/Article';
        $activePath = '/Articles-UPDATED/Article';

        // Lookup history first to warm-up URL alias object lookup cache by ID

        $urlAliasHistorized = $urlAliasService->lookup($historyPath);

        self::assertEquals($historyPath, $urlAliasHistorized->path);
        self::assertTrue($urlAliasHistorized->isHistory);

        // Reverse lookup once to warm-up URL alias ID cache by Location ID

        $urlAlias = $urlAliasService->reverseLookup(
            $locationService->loadLocation($article->contentInfo->mainLocationId)
        );

        self::assertEquals($activePath, $urlAlias->path);
        self::assertFalse($urlAlias->isHistory);

        // Reverse lookup again to trigger return of URL alias object lookup cache by ID,
        // through URL alias ID cache by Location ID

        $urlAlias = $urlAliasService->reverseLookup(
            $locationService->loadLocation($article->contentInfo->mainLocationId)
        );

        self::assertEquals($activePath, $urlAlias->path);
        self::assertFalse($urlAlias->isHistory);
    }

    public function testLookupHistoryUrlReturnsActiveAlias()
    {
        $contentService = $this->getRepository()->getContentService();
        $contentTypeService = $this->getRepository()->getContentTypeService();
        $locationService = $this->getRepository()->getLocationService();
        $urlAliasService = $this->getRepository()->getURLAliasService();

        // Create container for articles

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $locationCreateStruct = $locationService->newLocationCreateStruct(2);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');

        $contentCreateStruct->setField('name', 'Articles');
        $draft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $folder = $contentService->publishVersion($draft->versionInfo);

        // Create one article in the container

        $contentType = $contentTypeService->loadContentTypeByIdentifier('article');
        $locationCreateStruct = $locationService->newLocationCreateStruct(
            $folder->contentInfo->mainLocationId
        );
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');

        $contentCreateStruct->setField('title', 'Article');
        $draft = $contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
        $article = $contentService->publishVersion($draft->versionInfo);

        // Rename article container

        $draft = $contentService->createContentDraft($folder->contentInfo);
        $contentUpdateStruct = $contentService->newContentUpdateStruct();

        $contentUpdateStruct->setField('name', 'Articles-UPDATED');
        $draft = $contentService->updateContent($draft->versionInfo, $contentUpdateStruct);
        $contentService->publishVersion($draft->versionInfo);

        $historyPath = '/Articles/Article';
        $activePath = '/Articles-UPDATED/Article';

        // Reverse lookup to warm-up URL alias ID cache by Location ID

        $urlAlias = $urlAliasService->reverseLookup(
            $locationService->loadLocation($article->contentInfo->mainLocationId)
        );

        self::assertEquals($activePath, $urlAlias->path);
        self::assertFalse($urlAlias->isHistory);

        $urlAlias = $urlAliasService->reverseLookup(
            $locationService->loadLocation($article->contentInfo->mainLocationId)
        );

        self::assertEquals($activePath, $urlAlias->path);
        self::assertFalse($urlAlias->isHistory);

        // Lookup history URL one to warm-up URL alias ID cache by URL

        $urlAliasHistorized = $urlAliasService->lookup($historyPath);

        self::assertEquals($historyPath, $urlAliasHistorized->path);
        self::assertTrue($urlAliasHistorized->isHistory);

        // Lookup history URL again to trigger return of URL alias object reverse lookup cache by ID,
        // through URL alias ID cache by URL

        $urlAliasHistorized = $urlAliasService->lookup($historyPath);

        self::assertEquals($historyPath, $urlAliasHistorized->path);
        self::assertTrue($urlAliasHistorized->isHistory);
    }
}

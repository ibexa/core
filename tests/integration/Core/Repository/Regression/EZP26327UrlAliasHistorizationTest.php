<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Issue https://issues.ibexa.co/browse/EZP-26327.
 */
#[Group('ezp26327')]
class EZP26327UrlAliasHistorizationTest extends BaseTestCase
{
    public function testHistorization(): void
    {
        $contentService = $this->getRepository()->getContentService();
        $contentTypeService = $this->getRepository()->getContentTypeService();
        $locationService = $this->getRepository()->getLocationService();
        $urlAliasService = $this->getRepository()->getURLAliasService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $locationCreateStruct = $locationService->newLocationCreateStruct(2);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-US');

        $contentCreateStruct->setField('name', 'name-gb', 'eng-US');
        $contentCreateStruct->setField('name', 'name-us', 'eng-GB');

        $draft = $contentService->createContent(
            $contentCreateStruct,
            [$locationCreateStruct]
        );
        $content = $contentService->publishVersion($draft->versionInfo);

        // Warmup cache
        $urlAliasService->lookup('/name-gb');
        $urlAliasService->lookup('/name-us');

        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('name', 'name-gb', 'eng-GB');
        $draft = $contentService->createContentDraft($content->contentInfo);
        $draft = $contentService->updateContent($draft->versionInfo, $contentUpdateStruct);
        $contentService->publishVersion($draft->versionInfo);

        $activeAlias = $urlAliasService->lookup('/name-gb');
        $historyAlias = $urlAliasService->lookup('/name-us');

        self::assertFalse($activeAlias->isHistory);
        self::assertTrue($historyAlias->isHistory);
    }
}

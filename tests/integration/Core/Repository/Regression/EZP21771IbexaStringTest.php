<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Test case for 11+ string issue in EZP-21771.
 *
 * Issue EZP-21711
 */
class EZP21771IbexaStringTest extends BaseTestCase
{
    /**
     * This is an integration test for issue EZP-21771.
     *
     * It shouldn't throw a fatal error when inserting 11 consecutive digits
     * into an IbexaString field
     */
    public function test11NumbersOnIbexaString()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();

        // create content
        $createStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );
        $createStruct->setField('name', '12345678901');

        // make a draft
        $draft = $contentService->createContent(
            $createStruct,
            [$locationService->newLocationCreateStruct(2)]
        );

        // publish
        $contentService->publishVersion($draft->versionInfo);

        // load the content
        $content = $contentService->loadContent($draft->versionInfo->contentInfo->id);

        // finaly test if the value is done right
        self::assertEquals(
            $content->versionInfo->names,
            ['eng-GB' => '12345678901']
        );
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Visibility;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Test case for Visibility issues in EZP-20018.
 *
 * @covers \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Visibility
 *
 * Issue EZP-20018
 */
class EZP20018VisibilityTest extends BaseTestCase
{
    public function testSearchForHiddenContent()
    {
        $repository = $this->getRepository();

        $query = new Query();
        $query->filter = new Visibility(Visibility::HIDDEN);
        $results1 = $repository->getSearchService()->findContent($query);

        self::assertEquals(0, $results1->totalCount);
        self::assertCount(0, $results1->searchHits);

        // Hide "Images" Folder
        $locationService = $repository->getLocationService();
        $locationService->hideLocation($locationService->loadLocation(54));

        $this->refreshSearch($repository);

        // Assert updated values
        $results2 = $repository->getSearchService()->findContent($query);

        self::assertEquals(1, $results2->totalCount);
        self::assertCount(1, $results2->searchHits);
    }

    public function testSearchForVisibleContent()
    {
        $repository = $this->getRepository();

        $query = new Query();
        $query->filter = new Visibility(Visibility::VISIBLE);
        $query->limit = 50;
        $results1 = $repository->getSearchService()->findContent($query);

        self::assertEquals(18, $results1->totalCount);
        self::assertEquals($results1->totalCount, count($results1->searchHits));

        // Hide "Images" Folder
        $locationService = $repository->getLocationService();
        $locationService->hideLocation($locationService->loadLocation(54));

        $this->refreshSearch($repository);

        // Assert updated values
        $results2 = $repository->getSearchService()->findContent($query);

        self::assertEquals($results1->totalCount - 1, $results2->totalCount);
        self::assertEquals($results2->totalCount, count($results2->searchHits));
    }
}

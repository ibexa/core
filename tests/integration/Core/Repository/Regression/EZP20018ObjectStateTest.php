<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ObjectStateId;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * Test case for ObjectState issues in EZP-20018.
 *
 * @covers \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ObjectStateId
 *
 * Issue EZP-20018
 */
class EZP20018ObjectStateTest extends BaseTestCase
{
    public function testSearchForNonUsedObjectState()
    {
        $repository = $this->getRepository();

        $query = new Query();
        $query->filter = new ObjectStateId(2);
        $results1 = $repository->getSearchService()->findContent($query);

        self::assertEquals(0, $results1->totalCount);
        self::assertCount(0, $results1->searchHits);

        // Assign and make sure it updates
        $stateService = $repository->getObjectStateService();

        $stateService->setContentState(
            $repository->getContentService()->loadContentInfo(52),
            $stateService->loadObjectStateGroup(2),
            $stateService->loadObjectState(2)
        );

        $this->refreshSearch($repository);

        $results2 = $repository->getSearchService()->findContent($query);

        self::assertEquals(1, $results2->totalCount);
        self::assertCount($results2->totalCount, $results2->searchHits);
    }

    public function testSearchForUsedObjectState()
    {
        $repository = $this->getRepository();

        $query = new Query();
        $query->filter = new ObjectStateId(1);
        $query->limit = 50;
        $results1 = $repository->getSearchService()->findContent($query);

        self::assertEquals(18, $results1->totalCount);
        self::assertEquals($results1->totalCount, count($results1->searchHits));

        // Assign and make sure it updates
        $stateService = $repository->getObjectStateService();

        $stateService->setContentState(
            $repository->getContentService()->loadContentInfo(52),
            $stateService->loadObjectStateGroup(2),
            $stateService->loadObjectState(2)
        );

        $this->refreshSearch($repository);

        $results2 = $repository->getSearchService()->findContent($query);

        self::assertEquals(17, $results2->totalCount);
        self::assertCount($results2->totalCount, $results2->searchHits);
    }
}

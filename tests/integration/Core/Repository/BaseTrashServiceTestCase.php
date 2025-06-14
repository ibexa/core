<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Doctrine\DBAL\ParameterType;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway;

/**
 * Base class for trash specific tests.
 */
abstract class BaseTrashServiceTestCase extends BaseTestCase
{
    /**
     * Creates a trashed item from the <b>Community</b> page location and stores
     * this item in a location variable named <b>$trashItem</b>.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\TrashItem
     */
    protected function createTrashItem()
    {
        $repository = $this->getRepository();

        /* BEGIN: Inline */
        // remoteId of the "Media" page main location
        $mediaRemoteId = '75c715a51699d2d309a924eca6a95145';

        $trashService = $repository->getTrashService();
        $locationService = $repository->getLocationService();

        // Load "Media" page location
        $mediaLocation = $locationService->loadLocationByRemoteId(
            $mediaRemoteId
        );

        // Trash the "Community" page location
        $trashItem = $trashService->trash($mediaLocation);
        /* END: Inline */

        return $trashItem;
    }

    /**
     * @throws \ErrorException
     */
    protected function updateTrashedDate(int $locationId, int $newTimestamp): void
    {
        $connection = $this->getRawDatabaseConnection();
        $query = $connection->createQueryBuilder();
        $query
            ->update(Gateway::TRASH_TABLE)
            ->set('trashed', ':trashed_timestamp')
            ->where('node_id = :location_id')
            ->setParameter('trashed_timestamp', $newTimestamp, ParameterType::INTEGER)
            ->setParameter('location_id', $locationId, ParameterType::INTEGER);
        $query->executeStatement();
    }
}

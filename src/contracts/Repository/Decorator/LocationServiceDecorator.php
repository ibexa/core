<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationList;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;

abstract class LocationServiceDecorator implements LocationService
{
    protected LocationService $innerService;

    public function __construct(LocationService $innerService)
    {
        $this->innerService = $innerService;
    }

    public function copySubtree(
        Location $subtree,
        Location $targetParentLocation
    ): Location {
        return $this->innerService->copySubtree($subtree, $targetParentLocation);
    }

    public function loadLocation(
        int $locationId,
        ?array $prioritizedLanguages = null,
        ?bool $useAlwaysAvailable = null
    ): Location {
        return $this->innerService->loadLocation($locationId, $prioritizedLanguages, $useAlwaysAvailable);
    }

    public function loadLocationList(
        array $locationIds,
        ?array $prioritizedLanguages = null,
        ?bool $useAlwaysAvailable = null
    ): iterable {
        return $this->innerService->loadLocationList($locationIds, $prioritizedLanguages, $useAlwaysAvailable);
    }

    public function loadLocationByRemoteId(
        string $remoteId,
        ?array $prioritizedLanguages = null,
        ?bool $useAlwaysAvailable = null
    ): Location {
        return $this->innerService->loadLocationByRemoteId($remoteId, $prioritizedLanguages, $useAlwaysAvailable);
    }

    public function loadLocations(
        ContentInfo $contentInfo,
        ?Location $rootLocation = null,
        ?array $prioritizedLanguages = null
    ): iterable {
        return $this->innerService->loadLocations($contentInfo, $rootLocation, $prioritizedLanguages);
    }

    public function loadLocationChildren(
        Location $location,
        int $offset = 0,
        int $limit = 25,
        ?array $prioritizedLanguages = null
    ): LocationList {
        return $this->innerService->loadLocationChildren($location, $offset, $limit, $prioritizedLanguages);
    }

    public function loadParentLocationsForDraftContent(
        VersionInfo $versionInfo,
        ?array $prioritizedLanguages = null
    ): iterable {
        return $this->innerService->loadParentLocationsForDraftContent($versionInfo, $prioritizedLanguages);
    }

    public function getLocationChildCount(Location $location): int
    {
        return $this->innerService->getLocationChildCount($location);
    }

    public function getSubtreeSize(Location $location): int
    {
        return $this->innerService->getSubtreeSize($location);
    }

    public function createLocation(
        ContentInfo $contentInfo,
        LocationCreateStruct $locationCreateStruct
    ): Location {
        return $this->innerService->createLocation($contentInfo, $locationCreateStruct);
    }

    public function updateLocation(
        Location $location,
        LocationUpdateStruct $locationUpdateStruct
    ): Location {
        return $this->innerService->updateLocation($location, $locationUpdateStruct);
    }

    public function swapLocation(Location $location1, Location $location2): void
    {
        $this->innerService->swapLocation($location1, $location2);
    }

    public function hideLocation(Location $location): Location
    {
        return $this->innerService->hideLocation($location);
    }

    public function unhideLocation(Location $location): Location
    {
        return $this->innerService->unhideLocation($location);
    }

    public function moveSubtree(
        Location $location,
        Location $newParentLocation
    ): void {
        $this->innerService->moveSubtree($location, $newParentLocation);
    }

    public function deleteLocation(Location $location): void
    {
        $this->innerService->deleteLocation($location);
    }

    public function newLocationCreateStruct(int $parentLocationId): LocationCreateStruct
    {
        return $this->innerService->newLocationCreateStruct($parentLocationId);
    }

    public function newLocationUpdateStruct(): LocationUpdateStruct
    {
        return $this->innerService->newLocationUpdateStruct();
    }

    public function getAllLocationsCount(): int
    {
        return $this->innerService->getAllLocationsCount();
    }

    public function loadAllLocations(
        int $offset = 0,
        int $limit = 25
    ): array {
        return $this->innerService->loadAllLocations($offset, $limit);
    }

    public function find(Filter $filter, ?array $languages = null): LocationList
    {
        return $this->innerService->find($filter, $languages);
    }

    public function count(Filter $filter, ?array $languages = null): int
    {
        return $this->innerService->count($filter, $languages);
    }
}

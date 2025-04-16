<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\Decorator\LocationServiceDecorator;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocationServiceDecoratorTest extends TestCase
{
    private const EXAMPLE_LOCATION_ID = 54;
    private const EXAMPLE_OFFSET = 10;
    private const EXAMPLE_LIMIT = 100;

    protected function createDecorator(MockObject $service): LocationService
    {
        return new class($service) extends LocationServiceDecorator {
        };
    }

    protected function createServiceMock(): MockObject
    {
        return $this->createMock(LocationService::class);
    }

    public function testCopySubtreeDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(Location::class),
        ];

        $serviceMock->expects(self::once())->method('copySubtree')->with(...$parameters);

        $decoratedService->copySubtree(...$parameters);
    }

    public function testLoadLocationDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::EXAMPLE_LOCATION_ID,
            ['random_value_5ced05ce160308.46670993'],
            true,
        ];

        $serviceMock->expects(self::once())->method('loadLocation')->with(...$parameters);

        $decoratedService->loadLocation(...$parameters);
    }

    public function testLoadLocationListDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            ['random_value_5ced05ce160353.35020609'],
            ['random_value_5ced05ce160364.09322984'],
            true,
        ];

        $serviceMock->expects(self::once())->method('loadLocationList')->with(...$parameters);

        $decoratedService->loadLocationList(...$parameters);
    }

    public function testLoadLocationByRemoteIdDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            'random_value_5ced05ce160397.21653541',
            ['random_value_5ced05ce1603a3.59834231'],
            true,
        ];

        $serviceMock->expects(self::once())->method('loadLocationByRemoteId')->with(...$parameters);

        $decoratedService->loadLocationByRemoteId(...$parameters);
    }

    public function testLoadLocationsDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(Location::class),
            ['random_value_5ced05ce1603f9.50138109'],
        ];

        $serviceMock->expects(self::once())->method('loadLocations')->with(...$parameters);

        $decoratedService->loadLocations(...$parameters);
    }

    public function testLoadLocationChildrenDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(Location::class),
            self::EXAMPLE_OFFSET,
            self::EXAMPLE_LIMIT,
            ['random_value_5ced05ce160459.73858583'],
        ];

        $serviceMock->expects(self::once())->method('loadLocationChildren')->with(...$parameters);

        $decoratedService->loadLocationChildren(...$parameters);
    }

    public function testLoadParentLocationsForDraftContentDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(VersionInfo::class),
            ['random_value_5ced05ce160494.77580729'],
        ];

        $serviceMock->expects(self::once())->method('loadParentLocationsForDraftContent')->with(...$parameters);

        $decoratedService->loadParentLocationsForDraftContent(...$parameters);
    }

    public function testGetLocationChildCountDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(Location::class)];

        $serviceMock->expects(self::once())->method('getLocationChildCount')->with(...$parameters);

        $decoratedService->getLocationChildCount(...$parameters);
    }

    public function testCreateLocationDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(LocationCreateStruct::class),
        ];

        $serviceMock->expects(self::once())->method('createLocation')->with(...$parameters);

        $decoratedService->createLocation(...$parameters);
    }

    public function testUpdateLocationDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(LocationUpdateStruct::class),
        ];

        $serviceMock->expects(self::once())->method('updateLocation')->with(...$parameters);

        $decoratedService->updateLocation(...$parameters);
    }

    public function testSwapLocationDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(Location::class),
        ];

        $serviceMock->expects(self::once())->method('swapLocation')->with(...$parameters);

        $decoratedService->swapLocation(...$parameters);
    }

    public function testHideLocationDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(Location::class)];

        $serviceMock->expects(self::once())->method('hideLocation')->with(...$parameters);

        $decoratedService->hideLocation(...$parameters);
    }

    public function testUnhideLocationDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(Location::class)];

        $serviceMock->expects(self::once())->method('unhideLocation')->with(...$parameters);

        $decoratedService->unhideLocation(...$parameters);
    }

    public function testMoveSubtreeDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(Location::class),
        ];

        $serviceMock->expects(self::once())->method('moveSubtree')->with(...$parameters);

        $decoratedService->moveSubtree(...$parameters);
    }

    public function testDeleteLocationDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(Location::class)];

        $serviceMock->expects(self::once())->method('deleteLocation')->with(...$parameters);

        $decoratedService->deleteLocation(...$parameters);
    }

    public function testNewLocationCreateStructDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::EXAMPLE_LOCATION_ID];

        $serviceMock->expects(self::once())->method('newLocationCreateStruct')->with(...$parameters);

        $decoratedService->newLocationCreateStruct(...$parameters);
    }

    public function testNewLocationUpdateStructDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [];

        $serviceMock->expects(self::once())->method('newLocationUpdateStruct')->with(...$parameters);

        $decoratedService->newLocationUpdateStruct(...$parameters);
    }

    public function testGetAllLocationsCountDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [];

        $serviceMock->expects(self::once())->method('getAllLocationsCount')->with(...$parameters);

        $decoratedService->getAllLocationsCount(...$parameters);
    }

    public function testLoadAllLocationsDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            897,
            847,
        ];

        $serviceMock->expects(self::once())->method('loadAllLocations')->with(...$parameters);

        $decoratedService->loadAllLocations(...$parameters);
    }
}

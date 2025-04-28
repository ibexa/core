<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Helper;

use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as PersistenceLocationHandler;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo as APIContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Core\Helper\PreviewLocationProvider;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreviewLocationProviderTest extends TestCase
{
    private LocationService & MockObject $locationService;

    private PersistenceLocationHandler & MockObject $locationHandler;

    private PreviewLocationProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locationService = $this->createMock(LocationService::class);
        $this->locationHandler = $this->createMock(PersistenceLocationHandler::class);
        $this->provider = new PreviewLocationProvider($this->locationService, $this->locationHandler);
    }

    public function testGetPreviewLocationDraft(): void
    {
        $contentId = 123;
        $parentLocationId = 456;
        $content = $this->getContentMock($contentId);

        $this->locationService
            ->expects(self::never())
            ->method('loadLocation');

        $this->locationHandler
            ->expects(self::once())
            ->method('loadParentLocationsForDraftContent')
            ->with($contentId)
            ->willReturn([new Location(['id' => $parentLocationId])]);

        $location = $this->provider->loadMainLocationByContent($content);
        self::assertInstanceOf(APILocation::class, $location);
        self::assertSame($content, $location->getContent());
        self::assertNull($location->id);
        self::assertEquals($parentLocationId, $location->parentLocationId);
    }

    public function testGetPreviewLocation(): void
    {
        $contentId = 123;
        $locationId = 456;
        $content = $this->getContentMock($contentId, $locationId);

        $location = $this
            ->getMockBuilder(Location::class)
            ->setConstructorArgs([['id' => $locationId, 'content' => $content]])
            ->getMockForAbstractClass();

        $this->locationService
            ->expects(self::once())
            ->method('loadLocation')
            ->with($locationId)
            ->will(self::returnValue($location));

        $this->locationHandler->expects(self::never())->method('loadParentLocationsForDraftContent');

        $returnedLocation = $this->provider->loadMainLocationByContent($content);
        self::assertSame($location, $returnedLocation);
        self::assertSame($content, $location->getContent());
    }

    public function testGetPreviewLocationNoLocation(): void
    {
        $contentId = 123;
        $content = $this->getContentMock($contentId);

        $this->locationHandler
            ->expects(self::once())
            ->method('loadParentLocationsForDraftContent')
            ->with($contentId)
            ->will(self::returnValue([]));

        $this->locationHandler->expects(self::never())->method('loadLocationsByContent');

        self::assertNull($this->provider->loadMainLocationByContent($content));
    }

    private function getContentMock(int $contentId, ?int $mainLocationId = null, bool $published = false): Content
    {
        $contentInfo = new APIContentInfo([
            'id' => $contentId,
            'mainLocationId' => $mainLocationId,
            'published' => $published,
        ]);

        $versionInfo = $this->createMock(VersionInfo::class);
        $versionInfo->expects(self::once())
            ->method('getContentInfo')
            ->willReturn($contentInfo);

        $content = $this->createMock(Content::class);
        $content->expects(self::once())
            ->method('getVersionInfo')
            ->willReturn($versionInfo);

        return $content;
    }
}

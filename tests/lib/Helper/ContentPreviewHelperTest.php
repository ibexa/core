<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Helper;

use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Core\Helper\ContentPreviewHelper;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use PHPUnit\Framework\TestCase;

class ContentPreviewHelperTest extends TestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $siteAccessService;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $siteAccessRouter;

    /** @var \Ibexa\Core\Helper\ContentPreviewHelper */
    private $previewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteAccessService = $this->createMock(SiteAccessServiceInterface::class);
        $this->siteAccessRouter = $this->createMock(SiteAccessRouterInterface::class);
        $this->previewHelper = new ContentPreviewHelper($this->siteAccessRouter, $this->siteAccessService);
    }

    public function testChangeConfigScope()
    {
        $newSiteAccessName = 'test';
        $newSiteAccess = new SiteAccess($newSiteAccessName);

        $this->siteAccessRouter
            ->expects(self::once())
            ->method('matchByName')
            ->with(self::equalTo($newSiteAccessName))
            ->willReturn($newSiteAccess);

        $this->siteAccessService
            ->expects(self::once())
            ->method('changeSiteAccess')
            ->with($newSiteAccess)
            ->willReturn($newSiteAccess);

        self::assertEquals(
            $newSiteAccess,
            $this->previewHelper->changeConfigScope($newSiteAccessName)
        );
    }

    public function testRestoreConfigScope()
    {
        $originalSiteAccess = new SiteAccess('foo', 'bar');
        $this->siteAccessService
            ->expects(self::once())
            ->method('restoreSiteAccess')
            ->willReturn($originalSiteAccess);

        self::assertEquals(
            $originalSiteAccess,
            $this->previewHelper->restoreConfigScope()
        );
    }

    public function testPreviewActive()
    {
        $originalSiteAccess = new SiteAccess('foo', 'bar');
        $this->siteAccessService
            ->method('getCurrent')
            ->willReturn($originalSiteAccess);

        self::assertFalse($this->previewHelper->isPreviewActive());
        $this->previewHelper->setPreviewActive(true);
        self::assertTrue($this->previewHelper->isPreviewActive());
        $this->previewHelper->setPreviewActive(false);
        self::assertFalse($this->previewHelper->isPreviewActive());

        self::assertSame($originalSiteAccess, $this->previewHelper->getOriginalSiteAccess());
    }

    public function testPreviewedContent()
    {
        self::assertNull($this->previewHelper->getPreviewedContent());
        $content = $this->createMock(APIContent::class);
        $this->previewHelper->setPreviewedContent($content);
        self::assertSame($content, $this->previewHelper->getPreviewedContent());
    }

    public function testPreviewedLocation()
    {
        self::assertNull($this->previewHelper->getPreviewedLocation());
        $location = $this->createMock(APILocation::class);
        $this->previewHelper->setPreviewedLocation($location);
        self::assertSame($location, $this->previewHelper->getPreviewedLocation());
    }
}

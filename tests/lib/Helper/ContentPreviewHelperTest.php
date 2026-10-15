<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Helper;

use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Core\Helper\ContentPreviewHelper;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContentPreviewHelperTest extends TestCase
{
    /** @var MockObject */
    private $eventDispatcher;

    /** @var MockObject */
    private $siteAccessRouter;

    /** @var ContentPreviewHelper */
    private $previewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->siteAccessRouter = $this->createMock(SiteAccessRouterInterface::class);
        $this->previewHelper = new ContentPreviewHelper($this->eventDispatcher, $this->siteAccessRouter);
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

        $event = new ScopeChangeEvent($newSiteAccess);
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::equalTo($event), MVCEvents::CONFIG_SCOPE_CHANGE);

        $originalSiteAccess = new SiteAccess('foo', 'bar');
        $this->previewHelper->setSiteAccess($originalSiteAccess);
        self::assertEquals(
            $newSiteAccess,
            $this->previewHelper->changeConfigScope($newSiteAccessName)
        );
    }

    public function testRestoreConfigScope()
    {
        $originalSiteAccess = new SiteAccess('foo', 'bar');
        $event = new ScopeChangeEvent($originalSiteAccess);
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::equalTo($event), MVCEvents::CONFIG_SCOPE_RESTORE);

        $this->previewHelper->setSiteAccess($originalSiteAccess);
        self::assertEquals(
            $originalSiteAccess,
            $this->previewHelper->restoreConfigScope()
        );
    }

    public function testPreviewActive()
    {
        $originalSiteAccess = new SiteAccess('foo', 'bar');
        $this->previewHelper->setSiteAccess($originalSiteAccess);

        self::assertFalse($this->previewHelper->isPreviewActive());
        $this->previewHelper->setPreviewActive(true);
        self::assertTrue($this->previewHelper->isPreviewActive());
        $this->previewHelper->setPreviewActive(false);
        self::assertFalse($this->previewHelper->isPreviewActive());

        self::assertNotSame($originalSiteAccess, $this->previewHelper->getOriginalSiteAccess());
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

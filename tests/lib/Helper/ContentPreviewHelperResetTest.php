<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Helper;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\Helper\ContentPreviewHelper;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \Ibexa\Core\Helper\ContentPreviewHelper::reset
 */
final class ContentPreviewHelperResetTest extends TestCase
{
    public function testResetReplacesPreviewSnapshotWithSharedSiteAccess(): void
    {
        $sharedSiteAccess = new SiteAccess('site');
        $previewHelper = new ContentPreviewHelper(
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SiteAccessRouterInterface::class)
        );
        $previewHelper->setSiteAccess($sharedSiteAccess);
        $previewHelper->setPreviewActive(true);
        self::assertNotSame($sharedSiteAccess, $previewHelper->getOriginalSiteAccess());

        $previewHelper->reset();
        // The next request is matched to another SiteAccess and updates the shared object in place
        $sharedSiteAccess->name = 'admin';

        self::assertSame($sharedSiteAccess, $previewHelper->getOriginalSiteAccess());
    }

    public function testResetEndsPreviewLeftActive(): void
    {
        $previewHelper = new ContentPreviewHelper(
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(SiteAccessRouterInterface::class)
        );
        $previewHelper->setSiteAccess(new SiteAccess('site'));
        $previewHelper->setPreviewActive(true);
        $previewHelper->setPreviewedContent($this->createStub(Content::class));
        $previewHelper->setPreviewedLocation($this->createStub(Location::class));

        $previewHelper->reset();

        self::assertFalse($previewHelper->isPreviewActive());
        self::assertNull($previewHelper->getPreviewedContent());
        self::assertNull($previewHelper->getPreviewedLocation());
    }
}

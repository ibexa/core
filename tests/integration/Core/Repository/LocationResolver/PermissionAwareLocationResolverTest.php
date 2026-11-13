<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\LocationResolver;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\TrashService;
use Ibexa\Core\Repository\LocationResolver\PermissionAwareLocationResolver;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Core\Repository\LocationResolver\PermissionAwareLocationResolver
 */
final class PermissionAwareLocationResolverTest extends RepositoryTestCase
{
    private PermissionAwareLocationResolver $locationResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locationResolver = new PermissionAwareLocationResolver(
            $this->getIbexaTestCore()->getLocationService()
        );
    }

    public function testResolveLocation(): void
    {
        $folder = $this->createFolder(['eng-US' => 'Folder']);

        $location = $this->locationResolver->resolveLocation($folder->getContentInfo());

        self::assertSame($folder->getContentInfo()->getMainLocationId(), $location->getId());
    }

    public function testResolveLocationThrowsNotFoundExceptionForContentInTrash(): void
    {
        $folder = $this->createFolder(['eng-US' => 'Trashed folder']);
        $mainLocation = $folder->getContentInfo()->getMainLocation();
        self::assertNotNull($mainLocation);
        $this->getIbexaTestCore()->getServiceByClassName(TrashService::class)->trash($mainLocation);

        $contentInfo = $this->getIbexaTestCore()->getContentService()->loadContentInfo($folder->getId());
        self::assertTrue($contentInfo->isTrashed());
        self::assertNull($contentInfo->getMainLocationId());

        $this->expectException(NotFoundException::class);

        $this->locationResolver->resolveLocation($contentInfo);
    }
}

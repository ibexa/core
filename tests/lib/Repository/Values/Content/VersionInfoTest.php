<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Values\Content;

use Ibexa\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\Values\Content\VersionInfo
 */
final class VersionInfoTest extends TestCase
{
    public function testIsDraft(): void
    {
        $versionInfo = $this->createVersionInfoWithStatus(VersionInfo::STATUS_DRAFT);
        self::assertTrue($versionInfo->isDraft());

        $versionInfo = $this->createVersionInfoWithStatus(VersionInfo::STATUS_ARCHIVED);
        self::assertFalse($versionInfo->isDraft());
        $versionInfo = $this->createVersionInfoWithStatus(VersionInfo::STATUS_PUBLISHED);
        self::assertFalse($versionInfo->isDraft());
    }

    public function testIsPublished(): void
    {
        $versionInfo = $this->createVersionInfoWithStatus(VersionInfo::STATUS_PUBLISHED);
        self::assertTrue($versionInfo->isPublished());

        $versionInfo = $this->createVersionInfoWithStatus(VersionInfo::STATUS_DRAFT);
        self::assertFalse($versionInfo->isPublished());
        $versionInfo = $this->createVersionInfoWithStatus(VersionInfo::STATUS_ARCHIVED);
        self::assertFalse($versionInfo->isPublished());
    }

    public function testIsArchived(): void
    {
        $versionInfo = $this->createVersionInfoWithStatus(VersionInfo::STATUS_ARCHIVED);
        self::assertTrue($versionInfo->isArchived());

        $versionInfo = $this->createVersionInfoWithStatus(VersionInfo::STATUS_DRAFT);
        self::assertFalse($versionInfo->isArchived());
        $versionInfo = $this->createVersionInfoWithStatus(VersionInfo::STATUS_PUBLISHED);
        self::assertFalse($versionInfo->isArchived());
    }

    public function testStrictGetters(): void
    {
        $versionInfo = $this->createVersionInfoWithStatus(VersionInfo::STATUS_PUBLISHED);
        self::assertSame(123, $versionInfo->getVersionNo());
    }

    private function createVersionInfoWithStatus(int $status): VersionInfo
    {
        return new VersionInfo([
            'versionNo' => 123,
            'status' => $status,
        ]);
    }
}

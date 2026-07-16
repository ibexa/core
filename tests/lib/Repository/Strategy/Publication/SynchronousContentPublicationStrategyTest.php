<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Strategy\Publication;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Strategy\Publication\SynchronousContentPublicationStrategy;
use PHPUnit\Framework\TestCase;

final class SynchronousContentPublicationStrategyTest extends TestCase
{
    public function testSupportsAlwaysReturnsTrue(): void
    {
        $strategy = new SynchronousContentPublicationStrategy(
            $this->createMock(ContentService::class)
        );

        self::assertTrue($strategy->supports());
    }

    public function testPublishVersionDelegatesToContentService(): void
    {
        $versionInfo = $this->createMock(VersionInfo::class);

        $contentService = $this->createMock(ContentService::class);
        $contentService
            ->expects(self::once())
            ->method('publishVersion')
            ->with(self::identicalTo($versionInfo), ['ger-DE']);

        $strategy = new SynchronousContentPublicationStrategy($contentService);
        $strategy->publishVersion($versionInfo, ['ger-DE']);
    }

    public function testPublishVersionDefaultsToAllTranslations(): void
    {
        $versionInfo = $this->createMock(VersionInfo::class);

        $contentService = $this->createMock(ContentService::class);
        $contentService
            ->expects(self::once())
            ->method('publishVersion')
            ->with(self::identicalTo($versionInfo), Language::ALL);

        $strategy = new SynchronousContentPublicationStrategy($contentService);
        $strategy->publishVersion($versionInfo);
    }
}

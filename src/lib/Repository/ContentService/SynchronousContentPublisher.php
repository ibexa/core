<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\ContentPublisherInterface;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

/**
 * Synchronous publishing strategy: publishes the version inside the current request.
 *
 * This is the default strategy, wired unless "async_content_publish" is enabled.
 */
final readonly class SynchronousContentPublisher implements ContentPublisherInterface
{
    public function __construct(
        private ContentService $contentService,
    ) {
    }

    public function publishVersion(VersionInfo $versionInfo, array $translations = Language::ALL): void
    {
        $this->contentService->publishVersion($versionInfo, $translations);
    }
}

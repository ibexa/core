<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\Strategy\ContentPublication;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Strategy\ContentPublication\ContentPublicationResult;
use Ibexa\Contracts\Core\Repository\Strategy\ContentPublication\ContentPublicationStrategyInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

/**
 * Synchronous publication strategy: publishes the version inside the current request.
 * Always-applicable fallback, registered with the lowest priority.
 *
 * @internal
 */
final readonly class SynchronousContentPublicationStrategy implements ContentPublicationStrategyInterface
{
    public function __construct(
        private ContentService $contentService,
    ) {
    }

    public function supports(): bool
    {
        return true;
    }

    public function publishVersion(VersionInfo $versionInfo, array $translations = Language::ALL): ContentPublicationResult
    {
        return new ContentPublicationResult(
            $this->contentService->publishVersion($versionInfo, $translations)
        );
    }
}

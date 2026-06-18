<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

/**
 * Publishes a content version, hiding whether the publication happens synchronously
 * (in-request) or asynchronously (queued background work). The active strategy is
 * selected once at container-build time from the "async_content_publish" flag.
 *
 * Mirrors {@see ContentService::publishVersion()} arguments but returns void.
 */
interface ContentPublisherInterface
{
    /**
     * @param array<int, string> $translations List of language codes of translations which will be included
     *                                          in a published version.
     */
    public function publishVersion(VersionInfo $versionInfo, array $translations = Language::ALL): void;
}

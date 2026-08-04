<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\ContentPublisherInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

/**
 * Asynchronous publishing strategy: records a background publication job and returns
 * immediately. The heavy publication runs later via Symfony Messenger.
 *
 * Wired only when "async_content_publish" is enabled (see AsyncContentPublisherStrategyPass).
 */
final readonly class AsynchronousContentPublisher implements ContentPublisherInterface
{
    public function __construct(
        private AsyncPublicationService $asyncPublicationService,
    ) {
    }

    public function publishVersion(VersionInfo $versionInfo, array $translations = Language::ALL): void
    {
        $this->asyncPublicationService->registerPublication(
            $versionInfo->getContentInfo()->id,
            $versionInfo->versionNo,
            $translations,
        );
    }
}

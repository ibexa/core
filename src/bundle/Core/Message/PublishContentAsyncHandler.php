<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Message;

use Ibexa\Contracts\Core\Repository\ContentService;

final class PublishContentAsyncHandler
{
    public function __construct(
        private ContentService $contentService,
    ) {
    }

    public function __invoke(PublishContentAsync $message): void
    {
        $versionInfo = $this->contentService->loadVersionInfoById($message->contentId, $message->versionNo);

        $this->contentService->publishVersion(
            $versionInfo,
            [$versionInfo->getInitialLanguage()->getLanguageCode()],
        );

        // todo handle "publishing in progress" status
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Message;

use Ibexa\Core\Repository\ContentService\AsyncPublicationService;

final class PublishContentAsyncHandler
{
    public function __construct(private AsyncPublicationService $asyncPublicationService)
    {
    }

    public function __invoke(PublishContentAsync $message): void
    {
        $this->asyncPublicationService->processPublication($message->contentId, $message->versionNo, $message->translations);
    }
}

<?php

declare(strict_types=1);

namespace Ibexa\Bundle\Core\Message;

use Ibexa\Contracts\Core\Repository\ContentService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class PublishContentAsyncHandler
{
    public function __construct(
        private ContentService $contentService,
    )
    {
    }

    public function __invoke(PublishContentAsync $message)
    {
        $versionInfo = $this->contentService->loadVersionInfoById($message->contentId, $message->versionNo);

        $this->contentService->publishVersion(
            $versionInfo,
            [$versionInfo->getInitialLanguage()->getLanguageCode()],
        );

        // todo handle "publishing in progress" status
    }
}

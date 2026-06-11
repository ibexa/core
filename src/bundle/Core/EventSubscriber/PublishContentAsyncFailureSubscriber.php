<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\EventSubscriber;

use Ibexa\Bundle\Core\Message\PublishContentAsync;
use Ibexa\Core\Repository\ContentService\AsyncPublicationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/**
 * Reflects a terminally failed background publication (Messenger retries exhausted) in the job store,
 * so AdminUI can show the failure state and ops can see what is stuck.
 */
final class PublishContentAsyncFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AsyncPublicationService $asyncPublicationService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'onMessageFailed',
        ];
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        // Leave the job "processing" while Messenger will still retry it.
        if ($event->willRetry()) {
            return;
        }

        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof PublishContentAsync) {
            return;
        }

        $this->asyncPublicationService->markFailed(
            $message->contentId,
            $event->getThrowable()->getMessage()
        );
    }
}

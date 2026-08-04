<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\ContentService;

use DateTime;
use Ibexa\Bundle\Core\Message\PublishContentAsync;
use Ibexa\Bundle\Messenger\Stamp\DeduplicateStamp;
use Ibexa\Bundle\Messenger\Stamp\UserPermissionStamp;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJob;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\Handler;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Throwable;

/**
 * Releases queued background publication jobs onto the Messenger transport, one in flight per content
 * at a time, in creation order.
 *
 * Safe to call at any point: it scans every content with dispatchable work and, for each, sends the
 * oldest queued job. Concurrency ("only one message per content_id in flight") is enforced by the
 * {@see DeduplicateStamp} attached to each message: while a content's message is queued or being
 * processed, a second dispatch for the same content acquires no transport message id and is skipped.
 *
 * The message is stamped with the job's stored owner id (not the current user) because dispatch also
 * runs inside a worker, reacting to another job completing/failing, where there is no current user.
 */
final readonly class AsyncPublicationDispatcher
{
    private const string DEDUPLICATE_KEY_TEMPLATE = 'ibexa-async-publication-content-%d';

    public function __construct(
        private Handler $persistenceHandler,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * Dispatch the next eligible queued job for every content that has dispatchable work.
     */
    public function dispatchQueued(): void
    {
        foreach ($this->persistenceHandler->findContentIdsWithDispatchableWork() as $contentId) {
            $this->dispatchForContent($contentId);
        }
    }

    private function dispatchForContent(int $contentId): void
    {
        $job = $this->persistenceHandler->findOldestQueuedForContent($contentId);
        if ($job === null) {
            return;
        }

        $envelope = $this->bus->dispatch(
            new PublishContentAsync(
                $job->contentId,
                $job->versionNo,
                $job->data['translations'] ?? Language::ALL,
            ),
            [
                new UserPermissionStamp($job->ownerId),
                // TTL here is connected with presumed failed state
                // alternative to deduplicating messages, would be semaphore here per content id
                new DeduplicateStamp(sprintf(self::DEDUPLICATE_KEY_TEMPLATE, $job->contentId)),
            ],
        );

        $transportMessageIdStamp = $envelope->last(TransportMessageIdStamp::class);
        if ($transportMessageIdStamp === null) {
            // No transport message id means the DeduplicateMiddleware skipped the send because a
            // message for this content is already in flight. Leave the job queued; it will be
            // dispatched once the in-flight one completes.
            return;
        }

        $this->persistenceHandler->assignTransportMessageId(
            $job->id,
            (int) $transportMessageIdStamp->getId(),
            (new DateTime())->getTimestamp(),
        );
    }
}

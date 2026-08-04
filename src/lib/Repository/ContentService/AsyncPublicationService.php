<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\ContentService;

use DateTime;
use Exception;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJob as SPIAsyncPublicationJob;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJobStatus as SPIAsyncPublicationJobStatusAlias;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\Handler;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\TransactionHandler;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\AsyncPublication\AsyncPublicationJob as APIAsyncPublicationJob;
use Ibexa\Contracts\Core\Repository\Values\Content\AsyncPublication\AsyncPublicationJobStatus as APIAsyncPublicationJobStatus;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;

/**
 * Tracks background (asynchronous) content publication jobs so that AdminUI can surface
 * "publishing in progress" / "failed" state and operators can see in-flight and stuck jobs.
 *
 * The job store is the source of truth for the UI state; Symfony Messenger owns the actual queue.
 * Registering a job only records it as queued; releasing the message onto the transport (one in
 * flight per content at a time) is the {@see AsyncPublicationDispatcher}'s responsibility.
 */
class AsyncPublicationService
{
    public function __construct(
        private Handler $persistenceHandler,
        private PermissionResolver $permissionResolver,
        private AsyncPublicationDispatcher $dispatcher,
        private ContentService $contentService,
        private TransactionHandler $transactionHandler,
    ) {
    }

    /**
     * Record a background publication job for the given content version and mark it queued, then
     * trigger the dispatcher to release the next eligible message onto the transport.
     *
     * Multiple versions of the same content may be queued; the dispatcher serialises them so only
     * one is in flight per content at a time, in creation order.
     *
     * @param list<string> $translations
     */
    public function registerPublication(int $contentId, int $versionNo, array $translations = Language::ALL): void
    {
        $now = (new DateTime())->getTimestamp();
        $userId = $this->permissionResolver->getCurrentUserReference()->getUserId();

        $this->transactionHandler->beginTransaction();
        try {
            $createStruct = new CreateStruct();
            $createStruct->contentId = $contentId;
            $createStruct->versionNo = $versionNo;
            $createStruct->status = SPIAsyncPublicationJobStatusAlias::QUEUED;
            $createStruct->ownerId = $userId;
            $createStruct->created = $now;
            $createStruct->modified = $now;
            // transportMessageId stays null until the dispatcher actually sends the message.
            $createStruct->data = ['translations' => $translations];

            $this->persistenceHandler->register($createStruct);

            $this->transactionHandler->commit();
        } catch (Exception $exception) {
            $this->transactionHandler->rollback();
            throw $exception;
        }

        // After commit (so the new job is visible) and outside the DB transaction (so the Messenger
        // dispatch and its deduplication lock are not entangled with it), release the next message(s).
        $this->dispatcher->dispatchQueued();
    }

    /**
     * Perform the actual (heavy) publication of the given version.
     *
     * Job status transitions (processing/completed/failed) are driven separately by the AdminUI
     * AsyncPublicationStatusSubscriber, which reacts to the Messenger worker lifecycle events
     * surrounding this call and also notifies the UI via Mercure.
     *
     * @param string[] $translations
     */
    public function processPublication(int $contentId, int $versionNo, array $translations): void
    {
        $versionInfo = $this->contentService->loadVersionInfoById($contentId, $versionNo);

        $this->contentService->publishVersion(
            $versionInfo,
            $translations,
        );
    }

    /**
     * Mark the job for the given content version as being processed by a worker.
     */
    public function markProcessing(int $contentId, int $versionNo): void
    {
        $updateStruct = new UpdateStruct();
        $updateStruct->status = SPIAsyncPublicationJobStatusAlias::PROCESSING;
        $updateStruct->modified = (new DateTime())->getTimestamp();

        $this->persistenceHandler->update($contentId, $versionNo, $updateStruct);
    }

    /**
     * Clear the job for the given content version once its publication has completed successfully.
     */
    public function markCompleted(int $contentId, int $versionNo): void
    {
        $this->persistenceHandler->remove($contentId, $versionNo);
    }

    /**
     * Mark the job for the given content version as failed, retaining the error details.
     */
    public function markFailed(int $contentId, int $versionNo, string $errorMessage): void
    {
        $updateStruct = new UpdateStruct();
        $updateStruct->status = SPIAsyncPublicationJobStatusAlias::FAILED;
        $updateStruct->errorMessage = $errorMessage;
        $updateStruct->modified = (new DateTime())->getTimestamp();

        $this->persistenceHandler->update($contentId, $versionNo, $updateStruct);
    }

    /**
     * Return all jobs tracked for the given content (one per version), in creation order.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\AsyncPublication\AsyncPublicationJob[]
     */
    public function getPublicationsForContent(int $contentId): array
    {
        return array_map(
            fn (SPIAsyncPublicationJob $spiAsyncPublication): APIAsyncPublicationJob => $this->buildDomainObject($spiAsyncPublication),
            $this->persistenceHandler->findByContentId($contentId)
        );
    }

    /**
     * Return the in-flight and failed jobs (observability surface).
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\AsyncPublication\AsyncPublicationJob[]
     */
    public function findActivePublications(int $offset = 0, int $limit = 25): array
    {
        return array_map(
            fn (SPIAsyncPublicationJob $spiAsyncPublication): APIAsyncPublicationJob => $this->buildDomainObject($spiAsyncPublication),
            $this->persistenceHandler->find($offset, $limit)
        );
    }

    /**
     * @phpstan-return int<0, max>
     */
    public function countActivePublications(): int
    {
        return $this->persistenceHandler->count();
    }

    protected function buildDomainObject(SPIAsyncPublicationJob $spiAsyncPublication): APIAsyncPublicationJob
    {
        return new APIAsyncPublicationJob([
            'id' => $spiAsyncPublication->id,
            'contentId' => $spiAsyncPublication->contentId,
            'versionNo' => $spiAsyncPublication->versionNo,
            'status' => APIAsyncPublicationJobStatus::from($spiAsyncPublication->status->value),
            'ownerId' => $spiAsyncPublication->ownerId,
            'created' => new DateTime("@{$spiAsyncPublication->created}"),
            'modified' => new DateTime("@{$spiAsyncPublication->modified}"),
            'errorMessage' => $spiAsyncPublication->errorMessage,
            'data' => $spiAsyncPublication->data,
        ]);
    }
}

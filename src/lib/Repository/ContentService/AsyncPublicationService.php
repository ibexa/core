<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\ContentService;

use DateTime;
use Ibexa\Bundle\Core\Message\PublishContentAsync;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJob as SPIAsyncPublicationJob;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJobStatus;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\Handler;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\UpdateStruct;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\AsyncPublication\AsyncPublicationJob as APIAsyncPublicationJob;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Tracks background (asynchronous) content publication jobs so that AdminUI can surface
 * "publishing in progress" / "failed" state and operators can see in-flight and stuck jobs.
 *
 * The job store is the source of truth for the UI state; Symfony Messenger owns the actual queue.
 */
class AsyncPublicationService
{
    public function __construct(
        private Handler $persistenceHandler,
        private PermissionResolver $permissionResolver,
        private MessageBusInterface $bus,
        private ContentService $contentService,
    ) {
    }

    /**
     * Record (or supersede) a background publication job for the given content and mark it queued.
     *
     * Enforces "one active job per content".
     *
     * @param list<string> $translations
     */
    public function registerPublication(int $contentId, int $versionNo, array $translations = Language::ALL): void
    {
        $now = (new DateTime())->getTimestamp();

        $createStruct = new CreateStruct();
        $createStruct->contentId = $contentId;
        $createStruct->versionNo = $versionNo;
        $createStruct->status = AsyncPublicationJobStatus::QUEUED;
        $createStruct->ownerId = $this->permissionResolver->getCurrentUserReference()->getUserId();
        $createStruct->created = $now;
        $createStruct->modified = $now;
        $createStruct->data = ['translations' => $translations];

        // todo handle db transaction

        $this->persistenceHandler->register($createStruct);

        $this->bus->dispatch(
            new PublishContentAsync(
                $contentId,
                $versionNo,
                $translations,
            ),
        );
    }

    /**
     * @param string[] $translations
     */
    public function processPublication(int $contentId, int $versionNo, array $translations): void
    {
        $this->markProcessing($contentId);

        $versionInfo = $this->contentService->loadVersionInfoById($contentId, $versionNo);

        // todo handle db transaction
        /** todo handle errors:
         *   after EACH processing try:
         *   - job stays in current processing status
         *   - message is rejected then restarted accordingly to transport retry strategy
         *   after LAST messenger handling try:
         *   - job should be in failed status
         *   - message should be rejected accordingly
         */
        $this->contentService->publishVersion(
            $versionInfo,
            $translations,
        );

        // The new published version now exists; clearing the job clears the AdminUI "in progress" indicator.
        // On failure the job is left in place and marked failed by PublishContentAsyncFailureSubscriber.
        $this->markCompleted($contentId);
    }

    /**
     * Mark the job for the given content as being processed by a worker.
     */
    private function markProcessing(int $contentId): void
    {
        $updateStruct = new UpdateStruct();
        $updateStruct->status = AsyncPublicationJobStatus::PROCESSING;
        $updateStruct->modified = (new DateTime())->getTimestamp();

        $this->persistenceHandler->update($contentId, $updateStruct);
    }

    /**
     * Clear the job for the given content once its publication has completed successfully.
     */
    private function markCompleted(int $contentId): void
    {
        $this->persistenceHandler->remove($contentId);
    }

    /**
     * Mark the job for the given content as failed, retaining the error details.
     */
    // @phpstan-ignore method.unused
    private function markFailed(int $contentId, string $errorMessage): void
    {
        $updateStruct = new UpdateStruct();
        $updateStruct->status = AsyncPublicationJobStatus::FAILED;
        $updateStruct->errorMessage = $errorMessage;
        $updateStruct->modified = (new DateTime())->getTimestamp();

        $this->persistenceHandler->update($contentId, $updateStruct);
    }

    /**
     * Return the job tracked for the given content, or null when none is in flight.
     */
    public function getPublicationForContent(int $contentId): ?APIAsyncPublicationJob
    {
        $spiAsyncPublication = $this->persistenceHandler->getByContentId($contentId);

        return $spiAsyncPublication !== null
            ? $this->buildDomainObject($spiAsyncPublication)
            : null;
    }

    /**
     * Return the in-flight and failed jobs (observability surface).
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\AsyncPublication\AsyncPublicationJob[]
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
            'status' => $spiAsyncPublication->status->value,
            'ownerId' => $spiAsyncPublication->ownerId,
            'created' => new DateTime("@{$spiAsyncPublication->created}"),
            'modified' => new DateTime("@{$spiAsyncPublication->modified}"),
            'errorMessage' => $spiAsyncPublication->errorMessage,
            'data' => $spiAsyncPublication->data,
        ]);
    }
}

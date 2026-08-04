<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Content\AsyncPublication;

interface Handler
{
    /**
     * Register a background publication job for the given content version.
     *
     * A content item may hold several jobs (one per version_no); uniqueness is on
     * (content_id, version_no).
     */
    public function register(CreateStruct $createStruct): void;

    /**
     * Update the job tracked for the given content version.
     */
    public function update(int $contentId, int $versionNo, UpdateStruct $updateStruct): void;

    /**
     * Return all jobs tracked for the given content (one per version), ordered by creation.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJob[]
     */
    public function findByContentId(int $contentId): array;

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJob[]
     */
    public function find(int $offset = 0, int $limit = -1): array;

    /**
     * @phpstan-return int<0, max>
     */
    public function count(): int;

    /**
     * Remove the job tracked for the given content version (e.g. once the publication completed).
     */
    public function remove(int $contentId, int $versionNo): void;

    /**
     * Return the content ids that have a job awaiting dispatch and nothing currently in flight.
     *
     * @return int[]
     */
    public function findContentIdsWithDispatchableWork(): array;

    /**
     * Return the oldest not-yet-dispatched (queued) job for the content, or null when there is none.
     */
    public function findOldestQueuedForContent(int $contentId): ?AsyncPublicationJob;

    /**
     * Record the Messenger transport message id on a job once its message has been sent.
     */
    public function assignTransportMessageId(int $id, int $transportMessageId, int $modified): void;
}

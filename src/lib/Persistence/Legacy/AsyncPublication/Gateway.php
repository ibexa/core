<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\AsyncPublication;

use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\UpdateStruct;

abstract class Gateway
{
    /**
     * Store an AsyncPublication job in persistent storage and return its id.
     */
    abstract public function insert(CreateStruct $createStruct): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function findByContentId(int $contentId): array;

    /**
     * Update the job tracked for the given content version with the non-null values of $updateStruct.
     */
    abstract public function updateByContentIdAndVersion(int $contentId, int $versionNo, UpdateStruct $updateStruct): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function loadAll(int $offset = 0, int $limit = -1): array;

    /**
     * @phpstan-return int<0, max>
     */
    abstract public function countAll(): int;

    abstract public function deleteByContentIdAndVersion(int $contentId, int $versionNo): void;

    /**
     * Return the content ids that have at least one job awaiting dispatch (queued with no transport
     * message id) and no job currently in flight (dispatched or processing) for that content.
     *
     * @return int[]
     */
    abstract public function findContentIdsWithDispatchableWork(): array;

    /**
     * Return the oldest (by creation order) job for the content that is queued and not yet dispatched,
     * or an empty array when there is none.
     *
     * @return array<string, mixed>
     */
    abstract public function findOldestQueuedForContent(int $contentId): array;

    /**
     * Record the Messenger transport message id on a job once its message has been sent.
     */
    abstract public function assignTransportMessageId(int $id, int $transportMessageId, int $modified): void;
}

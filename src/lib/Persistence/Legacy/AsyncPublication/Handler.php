<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\AsyncPublication;

use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJob;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\Handler as HandlerInterface;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\UpdateStruct;

class Handler implements HandlerInterface
{
    public function __construct(
        private readonly Gateway $gateway,
        private readonly Mapper $mapper
    ) {
    }

    public function register(CreateStruct $createStruct): void
    {
        $this->gateway->insert($createStruct);
    }

    public function update(int $contentId, int $versionNo, UpdateStruct $updateStruct): void
    {
        $this->gateway->updateByContentIdAndVersion($contentId, $versionNo, $updateStruct);
    }

    public function findByContentId(int $contentId): array
    {
        return $this->mapper->extractAsyncPublicationsFromRows(
            $this->gateway->findByContentId($contentId)
        );
    }

    public function find(int $offset = 0, int $limit = -1): array
    {
        return $this->mapper->extractAsyncPublicationsFromRows(
            $this->gateway->loadAll($offset, $limit)
        );
    }

    public function count(): int
    {
        return $this->gateway->countAll();
    }

    public function remove(int $contentId, int $versionNo): void
    {
        $this->gateway->deleteByContentIdAndVersion($contentId, $versionNo);
    }

    public function findContentIdsWithDispatchableWork(): array
    {
        return $this->gateway->findContentIdsWithDispatchableWork();
    }

    public function findOldestQueuedForContent(int $contentId): ?AsyncPublicationJob
    {
        $row = $this->gateway->findOldestQueuedForContent($contentId);
        if ($row === []) {
            return null;
        }

        $jobs = $this->mapper->extractAsyncPublicationsFromRows([$row]);

        return $jobs[0] ?? null;
    }

    public function assignTransportMessageId(int $id, int $transportMessageId, int $modified): void
    {
        $this->gateway->assignTransportMessageId($id, $transportMessageId, $modified);
    }
}

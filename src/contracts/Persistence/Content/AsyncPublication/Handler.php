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
     * Register a background publication job for the given content.
     *
     * Enforces "one active job per content": if a job already exists for the content
     * exception is thrown.
     */
    public function register(CreateStruct $createStruct): void;

    /**
     * Update the job tracked for the given content.
     */
    public function update(int $contentId, UpdateStruct $updateStruct): void;

    /**
     * Return the job tracked for the given content, or null when none is in flight.
     */
    public function getByContentId(int $contentId): ?AsyncPublicationJob;

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJob[]
     */
    public function find(int $offset = 0, int $limit = -1): array;

    /**
     * @phpstan-return int<0, max>
     */
    public function count(): int;

    /**
     * Remove the job tracked for the given content (e.g. once the publication completed).
     */
    public function remove(int $contentId): void;
}

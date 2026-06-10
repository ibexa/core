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
     * Update the job tracked for the given content with the non-null values of $updateStruct.
     */
    abstract public function updateByContentId(int $contentId, UpdateStruct $updateStruct): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function loadAll(int $offset = 0, int $limit = -1): array;

    /**
     * @phpstan-return int<0, max>
     */
    abstract public function countAll(): int;

    abstract public function deleteByContentId(int $contentId): void;
}

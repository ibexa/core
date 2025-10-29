<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\Migration;

use Ibexa\Contracts\Core\IO\BinaryFile;

interface FileListerInterface extends MigrationHandlerInterface
{
    /**
     * Count the number of existing files.
     *
     * @return int|null Number of files, or null if they cannot be counted
     */
    public function countFiles(): ?int;

    /**
     * Loads and returns metadata for files, optionally limited by $limit and $offset.
     *
     * @param int|null $limit The number of files to load data for, or null
     * @param int|null $offset The offset used when loading in batches, or null
     *
     * @return BinaryFile[]
     */
    public function loadMetadataList(
        ?int $limit = null,
        ?int $offset = null
    ): array;
}

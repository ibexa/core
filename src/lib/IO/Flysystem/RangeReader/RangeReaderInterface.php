<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Flysystem\RangeReader;

use League\Flysystem\FilesystemException;

/**
 * Flysystem has no range API, so implementations talk to the storage backend directly.
 */
interface RangeReaderInterface
{
    /**
     * @param string $path path as passed to \League\Flysystem\FilesystemOperator::readStream()
     *
     * @return resource|null null when the range cannot be served natively, the caller then falls back to a full stream
     *
     * @throws FilesystemException
     */
    public function readStreamRange(
        string $path,
        int $offset,
        ?int $length = null
    );
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO;

use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;

interface RangeAwareIOBinarydataHandlerInterface
{
    /**
     * The resource MAY contain more than $length bytes; consumers must limit reading on their own.
     *
     * @return resource|null positioned at $offset; null when the storage cannot serve ranges natively,
     *                       the caller then falls back to getResource()
     *
     * @throws BinaryFileNotFoundException
     */
    public function getResourceRange(
        string $spiBinaryFileId,
        int $offset,
        ?int $length = null
    );
}

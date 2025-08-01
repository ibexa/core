<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\IO;

interface MimeTypeDetector
{
    /**
     * Returns the MIME type of the file identified by $path.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getFromPath(string $path): string;

    /**
     * Returns the MIME type of the data in $buffer.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getFromBuffer(string $buffer): string;
}

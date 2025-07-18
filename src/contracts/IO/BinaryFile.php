<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\IO;

use DateTimeInterface;

/**
 * This class provides abstract access to binary files.
 *
 * It allows reading & writing of files in a unified way
 */
class BinaryFile
{
    /**
     * Unique persistence layer identifier for this file.
     *
     * Ex: images/media/images/ibexa-logo/209-1-eng-GB/Ibexa-Logo.gif,
     *     or original/application/2b042138835bb5f48beb9c9df6e86de4.pdf.
     */
    public string $id;

    /**
     * File size, in bytes.
     *
     * @var int
     */
    public int $size;

    /**
     * File modification time.
     */
    public DateTimeInterface $mtime;

    /**
     * HTTP URI to the binary file.
     */
    public string $uri;
}

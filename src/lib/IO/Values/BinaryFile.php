<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\IO\Values;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class provides an abstract access to binary files.
 *
 * It allows reading & writing of files in a unified way
 *
 * @property-read string $id The id of the binary file
 * @property-read int $mtime File modification time
 * @property-read string $uri HTTP URI to the binary file
 * @property-read int $size File size
 */
class BinaryFile extends ValueObject
{
    /**
     * Unique ID
     * Ex: media/images/ibexa-logo/209-1-eng-GB/Ibexa-Logo.gif, or application/2b042138835bb5f48beb9c9df6e86de4.pdf.
     *
     * @var mixed
     */
    protected $id;

    /**
     * File size, in bytes.
     *
     * @var int
     */
    protected $size;

    /**
     * File modification time.
     *
     * @var \DateTime
     */
    protected $mtime;

    /**
     * URI to the binary file.
     *
     * @var string
     */
    protected $uri;
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Values;

use DateTime;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class provides abstract access to binary files.
 *
 * It allows reading & writing of files in a unified way
 *
 * @property-read string $id The id of the binary file
 * @property-read DateTime $mtime File modification time
 * @property-read string $uri HTTP URI to the binary file
 * @property-read int $size File size
 */
class BinaryFile extends ValueObject
{
    /**
     * Unique ID
     * Ex: media/images/ibexa-logo/209-1-eng-GB/Ibexa-Logo.gif, or application/2b042138835bb5f48beb9c9df6e86de4.pdf.
     */
    protected string $id;

    /**
     * File size, in bytes.
     */
    protected ?int $size = null;

    /**
     * File modification time.
     */
    protected ?DateTime $mtime = null;

    /**
     * URI to the binary file.
     */
    protected string $uri;

    public function getId(): string
    {
        return $this->id;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getMtime(): ?DateTime
    {
        return $this->mtime;
    }

    public function getUri(): string
    {
        return $this->uri;
    }
}

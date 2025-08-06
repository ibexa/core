<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\IO;

use DateTimeInterface;

/**
 * Create struct for BinaryFile objects.
 */
class BinaryFileCreateStruct
{
    /**
     * File size, in bytes.
     */
    public int $size;

    /**
     * File modification time.
     */
    public DateTimeInterface $mtime;

    /**
     * The file's mime type.
     *
     * If not provided, will be auto-detected by the IOService
     * Example: text/xml.
     */
    public string $mimeType;

    /**
     * Unique identifier for this file.
     *
     * Ex: images/media/images/ibexa-logo/209-1-eng-GB/Ibexa-Logo.gif,
     *     or original/application/2b042138835bb5f48beb9c9df6e86de4.pdf.
     */
    public string $id;

    /** @var resource */
    private mixed $inputStream;

    /**
     * Returns the file's input resource.
     *
     * @return resource
     */
    public function getInputStream(): mixed
    {
        return $this->inputStream;
    }

    /**
     * Sets the file's input resource.
     *
     * @param resource $inputStream
     */
    public function setInputStream(mixed $inputStream): void
    {
        $this->inputStream = $inputStream;
    }
}

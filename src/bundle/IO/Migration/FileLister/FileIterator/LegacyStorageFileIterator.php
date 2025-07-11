<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO\Migration\FileLister\FileIterator;

use Ibexa\Bundle\IO\Migration\FileLister\FileIteratorInterface;
use Ibexa\Bundle\IO\Migration\FileLister\FileRowReaderInterface;

/**
 * Iterator for entries in legacy's file tables.
 *
 * The returned items are filename of binary/media files (video/87c2bfd00.wmv).
 */
final class LegacyStorageFileIterator implements FileIteratorInterface
{
    /** @var mixed Last fetched item. */
    private $item;

    /** @var int Iteration cursor on statement. */
    private $cursor;

    /** @var \Ibexa\Bundle\IO\Migration\FileLister\FileRowReaderInterface Used to get file rows. */
    private $rowReader;

    /**
     * @param \Ibexa\Bundle\IO\Migration\FileLister\FileRowReaderInterface $rowReader
     */
    public function __construct(FileRowReaderInterface $rowReader)
    {
        $this->rowReader = $rowReader;
    }

    #[\ReturnTypeWillChange]
    public function current(): mixed
    {
        return $this->item;
    }

    public function next(): void
    {
        $this->fetchRow();
    }

    #[\ReturnTypeWillChange]
    public function key(): mixed
    {
        return $this->cursor;
    }

    public function valid(): bool
    {
        return $this->cursor < $this->count();
    }

    public function rewind(): void
    {
        $this->cursor = -1;
        $this->rowReader->init();
        $this->fetchRow();
    }

    public function count(): int
    {
        return $this->rowReader->getCount();
    }

    /**
     * Fetches the next item from the resultset and moves the cursor forward.
     */
    private function fetchRow()
    {
        ++$this->cursor;
        $fileId = $this->rowReader->getRow();

        $this->item = $fileId;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

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
    private ?string $item;

    /** @var int Iteration cursor on statement. */
    private int $cursor;

    public function __construct(private readonly FileRowReaderInterface $rowReader) {}

    #[\ReturnTypeWillChange]
    public function current(): ?string
    {
        return $this->item;
    }

    public function next(): void
    {
        $this->fetchRow();
    }

    #[\ReturnTypeWillChange]
    public function key(): int
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
    private function fetchRow(): void
    {
        ++$this->cursor;
        $fileId = $this->rowReader->getRow();

        $this->item = $fileId;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\VariationPurger;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\IO\IOConfigProvider;

/**
 * Iterator for entries in legacy's ibexa_image_file table.
 *
 * The returned items are id of Image BinaryFile (ibexa-mountains/mount-aconcagua/605-1-eng-GB/Mount-Aconcagua.jpg).
 */
class LegacyStorageImageFileList implements ImageFileList
{
    /**
     * Last fetched item.
     */
    private ?string $item;

    /**
     * Iteration cursor on $statement.
     *
     * @var int
     */
    private int $cursor;

    public function __construct(
        private readonly ImageFileRowReader $rowReader,
        private readonly IOConfigProvider $ioConfigResolver,
        private readonly ConfigResolverInterface $configResolver
    ) {
    }

    #[\ReturnTypeWillChange]
    public function current(): ?string
    {
        return $this->item;
    }

    public function next(): void
    {
        $this->fetchRow();
    }

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
     * Fetches the next item from the resultset, moves the cursor forward, and removes the prefix from the image id.
     */
    private function fetchRow(): void
    {
        // Folder, relative to the root, where files are stored. Example: var/ibexa_demo_site/storage
        $storageDir = $this->ioConfigResolver->getLegacyUrlPrefix();
        $prefix = $storageDir . '/' . $this->configResolver->getParameter('image.published_images_dir');
        ++$this->cursor;
        $this->item = $this->rowReader->getRow();
        if ($this->item === null) {
            return;
        }

        $imageId = $this->item;
        if (0 === strncmp($imageId, $prefix, strlen($prefix))) {
            $imageId = ltrim(substr($imageId, strlen($prefix)), '/');
        }

        $this->item = $imageId;
    }
}

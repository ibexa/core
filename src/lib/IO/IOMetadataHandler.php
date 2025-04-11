<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\IO;

use Ibexa\Contracts\Core\IO\BinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct;

/**
 * Provides reading & writing of files meta-data (size, modification time...).
 */
interface IOMetadataHandler
{
    /**
     * Stores the file from $binaryFileCreateStruct.
     *
     * @throws \RuntimeException if an error occurred creating the file
     */
    public function create(BinaryFileCreateStruct $spiBinaryFileCreateStruct): BinaryFile;

    /**
     * Deletes file $spiBinaryFileId.
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If $spiBinaryFileId is not found
     */
    public function delete(string $binaryFileId): void;

    /**
     * Loads and returns metadata for $spiBinaryFileId.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function load(string $binaryFileId): BinaryFile;

    /**
     * Checks if a file $spiBinaryFileId exists.
     */
    public function exists(string $binaryFileId): bool;

    /**
     * Returns the file's mimetype. Example: text/plain.
     */
    public function getMimeType(string $binaryFileId): string;

    public function deleteDirectory(string $pathName): void;
}

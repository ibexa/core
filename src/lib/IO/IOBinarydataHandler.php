<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO;

use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;

/**
 * Provides reading & writing of files binary data.
 */
interface IOBinarydataHandler
{
    /**
     * Creates a new file with data from $binaryFileCreateStruct.
     *
     * @param BinaryFileCreateStruct $binaryFileCreateStruct
     *
     * @throws \RuntimeException if an error occurred creating the file
     */
    public function create(BinaryFileCreateStruct $binaryFileCreateStruct): void;

    /**
     * Deletes the file by its $binaryFileId.
     *
     * @throws BinaryFileNotFoundException If the file is not found
     */
    public function delete(string $binaryFileId): void;

    /**
     * Returns the binary content from $path.
     *
     * @throws BinaryFileNotFoundException If $path is not found
     */
    public function getContents(string $spiBinaryFileId): string;

    /**
     * Returns a read-only, binary file resource to $path.
     *
     * @param string $spiBinaryFileId
     *
     * @return resource A read-only binary resource to $path
     *
     * @throws NotFoundException
     */
    public function getResource(string $spiBinaryFileId): mixed;

    /**
     * Returns the public URI for $path.
     */
    public function getUri(string $spiBinaryFileId): string;

    /**
     * Returns the id in $binaryFileUri.
     */
    public function getIdFromUri(string $binaryFileUri): string;

    /**
     * Deletes the directory $spiPath and all of its contents.
     */
    public function deleteDirectory(string $path): void;
}

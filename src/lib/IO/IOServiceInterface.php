<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO;

use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;

/**
 * Interface for Input/Output handling of binary files.
 */
interface IOServiceInterface
{
    /**
     * The internal prefix added by the IO Service.
     */
    public function setPrefix(string $prefix): void;

    /**
     * Creates a BinaryFileCreateStruct object from $localFile.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException When given a non-existing / unreadable file
     */
    public function newBinaryCreateStructFromLocalFile(string $localFile): BinaryFileCreateStruct;

    /**
     * Checks if a Binary File with $binaryFileId exists.
     */
    public function exists(string $binaryFileId): bool;

    /**
     * Loads the binary file with $id.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the id is invalid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If no file identified by $binaryFileId exists
     */
    public function loadBinaryFile(string $binaryFileId): BinaryFile;

    /**
     * Loads the binary file with uri $uri.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the id is invalid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If no file identified by $binaryFileId exists
     */
    public function loadBinaryFileByUri(string $binaryFileUri): BinaryFile;

    /**
     * Returns the content of the binary file.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If $binaryFile isn't found
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getFileContents(BinaryFile $binaryFile): string;

    /**
     * Creates a binary file in the repository.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function createBinaryFile(BinaryFileCreateStruct $binaryFileCreateStruct): BinaryFile;

    /**
     * Returns the public HTTP uri for $binaryFileId.
     */
    public function getUri(string $binaryFileId): string;

    /**
     * Gets the mime-type of the BinaryFile.
     *
     * Example: text/xml
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function getMimeType(string $binaryFileId): ?string;

    /**
     * Returns a read (mode: rb) file resource to the binary file identified by $path.
     *
     * @return resource
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getFileInputStream(BinaryFile $binaryFile): mixed;

    /**
     * Deletes the BinaryFile with $id.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function deleteBinaryFile(BinaryFile $binaryFile): void;

    /**
     * Creates a BinaryFileCreateStruct object from the uploaded file $uploadedFile.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException When given an invalid uploaded file
     *
     * @param array<string, mixed> $uploadedFile The $_POST hash of an uploaded file
     */
    public function newBinaryCreateStructFromUploadedFile(array $uploadedFile): BinaryFileCreateStruct;

    public function deleteDirectory(string $path): void;
}

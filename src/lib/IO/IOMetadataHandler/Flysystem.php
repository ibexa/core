<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\IO\IOMetadataHandler;

use DateTime;
use Ibexa\Contracts\Core\IO\BinaryFile as SPIBinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as SPIBinaryFileCreateStruct;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\Exception\IOException;
use Ibexa\Core\IO\IOMetadataHandler;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

class Flysystem implements IOMetadataHandler
{
    private FilesystemOperator $filesystem;

    public function __construct(FilesystemOperator $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Only reads & return metadata, since the binary data handler took care of creating the file already.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function create(SPIBinaryFileCreateStruct $spiBinaryFileCreateStruct): SPIBinaryFile
    {
        return $this->load($spiBinaryFileCreateStruct->id);
    }

    /**
     * Does really nothing, the binary data handler takes care of it.
     *
     * @param $spiBinaryFileId
     */
    public function delete($spiBinaryFileId)
    {
    }

    public function load($spiBinaryFileId): SPIBinaryFile
    {
        try {
            $spiBinaryFile = new SPIBinaryFile();
            $spiBinaryFile->id = $spiBinaryFileId;
            $spiBinaryFile->size = $this->filesystem->fileSize($spiBinaryFileId);
            $spiBinaryFile->mtime = new DateTime(
                '@' . $this->filesystem->lastModified($spiBinaryFileId)
            );

            return $spiBinaryFile;
        } catch (FilesystemException $e) {
            throw new BinaryFileNotFoundException($spiBinaryFileId);
        }
    }

    public function exists($spiBinaryFileId): bool
    {
        try {
            return $this->filesystem->fileExists($spiBinaryFileId);
        } catch (FilesystemException $e) {
            throw new IOException(
                "Unable to check if file '$spiBinaryFileId' exists: {$e->getMessage()}",
                $e
            );
        }
    }

    public function getMimeType($spiBinaryFileId): string
    {
        try {
            return $this->filesystem->mimeType($spiBinaryFileId);
        } catch (FilesystemException $e) {
            throw new IOException(
                "Unable to get mime type of file '$spiBinaryFileId': {$e->getMessage()}",
                $e
            );
        }
    }

    /**
     * Does nothing, as the binary data handler takes care of it.
     */
    public function deleteDirectory($spiPath)
    {
    }
}

class_alias(Flysystem::class, 'eZ\Publish\Core\IO\IOMetadataHandler\Flysystem');

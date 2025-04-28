<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\IO\IOMetadataHandler;

use DateTime;
use Ibexa\Contracts\Core\IO\BinaryFile as IOBinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as SPIBinaryFileCreateStruct;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\Exception\IOException;
use Ibexa\Core\IO\IOMetadataHandler;
use League\Flysystem\CorruptedPathDetected;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Flysystem implements IOMetadataHandler, LoggerAwareInterface
{
    private LoggerInterface $logger;

    private FilesystemOperator $filesystem;

    public function __construct(FilesystemOperator $filesystem, ?LoggerInterface $logger = null)
    {
        $this->filesystem = $filesystem;
        $this->logger = $logger ?? new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Only reads & returns metadata, since the binary data handler took care of creating the file already.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function create(SPIBinaryFileCreateStruct $spiBinaryFileCreateStruct): IOBinaryFile
    {
        return $this->load($spiBinaryFileCreateStruct->id);
    }

    /**
     * Does really nothing, the binary data handler takes care of it.
     */
    public function delete(string $binaryFileId): void
    {
    }

    public function load(string $binaryFileId): IOBinaryFile
    {
        try {
            return $this->getIOBinaryFile($binaryFileId);
        } catch (FilesystemException $e) {
            throw new BinaryFileNotFoundException($binaryFileId);
        }
    }

    public function exists(string $binaryFileId): bool
    {
        try {
            return $this->filesystem->fileExists($binaryFileId);
        } catch (CorruptedPathDetected $e) {
            $this->logger->error(
                sprintf('Binary file with ID="%s" does not exist: %s', $binaryFileId, $e->getMessage()),
                ['exception' => $e],
            );

            return false;
        } catch (FilesystemException $e) {
            throw new IOException(
                "Unable to check if file '$binaryFileId' exists: {$e->getMessage()}",
                $e
            );
        }
    }

    public function getMimeType(string $binaryFileId): string
    {
        try {
            return $this->filesystem->mimeType($binaryFileId);
        } catch (FilesystemException $e) {
            throw new IOException(
                "Unable to get mime type of file '$binaryFileId': {$e->getMessage()}",
                $e
            );
        }
    }

    /**
     * Does nothing, as the binary data handler takes care of it.
     */
    public function deleteDirectory(string $pathName): void
    {
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     * @throws \DateMalformedStringException
     */
    private function getIOBinaryFile(string $spiBinaryFileId): IOBinaryFile
    {
        $spiBinaryFile = new IOBinaryFile();
        $spiBinaryFile->id = $spiBinaryFileId;
        $spiBinaryFile->size = $this->filesystem->fileSize($spiBinaryFileId);
        $spiBinaryFile->mtime = new DateTime(
            '@' . $this->filesystem->lastModified($spiBinaryFileId)
        );

        return $spiBinaryFile;
    }
}

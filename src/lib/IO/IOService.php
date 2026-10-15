<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO;

use DateTime;
use Exception;
use Ibexa\Contracts\Core\IO\BinaryFile as PersistenceBinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as PersistenceBinaryFileCreateStruct;
use Ibexa\Contracts\Core\IO\MimeTypeDetector;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\Exception\InvalidBinaryFileIdException;
use Ibexa\Core\IO\Exception\InvalidBinaryPrefixException;
use Ibexa\Core\IO\Exception\IOException;
use Ibexa\Core\IO\Values\BinaryFile as BinaryFileValue;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;

/**
 * The io service for managing binary files.
 *
 * @internal
 */
class IOService implements IOServiceInterface
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        protected readonly IOMetadataHandler $metadataHandler,
        protected readonly IOBinarydataHandler $binarydataHandler,
        protected readonly MimeTypeDetector $mimeTypeDetector,
        protected array $settings = []
    ) {}

    public function setPrefix(string $prefix): void
    {
        $this->settings['prefix'] = $prefix;
    }

    public function newBinaryCreateStructFromUploadedFile(array $uploadedFile): BinaryFileCreateStruct
    {
        if (!is_string($uploadedFile['tmp_name']) || empty($uploadedFile['tmp_name'])) {
            throw new InvalidArgumentException('uploadedFile', "uploadedFile['tmp_name'] does not exist or has invalid value");
        }

        if (!is_uploaded_file($uploadedFile['tmp_name']) || !is_readable($uploadedFile['tmp_name'])) {
            throw new InvalidArgumentException('uploadedFile', 'file was not uploaded or is unreadable');
        }

        $fileHandle = fopen($uploadedFile['tmp_name'], 'rb');
        if ($fileHandle === false) {
            throw new InvalidArgumentException('uploadedFile', 'failed to get file resource');
        }

        $binaryCreateStruct = new BinaryFileCreateStruct();
        $binaryCreateStruct->size = $uploadedFile['size'];
        $binaryCreateStruct->inputStream = $fileHandle;
        $binaryCreateStruct->mimeType = $uploadedFile['type'];

        return $binaryCreateStruct;
    }

    public function newBinaryCreateStructFromLocalFile(string $localFile): BinaryFileCreateStruct
    {
        if (empty($localFile)) {
            throw new InvalidArgumentException('localFile', 'localFile has an invalid value');
        }

        if (!is_file($localFile) || !is_readable($localFile)) {
            throw new InvalidArgumentException('localFile', "file does not exist or is unreadable: {$localFile}");
        }

        $fileHandle = fopen($localFile, 'rb');
        if ($fileHandle === false) {
            throw new InvalidArgumentException('localFile', 'failed to get file resource');
        }

        return new BinaryFileCreateStruct(
            [
                'size' => filesize($localFile),
                'inputStream' => $fileHandle,
                'mimeType' => $this->mimeTypeDetector->getFromPath($localFile),
            ]
        );
    }

    public function createBinaryFile(BinaryFileCreateStruct $binaryFileCreateStruct): BinaryFileValue
    {
        if (empty($binaryFileCreateStruct->id)) {
            throw new InvalidArgumentValue('id', $binaryFileCreateStruct->id, 'BinaryFileCreateStruct');
        }

        // `fread` expects this to be a positive number
        if ($binaryFileCreateStruct->size <= 0) {
            throw new InvalidArgumentValue('size', $binaryFileCreateStruct->size, 'BinaryFileCreateStruct');
        }

        if (!is_resource($binaryFileCreateStruct->inputStream)) {
            throw new InvalidArgumentValue('inputStream', 'property is not a file resource', 'BinaryFileCreateStruct');
        }

        if (!isset($binaryFileCreateStruct->mimeType)) {
            $buffer = fread($binaryFileCreateStruct->inputStream, $binaryFileCreateStruct->size);
            if (false === $buffer) {
                throw new InvalidArgumentException(
                    '$binaryFileCreateStruct',
                    "Failed to read the resource of BinaryFile with id = '$binaryFileCreateStruct->id'"
                );
            }
            $binaryFileCreateStruct->mimeType = $this->mimeTypeDetector->getFromBuffer($buffer);
            unset($buffer);
        }

        $spiBinaryCreateStruct = $this->buildSPIBinaryFileCreateStructObject($binaryFileCreateStruct);

        try {
            $this->binarydataHandler->create($spiBinaryCreateStruct);
        } catch (Exception $e) {
            throw new IOException('An error occurred when creating binary data', $e);
        }

        $spiBinaryFile = $this->metadataHandler->create($spiBinaryCreateStruct);
        if (!isset($spiBinaryFile->uri)) {
            $spiBinaryFile->uri = $this->binarydataHandler->getUri($spiBinaryFile->id);
        }

        return $this->buildDomainBinaryFileObject($spiBinaryFile);
    }

    public function deleteBinaryFile(BinaryFileValue $binaryFile): void
    {
        $this->checkBinaryFileId($binaryFile->id);
        $spiUri = $this->getPrefixedUri($binaryFile->id);
        try {
            $this->metadataHandler->delete($spiUri);
        } catch (BinaryFileNotFoundException $e) {
            $this->binarydataHandler->delete($spiUri);
            throw $e;
        }

        $this->binarydataHandler->delete($spiUri);
    }

    public function loadBinaryFile(string $binaryFileId): BinaryFileValue
    {
        $this->checkBinaryFileId($binaryFileId);

        if ($this->isAbsolutePath($binaryFileId)) {
            throw new InvalidArgumentValue('$binaryFileId', "$binaryFileId is an absolute path");
        }

        $spiBinaryFile = $this->metadataHandler->load($this->getPrefixedUri($binaryFileId));
        if (!isset($spiBinaryFile->uri)) {
            $spiBinaryFile->uri = $this->binarydataHandler->getUri($spiBinaryFile->id);
        }

        return $this->buildDomainBinaryFileObject($spiBinaryFile);
    }

    public function loadBinaryFileByUri(string $binaryFileUri): BinaryFileValue
    {
        return $this->loadBinaryFile(
            $this->removeUriPrefix(
                $this->binarydataHandler->getIdFromUri($binaryFileUri)
            )
        );
    }

    public function getFileInputStream(BinaryFileValue $binaryFile): mixed
    {
        $this->checkBinaryFileId($binaryFile->id);

        return $this->binarydataHandler->getResource(
            $this->getPrefixedUri($binaryFile->id)
        );
    }

    public function getFileContents(BinaryFileValue $binaryFile): string
    {
        $this->checkBinaryFileId($binaryFile->id);

        return $this->binarydataHandler->getContents(
            $this->getPrefixedUri($binaryFile->id)
        );
    }

    public function getUri(string $binaryFileId): string
    {
        return $this->binarydataHandler->getUri($binaryFileId);
    }

    public function getMimeType(string $binaryFileId): ?string
    {
        return $this->metadataHandler->getMimeType($this->getPrefixedUri($binaryFileId));
    }

    public function exists(string $binaryFileId): bool
    {
        return $this->metadataHandler->exists($this->getPrefixedUri($binaryFileId));
    }

    /**
     * Generates BinaryFileCreateStruct object from the provided API BinaryFileCreateStruct object.
     */
    protected function buildSPIBinaryFileCreateStructObject(BinaryFileCreateStruct $binaryFileCreateStruct): PersistenceBinaryFileCreateStruct
    {
        $spiBinaryCreateStruct = new PersistenceBinaryFileCreateStruct();

        $spiBinaryCreateStruct->id = $this->getPrefixedUri($binaryFileCreateStruct->id ?? '');
        $spiBinaryCreateStruct->size = $binaryFileCreateStruct->size;
        $spiBinaryCreateStruct->setInputStream($binaryFileCreateStruct->inputStream);
        $spiBinaryCreateStruct->mimeType = $binaryFileCreateStruct->mimeType;
        $spiBinaryCreateStruct->mtime = new DateTime();

        return $spiBinaryCreateStruct;
    }

    /**
     * Generates an API BinaryFile object from the provided persistence layer BinaryFile object.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function buildDomainBinaryFileObject(PersistenceBinaryFile $spiBinaryFile): BinaryFileValue
    {
        return new BinaryFileValue(
            [
                'size' => $spiBinaryFile->size,
                'mtime' => $spiBinaryFile->mtime,
                'id' => $this->removeUriPrefix($spiBinaryFile->id),
                'uri' => $spiBinaryFile->uri,
            ]
        );
    }

    /**
     * Returns $uri prefixed with what is configured in the service.
     */
    protected function getPrefixedUri(string $binaryFileId): string
    {
        $prefix = '';
        if (isset($this->settings['prefix'])) {
            $prefix = $this->settings['prefix'] . '/';
        }

        return $prefix . $binaryFileId;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function removeUriPrefix(string $binaryFileId): string
    {
        if (!isset($this->settings['prefix'])) {
            return $binaryFileId;
        }

        if (!str_starts_with($binaryFileId, $this->settings['prefix'] . '/')) {
            throw new InvalidBinaryPrefixException($binaryFileId, $this->settings['prefix'] . '/');
        }

        return substr($binaryFileId, strlen($this->settings['prefix']) + 1);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the id is invalid
     */
    protected function checkBinaryFileId(string $binaryFileId): void
    {
        if (empty($binaryFileId)) {
            throw new InvalidBinaryFileIdException($binaryFileId);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $prefixedUri = $this->getPrefixedUri($path);
        $this->metadataHandler->deleteDirectory($prefixedUri);
        $this->binarydataHandler->deleteDirectory($prefixedUri);
    }

    /**
     * Check if a path is absolute, in terms of http or disk (incl if it contains a driver letter on Win).
     */
    protected function isAbsolutePath(string $path): bool
    {
        return $path[0] === '/' || (PHP_OS === 'WINNT' && $path[1] === ':');
    }
}

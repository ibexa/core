<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\IO;

use Exception;
use Ibexa\Contracts\Core\IO\BinaryFile as SPIBinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct as SPIBinaryFileCreateStruct;
use Ibexa\Contracts\Core\IO\MimeTypeDetector;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\Exception\InvalidBinaryFileIdException;
use Ibexa\Core\IO\Exception\InvalidBinaryPrefixException;
use Ibexa\Core\IO\Exception\IOException;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;

/**
 * The io service for managing binary files.
 */
class IOService implements IOServiceInterface
{
    /** @var \Ibexa\Core\IO\IOBinarydataHandler */
    protected $binarydataHandler;

    /** @var \Ibexa\Core\IO\IOMetadataHandler */
    protected $metadataHandler;

    /** @var \Ibexa\Contracts\Core\IO\MimeTypeDetector */
    protected $mimeTypeDetector;

    /** @var array */
    protected $settings;

    public function __construct(
        IOMetadataHandler $metadataHandler,
        IOBinarydataHandler $binarydataHandler,
        MimeTypeDetector $mimeTypeDetector,
        array $settings = []
    ) {
        $this->metadataHandler = $metadataHandler;
        $this->binarydataHandler = $binarydataHandler;
        $this->mimeTypeDetector = $mimeTypeDetector;
        $this->settings = $settings;
    }

    public function setPrefix($prefix)
    {
        $this->settings['prefix'] = $prefix;
    }

    public function newBinaryCreateStructFromUploadedFile(array $uploadedFile)
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

    public function newBinaryCreateStructFromLocalFile($localFile)
    {
        if (empty($localFile) || !is_string($localFile)) {
            throw new InvalidArgumentException('localFile', 'localFile has an invalid value');
        }

        if (!is_file($localFile) || !is_readable($localFile)) {
            throw new InvalidArgumentException('localFile', "file does not exist or is unreadable: {$localFile}");
        }

        $fileHandle = fopen($localFile, 'rb');
        if ($fileHandle === false) {
            throw new InvalidArgumentException('localFile', 'failed to get file resource');
        }

        $binaryCreateStruct = new BinaryFileCreateStruct(
            [
                'size' => filesize($localFile),
                'inputStream' => $fileHandle,
                'mimeType' => $this->mimeTypeDetector->getFromPath($localFile),
            ]
        );

        return $binaryCreateStruct;
    }

    public function createBinaryFile(BinaryFileCreateStruct $binaryFileCreateStruct)
    {
        if (empty($binaryFileCreateStruct->id) || !is_string($binaryFileCreateStruct->id)) {
            throw new InvalidArgumentValue('id', $binaryFileCreateStruct->id, 'BinaryFileCreateStruct');
        }

        if (!is_int($binaryFileCreateStruct->size) || $binaryFileCreateStruct->size < 0) {
            throw new InvalidArgumentValue('size', $binaryFileCreateStruct->size, 'BinaryFileCreateStruct');
        }

        if (!is_resource($binaryFileCreateStruct->inputStream)) {
            throw new InvalidArgumentValue('inputStream', 'property is not a file resource', 'BinaryFileCreateStruct');
        }

        if (!isset($binaryFileCreateStruct->mimeType)) {
            $buffer = fread($binaryFileCreateStruct->inputStream, $binaryFileCreateStruct->size);
            $binaryFileCreateStruct->mimeType = $this->mimeTypeDetector->getFromBuffer($buffer);
            unset($buffer);
        }

        $spiBinaryCreateStruct = $this->buildSPIBinaryFileCreateStructObject($binaryFileCreateStruct);

        try {
            $this->binarydataHandler->create($spiBinaryCreateStruct);
        } catch (Exception $e) {
            throw new IOException('An error occured when creating binary data', $e);
        }

        $spiBinaryFile = $this->metadataHandler->create($spiBinaryCreateStruct);
        if (!isset($spiBinaryFile->uri)) {
            $spiBinaryFile->uri = $this->binarydataHandler->getUri($spiBinaryFile->id);
        }

        return $this->buildDomainBinaryFileObject($spiBinaryFile);
    }

    public function deleteBinaryFile(BinaryFile $binaryFile)
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

    public function loadBinaryFile($binaryFileId)
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

    public function loadBinaryFileByUri($binaryFileUri)
    {
        return $this->loadBinaryFile(
            $this->removeUriPrefix(
                $this->binarydataHandler->getIdFromUri($binaryFileUri)
            )
        );
    }

    public function getFileInputStream(BinaryFile $binaryFile)
    {
        $this->checkBinaryFileId($binaryFile->id);

        return $this->binarydataHandler->getResource(
            $this->getPrefixedUri($binaryFile->id)
        );
    }

    public function getFileContents(BinaryFile $binaryFile)
    {
        $this->checkBinaryFileId($binaryFile->id);

        return $this->binarydataHandler->getContents(
            $this->getPrefixedUri($binaryFile->id)
        );
    }

    public function getUri($binaryFileId)
    {
        return $this->binarydataHandler->getUri($binaryFileId);
    }

    public function getMimeType($binaryFileId)
    {
        return $this->metadataHandler->getMimeType($this->getPrefixedUri($binaryFileId));
    }

    public function exists($binaryFileId)
    {
        return $this->metadataHandler->exists($this->getPrefixedUri($binaryFileId));
    }

    /**
     * Generates SPI BinaryFileCreateStruct object from provided API BinaryFileCreateStruct object.
     *
     * @param \Ibexa\Core\IO\Values\BinaryFileCreateStruct $binaryFileCreateStruct
     *
     * @return \Ibexa\Contracts\Core\IO\BinaryFileCreateStruct
     */
    protected function buildSPIBinaryFileCreateStructObject(BinaryFileCreateStruct $binaryFileCreateStruct)
    {
        $spiBinaryCreateStruct = new SPIBinaryFileCreateStruct();

        $spiBinaryCreateStruct->id = $this->getPrefixedUri($binaryFileCreateStruct->id);
        $spiBinaryCreateStruct->size = $binaryFileCreateStruct->size;
        $spiBinaryCreateStruct->setInputStream($binaryFileCreateStruct->inputStream);
        $spiBinaryCreateStruct->mimeType = $binaryFileCreateStruct->mimeType;
        $spiBinaryCreateStruct->mtime = new \DateTime();

        return $spiBinaryCreateStruct;
    }

    /**
     * Generates API BinaryFile object from provided SPI BinaryFile object.
     *
     * @param \Ibexa\Contracts\Core\IO\BinaryFile $spiBinaryFile
     *
     * @return \Ibexa\Core\IO\Values\BinaryFile
     */
    protected function buildDomainBinaryFileObject(SPIBinaryFile $spiBinaryFile)
    {
        return new BinaryFile(
            [
                'size' => (int)$spiBinaryFile->size,
                'mtime' => $spiBinaryFile->mtime,
                'id' => $this->removeUriPrefix($spiBinaryFile->id),
                'uri' => $spiBinaryFile->uri,
            ]
        );
    }

    /**
     * Returns $uri prefixed with what is configured in the service.
     *
     * @param string $binaryFileId
     *
     * @return string
     */
    protected function getPrefixedUri($binaryFileId)
    {
        $prefix = '';
        if (isset($this->settings['prefix'])) {
            $prefix = $this->settings['prefix'] . '/';
        }

        return $prefix . $binaryFileId;
    }

    /**
     * @param mixed $spiBinaryFileId
     *
     * @return string
     *
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryPrefixException
     */
    protected function removeUriPrefix($spiBinaryFileId)
    {
        if (!isset($this->settings['prefix'])) {
            return $spiBinaryFileId;
        }

        if (strpos($spiBinaryFileId, $this->settings['prefix'] . '/') !== 0) {
            throw new InvalidBinaryPrefixException($spiBinaryFileId, $this->settings['prefix'] . '/');
        }

        return substr($spiBinaryFileId, strlen($this->settings['prefix']) + 1);
    }

    /**
     * @param string $binaryFileId
     *
     * @throws \Ibexa\Core\IO\Exception\InvalidBinaryFileIdException If the id is invalid
     */
    protected function checkBinaryFileId($binaryFileId)
    {
        if (empty($binaryFileId) || !is_string($binaryFileId)) {
            throw new InvalidBinaryFileIdException($binaryFileId);
        }
    }

    /**
     * Deletes a directory.
     *
     * @param string $path
     */
    public function deleteDirectory($path)
    {
        $prefixedUri = $this->getPrefixedUri($path);
        $this->metadataHandler->deleteDirectory($prefixedUri);
        $this->binarydataHandler->deleteDirectory($prefixedUri);
    }

    /**
     * Check if path is absolute, in terms of http or disk (incl if it contains driver letter on Win).
     *
     * @param string $path
     *
     * @return bool
     */
    protected function isAbsolutePath($path)
    {
        return $path[0] === '/' || (PHP_OS === 'WINNT' && $path[1] === ':');
    }
}

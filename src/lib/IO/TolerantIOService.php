<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\Exception\InvalidBinaryAbsolutePathException;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\MissingBinaryFile;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * An extended IOService that tolerates physically missing files.
 *
 * Meant to be used on a "broken" instance where the storage directory isn't in sync with the database.
 *
 * Note that it will still return false when exists() is used.
 *
 * @internal
 */
class TolerantIOService extends IOService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function deleteBinaryFile(BinaryFile $binaryFile): void
    {
        $this->checkBinaryFileId($binaryFile->id);
        $spiUri = $this->getPrefixedUri($binaryFile->id);

        try {
            $this->metadataHandler->delete($spiUri);
        } catch (BinaryFileNotFoundException) {
            $this->logMissingFile($binaryFile->uri);
            $logged = true;
        }

        try {
            $this->binarydataHandler->delete($spiUri);
        } catch (BinaryFileNotFoundException) {
            if (!isset($logged)) {
                $this->logMissingFile($binaryFile->uri);
            }
        }
    }

    public function loadBinaryFile(string $binaryFileId): BinaryFile
    {
        $this->checkBinaryFileId($binaryFileId);

        if ($this->isAbsolutePath($binaryFileId)) {
            throw new InvalidBinaryAbsolutePathException($binaryFileId);
        }

        try {
            $spiBinaryFile = $this->metadataHandler->load($this->getPrefixedUri($binaryFileId));
        } catch (BinaryFileNotFoundException) {
            $this->logMissingFile($binaryFileId);

            return new MissingBinaryFile([
                'id' => $binaryFileId,
                'uri' => $this->binarydataHandler->getUri($this->getPrefixedUri($binaryFileId)),
            ]);
        }

        if (!isset($spiBinaryFile->uri)) {
            $spiBinaryFile->uri = $this->binarydataHandler->getUri($spiBinaryFile->id);
        }

        return $this->buildDomainBinaryFileObject($spiBinaryFile);
    }

    public function loadBinaryFileByUri(string $binaryFileUri): BinaryFile
    {
        $binaryFileId = $this->binarydataHandler->getIdFromUri($binaryFileUri);
        try {
            $binaryFileId = $this->removeUriPrefix($binaryFileId);
        } catch (InvalidArgumentException $e) {
            $this->logMissingFile($binaryFileUri);

            return new MissingBinaryFile([
                'id' => $binaryFileId,
                'uri' => $binaryFileUri,
            ]);
        }

        try {
            return $this->loadBinaryFile($binaryFileId);
        } catch (BinaryFileNotFoundException) {
            $this->logMissingFile($binaryFileUri);

            return new MissingBinaryFile([
                'id' => $binaryFileId,
                'uri' => $this->binarydataHandler->getUri($this->getPrefixedUri($binaryFileId)),
            ]);
        }
    }

    private function logMissingFile(string $id): void
    {
        $this->logger?->info("BinaryFile with id $id not found");
    }
}

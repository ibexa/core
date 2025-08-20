<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\IO\IOBinarydataHandler;

use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\Exception\IOException;
use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Core\IO\UrlDecorator;
use League\Flysystem\Config;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Visibility;

/**
 * @internal type-hint \Ibexa\Core\IO\IOBinarydataHandler instead
 */
final class Flysystem implements IOBinaryDataHandler
{
    private FilesystemOperator $filesystem;

    private ?UrlDecorator $urlDecorator;

    public function __construct(FilesystemOperator $filesystem, ?UrlDecorator $urlDecorator = null)
    {
        $this->filesystem = $filesystem;
        $this->urlDecorator = $urlDecorator;
    }

    public function create(BinaryFileCreateStruct $binaryFileCreateStruct): void
    {
        try {
            $this->filesystem->writeStream(
                $binaryFileCreateStruct->id,
                $binaryFileCreateStruct->getInputStream(),
                [
                    'mimetype' => $binaryFileCreateStruct->mimeType,
                    Config::OPTION_VISIBILITY => Visibility::PUBLIC,
                    Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PUBLIC,
                ]
            );
        } catch (FilesystemException $e) {
            throw new IOException("Failed to create file '{$binaryFileCreateStruct->id}'", $e);
        }
    }

    public function delete($spiBinaryFileId): void
    {
        try {
            $this->filesystem->delete($spiBinaryFileId);
        } catch (FilesystemException $e) {
            throw new BinaryFileNotFoundException($spiBinaryFileId, $e);
        }
    }

    public function getContents($spiBinaryFileId): string
    {
        try {
            return $this->filesystem->read($spiBinaryFileId);
        } catch (FilesystemException $e) {
            throw new BinaryFileNotFoundException($spiBinaryFileId, $e);
        }
    }

    public function getResource($spiBinaryFileId)
    {
        try {
            return $this->filesystem->readStream($spiBinaryFileId);
        } catch (FilesystemException $e) {
            throw new BinaryFileNotFoundException($spiBinaryFileId, $e);
        }
    }

    public function getUri($spiBinaryFileId): string
    {
        return null !== $this->urlDecorator
            ? $this->urlDecorator->decorate($spiBinaryFileId)
            : '/' . $spiBinaryFileId;
    }

    public function getIdFromUri($binaryFileUri): string
    {
        if (isset($this->urlDecorator)) {
            return $this->urlDecorator->undecorate($binaryFileUri);
        }

        return ltrim($binaryFileUri, '/');
    }

    public function deleteDirectory($spiPath): void
    {
        try {
            $this->filesystem->deleteDirectory($spiPath);
        } catch (FilesystemException $e) {
            throw new IOException("'Unable to delete directory '$spiPath'", $e);
        }
    }
}

class_alias(Flysystem::class, 'eZ\Publish\Core\IO\IOBinarydataHandler\Flysystem');

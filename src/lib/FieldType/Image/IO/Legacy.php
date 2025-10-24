<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Image\IO;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;
use Ibexa\Core\IO\Values\MissingBinaryFile;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * Legacy Image IOService.
 *
 * Acts as a dispatcher between the two IOService instances required by FieldType\Image in Legacy.
 * - One is the usual one, as used in ImageStorage, that uses 'images' as the prefix
 * - The other is a special one, that uses 'images-versioned' as the  prefix, in  order to cope with content created
 *   from the backoffice
 *
 * To load a binary file, this service will first try with the normal IOService,
 * and on exception, will fall back to the draft IOService.
 *
 * In addition, loadBinaryFile() will also hide the need to explicitly call getExternalPath()
 * on the internal path stored in legacy.
 *
 * @internal
 */
class Legacy implements IOServiceInterface
{
    /**
     * Published images IO Service.
     */
    private IOServiceInterface $publishedIOService;

    /**
     * Draft images IO Service.
     */
    private IOServiceInterface $draftIOService;

    /**
     * Prefix for published images.
     * Example: var/ibexa_demo_site/storage/images.
     */
    private string $publishedPrefix;

    /**
     * Prefix for draft images.
     * Example: var/ibexa_demo_site/storage/images-versioned.
     */
    private string $draftPrefix;

    private OptionsProvider $optionsProvider;

    /**
     * @param OptionsProvider $optionsProvider Path options. Known keys: var_dir, storage_dir, draft_images_dir, published_images_dir.
     *
     * @throws InvalidOptionsException
     *         If any of the passed options has not been defined or does not contain an allowed value
     * @throws MissingOptionsException
     *         If a required option is missing.
     */
    public function __construct(
        IOServiceInterface $publishedIOService,
        IOServiceInterface $draftIOService,
        OptionsProvider $optionsProvider
    ) {
        $this->publishedIOService = $publishedIOService;
        $this->draftIOService = $draftIOService;
        $this->optionsProvider = $optionsProvider;
        $this->setPrefixes();
    }

    /**
     * Sets the IOService prefix.
     */
    public function setPrefix(string $prefix): void
    {
        $this->publishedIOService->setPrefix($prefix);
        $this->draftIOService->setPrefix($prefix);
    }

    /**
     * Computes the paths to published & draft images path using the options from the provider.
     */
    private function setPrefixes(): void
    {
        $pathArray = [$this->optionsProvider->getVarDir()];

        // The storage dir itself might be null
        if ($storageDir = $this->optionsProvider->getStorageDir()) {
            $pathArray[] = $storageDir;
        }

        $this->draftPrefix = implode('/', array_merge($pathArray, [$this->optionsProvider->getDraftImagesDir()]));
        $this->publishedPrefix = implode('/', array_merge($pathArray, [$this->optionsProvider->getPublishedImagesDir()]));
    }

    public function newBinaryCreateStructFromLocalFile(string $localFile): BinaryFileCreateStruct
    {
        return $this->publishedIOService->newBinaryCreateStructFromLocalFile($localFile);
    }

    public function exists(string $binaryFileId): bool
    {
        return $this->publishedIOService->exists($binaryFileId);
    }

    public function loadBinaryFile(string $binaryFileId): BinaryFile
    {
        // If the id is an internal (absolute) path to a draft image, use the draft service to get external path & load
        if ($this->isDraftImagePath($binaryFileId)) {
            return $this->draftIOService->loadBinaryFileByUri($binaryFileId);
        }

        // If the id is an internal path (absolute) to a published image, replace with the internal path
        if ($this->isPublishedImagePath($binaryFileId)) {
            return $this->publishedIOService->loadBinaryFileByUri($binaryFileId);
        }

        try {
            $image = $this->publishedIOService->loadBinaryFile($binaryFileId);

            if ($image instanceof MissingBinaryFile) {
                throw new InvalidArgumentException('binaryFileId', sprintf('Cannot find file with ID %s', $binaryFileId));
            }

            return $image;
        } catch (InvalidArgumentException $prefixException) {
            // InvalidArgumentException means that the prefix didn't match, NotFound can pass through
            try {
                return $this->draftIOService->loadBinaryFile($binaryFileId);
            } catch (InvalidArgumentException $e) {
                throw $prefixException;
            }
        }
    }

    /**
     * Since both services should use the same uri, we can use any of them to *GET* the URI.
     *
     * {@inheritDoc}
     */
    public function loadBinaryFileByUri(string $binaryFileUri): BinaryFile
    {
        try {
            $image = $this->publishedIOService->loadBinaryFileByUri($binaryFileUri);

            if ($image instanceof MissingBinaryFile) {
                throw new InvalidArgumentException('binaryFileUri', sprintf('Cannot find file with URL %s', $binaryFileUri));
            }

            return $image;
        } catch (InvalidArgumentException $prefixException) {
            // InvalidArgumentException means that the prefix didn't match, NotFound can pass through
            try {
                return $this->draftIOService->loadBinaryFileByUri($binaryFileUri);
            } catch (InvalidArgumentException $e) {
                throw $prefixException;
            }
        }
    }

    public function getFileContents(BinaryFile $binaryFile): string
    {
        if ($this->draftIOService->exists($binaryFile->id)) {
            return $this->draftIOService->getFileContents($binaryFile);
        }

        return $this->publishedIOService->getFileContents($binaryFile);
    }

    public function createBinaryFile(BinaryFileCreateStruct $binaryFileCreateStruct): BinaryFile
    {
        return $this->publishedIOService->createBinaryFile($binaryFileCreateStruct);
    }

    public function getUri(string $binaryFileId): string
    {
        return $this->publishedIOService->getUri($binaryFileId);
    }

    public function getMimeType(string $binaryFileId): ?string
    {
        // If the id is an internal (absolute) path to a draft image, use the draft service to get external path & load
        if ($this->isDraftImagePath($binaryFileId)) {
            return $this->draftIOService->getMimeType(
                $this->draftIOService->loadBinaryFileByUri($binaryFileId)->id
            );
        }

        // If the id is an internal path (absolute) to a published image, replace with the internal path
        if ($this->isPublishedImagePath($binaryFileId)) {
            $binaryFileId = $this->publishedIOService->loadBinaryFileByUri($binaryFileId)->id;
        }

        if ($this->draftIOService->exists($binaryFileId)) {
            return $this->draftIOService->getMimeType($binaryFileId);
        }

        return $this->publishedIOService->getMimeType($binaryFileId);
    }

    public function getFileInputStream(BinaryFile $binaryFile): mixed
    {
        return $this->publishedIOService->getFileInputStream($binaryFile);
    }

    public function deleteBinaryFile(BinaryFile $binaryFile): void
    {
        $this->publishedIOService->deleteBinaryFile($binaryFile);
    }

    /**
     * Deletes a directory.
     *
     * @param string $path
     */
    public function deleteDirectory(string $path): void
    {
        $this->publishedIOService->deleteDirectory($path);
    }

    public function newBinaryCreateStructFromUploadedFile(array $uploadedFile): BinaryFileCreateStruct
    {
        return $this->publishedIOService->newBinaryCreateStructFromUploadedFile($uploadedFile);
    }

    /**
     * Checks if $internalPath is a published image path.
     *
     * @return bool true if $internalPath is the path to a published image
     */
    protected function isPublishedImagePath(string $internalPath): bool
    {
        return str_starts_with($internalPath, $this->publishedPrefix);
    }

    /**
     * Checks if $internalPath is a draft image path.
     *
     * @return bool true if $internalPath is the path to a draft image
     */
    protected function isDraftImagePath(string $internalPath): bool
    {
        return str_starts_with($internalPath, $this->draftPrefix);
    }
}

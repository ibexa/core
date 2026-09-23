<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO;

use Ibexa\Contracts\Core\MVC\EventSubscriber\ConfigScopeChangeSubscriber;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;

/**
 * @internal
 */
class ConfigScopeChangeAwareIOService implements IOServiceInterface, ConfigScopeChangeSubscriber
{
    private ConfigResolverInterface $configResolver;

    private IOServiceInterface $innerIOService;

    private string $prefixParameterName;

    private bool $prefixResolved = false;

    public function __construct(
        ConfigResolverInterface $configResolver,
        IOServiceInterface $innerIOService,
        string $prefixParameterName
    ) {
        $this->configResolver = $configResolver;
        $this->innerIOService = $innerIOService;
        $this->prefixParameterName = $prefixParameterName;
    }

    public function setPrefix(string $prefix): void
    {
        $this->innerIOService->setPrefix($prefix);
        $this->prefixResolved = true;
    }

    /**
     * Returns the inner IOService, resolving and setting its prefix lazily on first use so that
     * the ConfigResolver is never read in the constructor (its scope may not be final yet then).
     */
    private function getInnerIOService(): IOServiceInterface
    {
        if (!$this->prefixResolved) {
            $this->setPrefix($this->configResolver->getParameter($this->prefixParameterName));
        }

        return $this->innerIOService;
    }

    public function newBinaryCreateStructFromLocalFile(string $localFile): BinaryFileCreateStruct
    {
        return $this->getInnerIOService()->newBinaryCreateStructFromLocalFile($localFile);
    }

    public function exists(string $binaryFileId): bool
    {
        return $this->getInnerIOService()->exists($binaryFileId);
    }

    public function loadBinaryFile(string $binaryFileId): BinaryFile
    {
        return $this->getInnerIOService()->loadBinaryFile($binaryFileId);
    }

    public function loadBinaryFileByUri(string $binaryFileUri): BinaryFile
    {
        return $this->getInnerIOService()->loadBinaryFileByUri($binaryFileUri);
    }

    public function getFileContents(BinaryFile $binaryFile): string
    {
        return $this->getInnerIOService()->getFileContents($binaryFile);
    }

    public function createBinaryFile(BinaryFileCreateStruct $binaryFileCreateStruct): BinaryFile
    {
        return $this->getInnerIOService()->createBinaryFile($binaryFileCreateStruct);
    }

    public function getUri(string $binaryFileId): string
    {
        return $this->getInnerIOService()->getUri($binaryFileId);
    }

    public function getMimeType(string $binaryFileId): ?string
    {
        return $this->getInnerIOService()->getMimeType($binaryFileId);
    }

    public function getFileInputStream(BinaryFile $binaryFile): mixed
    {
        return $this->getInnerIOService()->getFileInputStream($binaryFile);
    }

    public function deleteBinaryFile(BinaryFile $binaryFile): void
    {
        $this->getInnerIOService()->deleteBinaryFile($binaryFile);
    }

    public function newBinaryCreateStructFromUploadedFile(array $uploadedFile): BinaryFileCreateStruct
    {
        return $this->getInnerIOService()->newBinaryCreateStructFromUploadedFile($uploadedFile);
    }

    public function deleteDirectory(string $path): void
    {
        $this->getInnerIOService()->deleteDirectory($path);
    }

    public function onConfigScopeChange(ScopeChangeEvent $event): void
    {
        $this->prefixResolved = false;
    }
}

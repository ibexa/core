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

    public function __construct(
        ConfigResolverInterface $configResolver,
        IOServiceInterface $innerIOService,
        string $prefixParameterName
    ) {
        $this->configResolver = $configResolver;
        $this->innerIOService = $innerIOService;
        $this->prefixParameterName = $prefixParameterName;

        // set initial prefix on inner IOService
        $this->setPrefix($this->configResolver->getParameter($this->prefixParameterName));
    }

    public function setPrefix(string $prefix): void
    {
        $this->innerIOService->setPrefix($prefix);
    }

    public function newBinaryCreateStructFromLocalFile(string $localFile): BinaryFileCreateStruct
    {
        return $this->innerIOService->newBinaryCreateStructFromLocalFile($localFile);
    }

    public function exists(string $binaryFileId): bool
    {
        return $this->innerIOService->exists($binaryFileId);
    }

    public function loadBinaryFile(string $binaryFileId): BinaryFile
    {
        return $this->innerIOService->loadBinaryFile($binaryFileId);
    }

    public function loadBinaryFileByUri(string $binaryFileUri): BinaryFile
    {
        return $this->innerIOService->loadBinaryFileByUri($binaryFileUri);
    }

    public function getFileContents(BinaryFile $binaryFile): string
    {
        return $this->innerIOService->getFileContents($binaryFile);
    }

    public function createBinaryFile(BinaryFileCreateStruct $binaryFileCreateStruct): BinaryFile
    {
        return $this->innerIOService->createBinaryFile($binaryFileCreateStruct);
    }

    public function getUri(string $binaryFileId): string
    {
        return $this->innerIOService->getUri($binaryFileId);
    }

    public function getMimeType(string $binaryFileId): ?string
    {
        return $this->innerIOService->getMimeType($binaryFileId);
    }

    public function getFileInputStream(BinaryFile $binaryFile): mixed
    {
        return $this->innerIOService->getFileInputStream($binaryFile);
    }

    public function deleteBinaryFile(BinaryFile $binaryFile): void
    {
        $this->innerIOService->deleteBinaryFile($binaryFile);
    }

    public function newBinaryCreateStructFromUploadedFile(array $uploadedFile): BinaryFileCreateStruct
    {
        return $this->innerIOService->newBinaryCreateStructFromUploadedFile($uploadedFile);
    }

    public function deleteDirectory(string $path): void
    {
        $this->innerIOService->deleteDirectory($path);
    }

    public function onConfigScopeChange(ScopeChangeEvent $event): void
    {
        $this->setPrefix($this->configResolver->getParameter($this->prefixParameterName));
    }
}

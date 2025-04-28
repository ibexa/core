<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\IO\IOBinarydataHandler;

use Ibexa\Bundle\IO\ApiLoader\HandlerRegistry;
use Ibexa\Contracts\Core\IO\BinaryFile;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\IO\IOMetadataHandler;

/**
 * @internal
 */
final class SiteAccessDependentMetadataHandler implements IOMetadataHandler
{
    private ConfigResolverInterface $configResolver;

    private HandlerRegistry $dataHandlerRegistry;

    public function __construct(
        ConfigResolverInterface $configResolver,
        HandlerRegistry $dataHandlerRegistry
    ) {
        $this->configResolver = $configResolver;
        $this->dataHandlerRegistry = $dataHandlerRegistry;
    }

    private function getHandler(): IOMetadataHandler
    {
        return $this->dataHandlerRegistry->getConfiguredHandler(
            $this->configResolver->getParameter('io.metadata_handler')
        );
    }

    public function create(BinaryFileCreateStruct $spiBinaryFileCreateStruct): BinaryFile
    {
        return $this->getHandler()->create($spiBinaryFileCreateStruct);
    }

    public function delete(string $binaryFileId): void
    {
        $this->getHandler()->delete($binaryFileId);
    }

    public function load(string $binaryFileId): BinaryFile
    {
        return $this->getHandler()->load($binaryFileId);
    }

    public function exists(string $binaryFileId): bool
    {
        return $this->getHandler()->exists($binaryFileId);
    }

    public function getMimeType(string $binaryFileId): string
    {
        return $this->getHandler()->getMimeType($binaryFileId);
    }

    public function deleteDirectory(string $pathName): void
    {
        $this->getHandler()->deleteDirectory($pathName);
    }
}

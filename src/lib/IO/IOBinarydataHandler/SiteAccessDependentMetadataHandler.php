<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

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
    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface */
    private ConfigResolverInterface $configResolver;

    /** @phpstan-var \Ibexa\Bundle\IO\ApiLoader\HandlerRegistry<\Ibexa\Core\IO\IOMetadataHandler> */
    private HandlerRegistry $dataHandlerRegistry;

    /**
     * @phpstan-param \Ibexa\Bundle\IO\ApiLoader\HandlerRegistry<\Ibexa\Core\IO\IOMetadataHandler> $dataHandlerRegistry
     */
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

    public function load(string $spiBinaryFileId): BinaryFile
    {
        return $this->getHandler()->load($spiBinaryFileId);
    }

    public function exists(string $spiBinaryFileId): bool
    {
        return $this->getHandler()->exists($spiBinaryFileId);
    }

    public function getMimeType(string $spiBinaryFileId): string
    {
        return $this->getHandler()->getMimeType($spiBinaryFileId);
    }

    public function deleteDirectory(string $path): void
    {
        $this->getHandler()->deleteDirectory($path);
    }
}

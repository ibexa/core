<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\IOBinarydataHandler;

use Ibexa\Bundle\IO\ApiLoader\HandlerRegistry;
use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\IO\IOBinarydataHandler;

/**
 * @internal
 */
final class SiteAccessDependentBinaryDataHandler implements IOBinarydataHandler
{
    private ConfigResolverInterface $configResolver;

    /** @phpstan-var HandlerRegistry<IOBinarydataHandler> */
    private HandlerRegistry $dataHandlerRegistry;

    /**
     * @phpstan-param HandlerRegistry<IOBinarydataHandler> $dataHandlerRegistry
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        HandlerRegistry $dataHandlerRegistry
    ) {
        $this->configResolver = $configResolver;
        $this->dataHandlerRegistry = $dataHandlerRegistry;
    }

    private function getHandler(): IOBinarydataHandler
    {
        return $this->dataHandlerRegistry->getConfiguredHandler(
            $this->configResolver->getParameter('io.binarydata_handler')
        );
    }

    public function create(BinaryFileCreateStruct $binaryFileCreateStruct): void
    {
        $this->getHandler()->create($binaryFileCreateStruct);
    }

    public function delete(string $binaryFileId): void
    {
        $this->getHandler()->delete($binaryFileId);
    }

    public function getContents(string $spiBinaryFileId): string
    {
        return $this->getHandler()->getContents($spiBinaryFileId);
    }

    public function getResource(string $spiBinaryFileId): mixed
    {
        return $this->getHandler()->getResource($spiBinaryFileId);
    }

    public function getUri(string $spiBinaryFileId): string
    {
        return $this->getHandler()->getUri($spiBinaryFileId);
    }

    public function getIdFromUri(string $binaryFileUri): string
    {
        return $this->getHandler()->getIdFromUri($binaryFileUri);
    }

    public function deleteDirectory(string $path): void
    {
        $this->getHandler()->deleteDirectory($path);
    }
}

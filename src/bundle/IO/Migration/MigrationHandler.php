<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\Migration;

use Ibexa\Bundle\IO\ApiLoader\HandlerRegistry;
use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Core\IO\IOMetadataHandler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * The migration handler sets up from/to IO data handlers, and provides logging, for file migrators and listers.
 */
abstract class MigrationHandler implements MigrationHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected IOMetadataHandler $fromMetadataHandler;

    protected IOBinarydataHandler $fromBinarydataHandler;

    protected IOMetadataHandler $toMetadataHandler;

    protected IOBinarydataHandler $toBinarydataHandler;

    /**
     * @param \Ibexa\Bundle\IO\ApiLoader\HandlerRegistry<\Ibexa\Core\IO\IOMetadataHandler> $metadataHandlerRegistry
     * @param \Ibexa\Bundle\IO\ApiLoader\HandlerRegistry<\Ibexa\Core\IO\IOBinarydataHandler> $binarydataHandlerRegistry
     */
    public function __construct(
        private readonly HandlerRegistry $metadataHandlerRegistry,
        private readonly HandlerRegistry $binarydataHandlerRegistry,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger;
    }

    public function setIODataHandlersByIdentifiers(
        string $fromMetadataHandlerIdentifier,
        string $fromBinarydataHandlerIdentifier,
        string $toMetadataHandlerIdentifier,
        string $toBinarydataHandlerIdentifier
    ): MigrationHandler {
        $this->fromMetadataHandler = $this->metadataHandlerRegistry->getConfiguredHandler($fromMetadataHandlerIdentifier);
        $this->fromBinarydataHandler = $this->binarydataHandlerRegistry->getConfiguredHandler($fromBinarydataHandlerIdentifier);
        $this->toMetadataHandler = $this->metadataHandlerRegistry->getConfiguredHandler($toMetadataHandlerIdentifier);
        $this->toBinarydataHandler = $this->binarydataHandlerRegistry->getConfiguredHandler($toBinarydataHandlerIdentifier);

        return $this;
    }

    final protected function logError(string $message): void
    {
        if (isset($this->logger)) {
            $this->logger->error($message);
        }
    }

    final protected function logInfo(string $message): void
    {
        if (isset($this->logger)) {
            $this->logger->info($message);
        }
    }

    final protected function logMissingFile(string $id): void
    {
        $this->logInfo("File with id $id not found");
    }
}

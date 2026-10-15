<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\ApiLoader;

use Ibexa\Bundle\Core\ApiLoader\Exception\InvalidStorageEngine;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Contracts\Core\Persistence\Handler;
use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;

/**
 * The storage engine factory.
 */
class StorageEngineFactory
{
    /**
     * Hash of registered storage engines.
     * Key is the storage engine identifier, value persistence handler itself.
     *
     * @var Handler[]
     */
    protected array $storageEngines = [];

    public function __construct(
        private readonly RepositoryConfigurationProviderInterface $repositoryConfigurationProvider,
    ) {}

    /**
     * Registers $persistenceHandler as a valid storage engine, with identifier $storageEngineIdentifier.
     *
     * Note: It is strongly recommenced to register a lazy persistent handler.
     */
    public function registerStorageEngine(
        PersistenceHandler $persistenceHandler,
        string $storageEngineIdentifier
    ): void {
        $this->storageEngines[$storageEngineIdentifier] = $persistenceHandler;
    }

    /**
     * @return Handler[]
     */
    public function getStorageEngines(): array
    {
        return $this->storageEngines;
    }

    /**
     * Builds storage engine identified by $storageEngineIdentifier (the "alias" attribute in the service tag).
     *
     * @throws InvalidArgumentException
     */
    public function buildStorageEngine(): PersistenceHandler
    {
        $repositoryConfig = $this->repositoryConfigurationProvider->getRepositoryConfig();

        $storageEngineAlias = $repositoryConfig['storage']['engine'] ?? null;
        if (null === $storageEngineAlias) {
            throw new InvalidStorageEngine(
                sprintf(
                    'Ibexa "%s" Repository has no Storage Engine configured',
                    $this->repositoryConfigurationProvider->getCurrentRepositoryAlias()
                )
            );
        }

        if (!isset($this->storageEngines[$storageEngineAlias])) {
            throw new InvalidStorageEngine(
                "Invalid storage engine '$storageEngineAlias'. " .
                'Could not find any service tagged with ibexa.storage ' .
                "with alias $storageEngineAlias."
            );
        }

        return $this->storageEngines[$repositoryConfig['storage']['engine']];
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\ApiLoader;

use Ibexa\Bundle\Core\ApiLoader\Exception\InvalidStorageEngine;
use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;

/**
 * The storage engine factory.
 */
class StorageEngineFactory
{
    /** @var \Ibexa\Bundle\Core\ApiLoader\RepositoryConfigurationProvider */
    private $repositoryConfigurationProvider;

    /**
     * Hash of registered storage engines.
     * Key is the storage engine identifier, value persistence handler itself.
     *
     * @var \Ibexa\Contracts\Core\Persistence\Handler[]
     */
    protected $storageEngines = [];

    public function __construct(RepositoryConfigurationProvider $repositoryConfigurationProvider)
    {
        $this->repositoryConfigurationProvider = $repositoryConfigurationProvider;
    }

    /**
     * Registers $persistenceHandler as a valid storage engine, with identifier $storageEngineIdentifier.
     *
     * Note: It is strongly recommenced to register a lazy persistent handler.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Handler $persistenceHandler
     * @param string $storageEngineIdentifier
     */
    public function registerStorageEngine(PersistenceHandler $persistenceHandler, $storageEngineIdentifier)
    {
        $this->storageEngines[$storageEngineIdentifier] = $persistenceHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Handler[]
     */
    public function getStorageEngines()
    {
        return $this->storageEngines;
    }

    /**
     * Builds storage engine identified by $storageEngineIdentifier (the "alias" attribute in the service tag).
     *
     * @throws \Ibexa\Bundle\Core\ApiLoader\Exception\InvalidStorageEngine
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
                "Invalid storage engine '{$storageEngineAlias}'. " .
                'Could not find any service tagged with ibexa.storage ' .
                "with alias {$storageEngineAlias}."
            );
        }

        return $this->storageEngines[$repositoryConfig['storage']['engine']];
    }
}

class_alias(StorageEngineFactory::class, 'eZ\Bundle\EzPublishCoreBundle\ApiLoader\StorageEngineFactory');

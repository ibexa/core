<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\ApiLoader;

use Ibexa\Bundle\Core\ApiLoader\Exception\InvalidSearchEngine;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Contracts\Core\Search\Handler;
use Ibexa\Contracts\Core\Search\Handler as SearchHandler;

/**
 * The search engine factory.
 */
class SearchEngineFactory
{
    /**
     * Hash of registered search engines.
     * Key is the search engine identifier, value search handler itself.
     *
     * @var Handler[]
     */
    protected $searchEngines = [];

    public function __construct(
        private readonly RepositoryConfigurationProviderInterface $repositoryConfigurationProvider,
    ) {}

    /**
     * Registers $searchHandler as a valid search engine with identifier $searchEngineIdentifier.
     *
     * Note It is strongly recommended to register a lazy persistent handler.
     *
     * @param Handler $searchHandler
     * @param string $searchEngineIdentifier
     */
    public function registerSearchEngine(
        SearchHandler $searchHandler,
        $searchEngineIdentifier
    ) {
        $this->searchEngines[$searchEngineIdentifier] = $searchHandler;
    }

    /**
     * Returns registered search engines.
     *
     * @return Handler[]
     */
    public function getSearchEngines()
    {
        return $this->searchEngines;
    }

    /**
     * Builds search engine identified by its identifier (the "alias" attribute in the service tag),
     * resolved for current SiteAccess.
     *
     * @throws InvalidSearchEngine
     */
    public function buildSearchEngine(): SearchHandler
    {
        $repositoryConfig = $this->repositoryConfigurationProvider->getRepositoryConfig();

        $searchEngineAlias = $repositoryConfig['search']['engine'] ?? null;
        if (null === $searchEngineAlias) {
            throw new InvalidSearchEngine(
                sprintf(
                    'Ibexa "%s" Repository has no Search Engine configured',
                    $this->repositoryConfigurationProvider->getCurrentRepositoryAlias()
                )
            );
        }

        if (!isset($this->searchEngines[$searchEngineAlias])) {
            throw new InvalidSearchEngine(
                "Invalid search engine '{$searchEngineAlias}'. " .
                "Could not find any service tagged with 'ibexa.search.engine' " .
                "with alias '{$searchEngineAlias}'."
            );
        }

        return $this->searchEngines[$searchEngineAlias];
    }
}

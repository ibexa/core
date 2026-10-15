<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\LegacySearchEngine\ApiLoader;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConnectionFactory
{
    protected ContainerInterface $container;

    protected RepositoryConfigurationProviderInterface $repositoryConfigurationProvider;

    public function __construct(
        ContainerInterface $container,
        RepositoryConfigurationProviderInterface $repositoryConfigurationProvider
    ) {
        $this->container = $container;
        $this->repositoryConfigurationProvider = $repositoryConfigurationProvider;
    }

    /**
     * Returns database connection used by database handler.
     *
     * @throws InvalidArgumentException
     */
    public function getConnection(): Connection
    {
        $repositoryConfig = $this->repositoryConfigurationProvider->getRepositoryConfig();
        // Taking provided connection name if any.
        // Otherwise, just fallback to the default connection.

        if (isset($repositoryConfig['search']['connection'])) {
            $doctrineConnectionId = sprintf('doctrine.dbal.%s_connection', $repositoryConfig['search']['connection']);
        } else {
            // "database_connection" is an alias to the default connection, set up by DoctrineBundle.
            $doctrineConnectionId = 'database_connection';
        }

        if (!$this->container->has($doctrineConnectionId)) {
            throw new InvalidArgumentException(
                "Invalid Doctrine connection '{$repositoryConfig['search']['connection']}' for Repository '{$repositoryConfig['alias']}'." .
                'Valid connections are: ' . implode(', ', array_keys($this->container->getParameter('doctrine.connections')))
            );
        }

        return $this->container->get($doctrineConnectionId);
    }
}

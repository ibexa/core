<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\ApiLoader;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class StorageConnectionFactory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected RepositoryConfigurationProviderInterface $repositoryConfigurationProvider;

    public function __construct(RepositoryConfigurationProviderInterface $repositoryConfigurationProvider)
    {
        $this->repositoryConfigurationProvider = $repositoryConfigurationProvider;
    }

    /**
     * Returns database connection used by database handler.
     *
     * @throws \InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getConnection(): Connection
    {
        $repositoryConfig = $this->repositoryConfigurationProvider->getRepositoryConfig();
        // Taking provided connection name if any.
        // Otherwise, just fallback to the default connection.

        if (isset($repositoryConfig['storage']['connection'])) {
            $doctrineConnectionId = sprintf('doctrine.dbal.%s_connection', $repositoryConfig['storage']['connection']);
        } else {
            // "database_connection" is an alias to the default connection, set up by DoctrineBundle.
            $doctrineConnectionId = 'database_connection';
        }

        if (!$this->container?->has($doctrineConnectionId)) {
            /** @var string[] $doctrineConnections */
            $doctrineConnections = $this->container?->getParameter('doctrine.connections') ?? [];
            throw new InvalidArgumentException(
                "Invalid Doctrine connection '$doctrineConnectionId' for Repository '{$repositoryConfig['alias']}'." .
                'Valid connections are: ' . implode(', ', array_keys($doctrineConnections))
            );
        }

        /** @return \Doctrine\DBAL\Connection  */
        return $this->container->get($doctrineConnectionId);
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\ApiLoader;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 */
final readonly class StorageConnectionFactory
{
    /**
     * @param array<string, string> $doctrineConnections
     */
    public function __construct(
        private RepositoryConfigurationProviderInterface $repositoryConfigurationProvider,
        private ServiceLocator $serviceLocator,
        private array $doctrineConnections,
    ) {
    }

    /**
     * Returns database connection used by database handler.
     *
     * @throws \InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getConnection(): Connection
    {
        $repositoryConfig = $this->repositoryConfigurationProvider->getRepositoryConfig();
        // Taking provided connection name if any.
        // Otherwise, just fallback to the default connection.

        $connectionName = $repositoryConfig['storage']['connection'] ?? 'default';
        if (!$this->serviceLocator->has($connectionName)) {
            throw new InvalidArgumentException(
                "Invalid Doctrine connection '$connectionName' for Repository '{$repositoryConfig['alias']}'. " .
                'Valid connections are: ' . implode(', ', array_keys($this->doctrineConnections))
            );
        }

        return $this->serviceLocator->get($connectionName);
    }
}

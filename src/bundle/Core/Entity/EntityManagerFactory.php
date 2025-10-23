<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 */
class EntityManagerFactory
{
    /** @phpstan-var ServiceLocator<EntityManagerInterface> */
    private ServiceLocator $serviceLocator;

    /** @var string */
    private string $defaultConnection;

    /** @var array<string, string> */
    private array $entityManagers;

    /**
     * @phpstan-param ServiceLocator<EntityManagerInterface> $serviceLocator
     */
    public function __construct(
        private readonly RepositoryConfigurationProviderInterface $repositoryConfigurationProvider,
        ServiceLocator $serviceLocator,
        string $defaultConnection,
        array $entityManagers
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->defaultConnection = $defaultConnection;
        $this->entityManagers = $entityManagers;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        $repositoryConfig = $this->repositoryConfigurationProvider->getRepositoryConfig();

        if (isset($repositoryConfig['storage']['connection'])) {
            $entityManagerId = $this->getEntityManagerServiceId($repositoryConfig['storage']['connection']);
        } else {
            $defaultEntityManagerId = $this->getEntityManagerServiceId($this->defaultConnection);
            $entityManagerId = $this->serviceLocator->has($defaultEntityManagerId)
                ? $defaultEntityManagerId
                : 'doctrine.orm.entity_manager';
        }

        if (!$this->serviceLocator->has($entityManagerId)) {
            throw new \InvalidArgumentException(
                "Invalid Doctrine Entity Manager '{$entityManagerId}' for Repository '{$repositoryConfig['alias']}'. " .
                'Valid Entity Managers are: ' . implode(', ', array_keys($this->entityManagers))
            );
        }

        return $this->serviceLocator->get($entityManagerId);
    }

    protected function getEntityManagerServiceId(string $connection): string
    {
        return sprintf('doctrine.orm.ibexa_%s_entity_manager', $connection);
    }
}

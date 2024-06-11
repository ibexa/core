<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\ApiLoader;

use Doctrine\DBAL\Connection;
use Ibexa\Bundle\Core\ApiLoader\Exception\InvalidRepositoryException;
use Ibexa\Bundle\Core\ApiLoader\StorageConnectionFactory;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Container\ApiLoader\RepositoryConfigurationProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StorageConnectionFactoryTest extends BaseRepositoryConfigurationProviderTestCase
{
    /**
     * @dataProvider getConnectionProvider
     */
    public function testGetConnection(string $repositoryAlias, string $doctrineConnection): void
    {
        $repositories = [
            $repositoryAlias => $this->buildNormalizedSingleRepositoryConfig('legacy', $doctrineConnection),
        ];

        $configResolver = $this->getConfigResolverMock();
        $configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('repository')
            ->willReturn($repositoryAlias);

        $container = $this->getContainerMock();
        $container
            ->expects(self::once())
            ->method('has')
            ->with("doctrine.dbal.{$doctrineConnection}_connection")
            ->willReturn(true);
        $container
            ->expects(self::once())
            ->method('get')
            ->with("doctrine.dbal.{$doctrineConnection}_connection")
            ->willReturn($this->createMock(Connection::class));

        $repositoryConfigurationProvider = new RepositoryConfigurationProvider($configResolver, $repositories);
        $factory = new StorageConnectionFactory($repositoryConfigurationProvider);
        $factory->setContainer($container);
        $connection = $factory->getConnection();
        self::assertInstanceOf(Connection::class, $connection);
    }

    /**
     * @return list<array{string, string}>
     */
    public function getConnectionProvider(): array
    {
        return [
            ['my_repository', 'my_doctrine_connection'],
            ['foo', 'default'],
            ['répository_de_dédé', 'la_connexion_de_bébêrt'],
        ];
    }

    public function testGetConnectionInvalidRepository(): void
    {
        $repositories = [
            'foo' => $this->buildNormalizedSingleRepositoryConfig('legacy', 'my_doctrine_connection'),
        ];

        $configResolver = $this->getConfigResolverMock();
        $configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('repository')
            ->willReturn('nonexistent_repository');

        $repositoryConfigurationProvider = new RepositoryConfigurationProvider($configResolver, $repositories);
        $factory = new StorageConnectionFactory($repositoryConfigurationProvider);
        $factory->setContainer($this->getContainerMock());

        $this->expectException(InvalidRepositoryException::class);
        $factory->getConnection();
    }

    public function testGetConnectionInvalidConnection(): void
    {
        $repositoryConfigurationProviderMock = $this->createMock(RepositoryConfigurationProviderInterface::class);
        $repositoryConfig = [
            'alias' => 'foo',
            'storage' => [
                'engine' => 'legacy',
                'connection' => 'my_doctrine_connection',
            ],
        ];
        $repositoryConfigurationProviderMock
            ->expects(self::once())
            ->method('getRepositoryConfig')
            ->willReturn($repositoryConfig);

        $container = $this->getContainerMock();
        $container
            ->expects(self::once())
            ->method('has')
            ->with('doctrine.dbal.my_doctrine_connection_connection')
            ->willReturn(false);
        $container
            ->expects(self::once())
            ->method('getParameter')
            ->with('doctrine.connections')
            ->willReturn([]);
        $factory = new StorageConnectionFactory($repositoryConfigurationProviderMock);
        $factory->setContainer($container);

        $this->expectException(\InvalidArgumentException::class);
        $factory->getConnection();
    }

    /**
     * @return \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigResolverMock(): ConfigResolverInterface
    {
        return $this->createMock(ConfigResolverInterface::class);
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getContainerMock(): ContainerInterface
    {
        return $this->createMock(ContainerInterface::class);
    }
}

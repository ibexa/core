<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\ApiLoader;

use Doctrine\DBAL\Connection;
use Ibexa\Bundle\Core\ApiLoader\Exception\InvalidRepositoryException;
use Ibexa\Bundle\Core\ApiLoader\StorageConnectionFactory;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Container\ApiLoader\RepositoryConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class StorageConnectionFactoryTest extends BaseRepositoryConfigurationProviderTestCase
{
    /**
     * @dataProvider getConnectionProvider
     */
    public function testGetConnection(
        string $repositoryAlias,
        string $doctrineConnection
    ): void {
        $repositories = [
            $repositoryAlias => $this->buildNormalizedSingleRepositoryConfig('legacy', $doctrineConnection),
        ];

        $configResolver = $this->getConfigResolverMock();
        $configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('repository')
            ->willReturn($repositoryAlias)
        ;

        $repositoryConfigurationProvider = new RepositoryConfigurationProvider($configResolver, $repositories);
        $factory = $this->buildStorageConnectionFactory(
            $repositoryConfigurationProvider,
            $doctrineConnection,
            [$doctrineConnection => "doctrine.dbal.{$doctrineConnection}_connection"]
        );
        $connection = $factory->getConnection();
        self::assertInstanceOf(Connection::class, $connection);
    }

    /**
     * @return list<array{string, string}>
     */
    public static function getConnectionProvider(): array
    {
        return [
            ['my_repository', 'my_doctrine_connection'],
            ['foo', 'default'],
            ['répository_de_dédé', 'la_connexion_de_bébêrt'],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
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
            ->willReturn('nonexistent_repository')
        ;

        $repositoryConfigurationProvider = new RepositoryConfigurationProvider($configResolver, $repositories);
        $factory = $this->buildStorageConnectionFactory($repositoryConfigurationProvider);

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
            ->willReturn($repositoryConfig)
        ;

        $factory = $this->buildStorageConnectionFactory(
            $repositoryConfigurationProviderMock,
            'my_doctrine_connection',
            [
                'default' => 'doctrine.dbal.default_connection',
                'foo' => 'doctrine.dbal.foo_connection',
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid Doctrine connection \'my_doctrine_connection\' for Repository \'foo\'. Valid connections are: default, foo'
        );
        $factory->getConnection();
    }

    protected function getConfigResolverMock(): ConfigResolverInterface & MockObject
    {
        return $this->createMock(ConfigResolverInterface::class);
    }

    protected function getContainerMock(): ContainerInterface & MockObject
    {
        return $this->createMock(ContainerInterface::class);
    }

    /**
     * @param array<string, string> $doctrineConnections
     */
    private function buildStorageConnectionFactory(
        RepositoryConfigurationProviderInterface $repositoryConfigurationProvider,
        string $connectionName = 'default',
        array $doctrineConnections = ['default' => 'doctrine.dbal.default_connection']
    ): StorageConnectionFactory {
        $serviceLocatorMock = $this->createMock(ServiceLocator::class);
        $serviceLocatorMock
            ->method('has')
            ->with($connectionName)
            ->willReturn(isset($doctrineConnections[$connectionName]))
        ;
        $serviceLocatorMock->method('getProvidedServices')->willReturn($doctrineConnections);
        if (isset($doctrineConnections[$connectionName])) {
            $serviceLocatorMock->method('get')->with($connectionName)->willReturn($this->createMock(Connection::class));
        } else {
            $serviceLocatorMock->expects(self::never())->method('get');
        }

        return new StorageConnectionFactory(
            $repositoryConfigurationProvider,
            $serviceLocatorMock
        );
    }
}

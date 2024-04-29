<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\ApiLoader;

use Ibexa\Bundle\Core\ApiLoader\Exception\InvalidRepositoryException;
use Ibexa\Bundle\Core\ApiLoader\RepositoryConfigurationProvider;
use Ibexa\Bundle\Core\ApiLoader\StorageConnectionFactory;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StorageConnectionFactoryTest extends TestCase
{
    /**
     * @dataProvider getConnectionProvider
     */
    public function testGetConnection($repositoryAlias, $doctrineConnection)
    {
        $repositories = [
            $repositoryAlias => [
                'storage' => [
                    'engine' => 'legacy',
                    'connection' => $doctrineConnection,
                ],
            ],
        ];

        $configResolver = $this->getConfigResolverMock();
        $configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('repository')
            ->will(self::returnValue($repositoryAlias));

        $container = $this->getContainerMock();
        $container
            ->expects(self::once())
            ->method('has')
            ->with("doctrine.dbal.{$doctrineConnection}_connection")
            ->will(self::returnValue(true));
        $container
            ->expects(self::once())
            ->method('get')
            ->with("doctrine.dbal.{$doctrineConnection}_connection")
            ->will(self::returnValue($this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock()));

        $repositoryConfigurationProvider = new RepositoryConfigurationProvider($configResolver, $repositories);
        $factory = new StorageConnectionFactory($repositoryConfigurationProvider);
        $factory->setContainer($container);
        $connection = $factory->getConnection();
        self::assertInstanceOf(
            'Doctrine\DBAL\Connection',
            $connection
        );
    }

    public function getConnectionProvider()
    {
        return [
            ['my_repository', 'my_doctrine_connection'],
            ['foo', 'default'],
            ['répository_de_dédé', 'la_connexion_de_bébêrt'],
        ];
    }

    public function testGetConnectionInvalidRepository()
    {
        $this->expectException(InvalidRepositoryException::class);

        $repositories = [
            'foo' => [
                'storage' => [
                    'engine' => 'legacy',
                    'connection' => 'my_doctrine_connection',
                ],
            ],
        ];

        $configResolver = $this->getConfigResolverMock();
        $configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('repository')
            ->will(self::returnValue('inexistent_repository'));

        $repositoryConfigurationProvider = new RepositoryConfigurationProvider($configResolver, $repositories);
        $factory = new StorageConnectionFactory($repositoryConfigurationProvider);
        $factory->setContainer($this->getContainerMock());
        $factory->getConnection();
    }

    public function testGetConnectionInvalidConnection()
    {
        $this->expectException(\InvalidArgumentException::class);

        $repositoryConfigurationProviderMock = $this->createMock(RepositoryConfigurationProvider::class);
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
            ->will(self::returnValue($repositoryConfig));

        $container = $this->getContainerMock();
        $container
            ->expects(self::once())
            ->method('has')
            ->with('doctrine.dbal.my_doctrine_connection_connection')
            ->will(self::returnValue(false));
        $container
            ->expects(self::once())
            ->method('getParameter')
            ->with('doctrine.connections')
            ->will(self::returnValue([]));
        $factory = new StorageConnectionFactory($repositoryConfigurationProviderMock);
        $factory->setContainer($container);
        $factory->getConnection();
    }

    protected function getConfigResolverMock()
    {
        return $this->createMock(ConfigResolverInterface::class);
    }

    protected function getContainerMock()
    {
        return $this->createMock(ContainerInterface::class);
    }
}

class_alias(StorageConnectionFactoryTest::class, 'eZ\Bundle\EzPublishCoreBundle\Tests\ApiLoader\StorageConnectionFactoryTest');

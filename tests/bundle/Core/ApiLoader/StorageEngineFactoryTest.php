<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\ApiLoader;

use Ibexa\Bundle\Core\ApiLoader\Exception\InvalidStorageEngine;
use Ibexa\Bundle\Core\ApiLoader\StorageEngineFactory;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Contracts\Core\Persistence\Handler;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Container\ApiLoader\RepositoryConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;

final class StorageEngineFactoryTest extends BaseRepositoryConfigurationProviderTestCase
{
    public function testRegisterStorageEngine(): void
    {
        $repositoryConfigurationProvider = $this->createMock(RepositoryConfigurationProviderInterface::class);
        $factory = new StorageEngineFactory($repositoryConfigurationProvider);

        $storageEngines = [
            'foo' => $this->getPersistenceHandlerMock(),
            'bar' => $this->getPersistenceHandlerMock(),
            'baz' => $this->getPersistenceHandlerMock(),
        ];

        foreach ($storageEngines as $identifier => $persistenceHandler) {
            $factory->registerStorageEngine($persistenceHandler, $identifier);
        }

        self::assertSame($storageEngines, $factory->getStorageEngines());
    }

    public function testBuildStorageEngine(): void
    {
        $configResolver = $this->getConfigResolverMock();
        $repositoryAlias = 'main';
        $repositories = [
            $repositoryAlias => $this->buildNormalizedSingleRepositoryConfig('foo'),
            'another' => $this->buildNormalizedSingleRepositoryConfig('bar'),
        ];
        $expectedStorageEngine = $this->getPersistenceHandlerMock();
        $storageEngines = [
            'foo' => $expectedStorageEngine,
            'bar' => $this->getPersistenceHandlerMock(),
            'baz' => $this->getPersistenceHandlerMock(),
        ];
        $repositoryConfigurationProvider = new RepositoryConfigurationProvider($configResolver, $repositories);
        $factory = new StorageEngineFactory($repositoryConfigurationProvider);
        foreach ($storageEngines as $identifier => $persistenceHandler) {
            $factory->registerStorageEngine($persistenceHandler, $identifier);
        }

        $configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('repository')
            ->willReturn($repositoryAlias)
        ;

        self::assertSame($expectedStorageEngine, $factory->buildStorageEngine());
    }

    public function testBuildInvalidStorageEngine(): void
    {
        $this->expectException(InvalidStorageEngine::class);

        $configResolver = $this->getConfigResolverMock();
        $repositoryAlias = 'main';
        $repositories = [
            $repositoryAlias => $this->buildNormalizedSingleRepositoryConfig('undefined_storage_engine'),
            'another' => $this->buildNormalizedSingleRepositoryConfig('bar'),
        ];

        $storageEngines = [
            'foo' => $this->getPersistenceHandlerMock(),
            'bar' => $this->getPersistenceHandlerMock(),
            'baz' => $this->getPersistenceHandlerMock(),
        ];

        $repositoryConfigurationProvider = new RepositoryConfigurationProvider($configResolver, $repositories);
        $factory = new StorageEngineFactory($repositoryConfigurationProvider);
        foreach ($storageEngines as $identifier => $persistenceHandler) {
            $factory->registerStorageEngine($persistenceHandler, $identifier);
        }

        $configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('repository')
            ->willReturn($repositoryAlias)
        ;

        self::assertSame($this->getPersistenceHandlerMock(), $factory->buildStorageEngine());
    }

    protected function getConfigResolverMock(): ConfigResolverInterface & MockObject
    {
        return $this->createMock(ConfigResolverInterface::class);
    }

    protected function getPersistenceHandlerMock(): Handler & MockObject
    {
        return $this->createMock(Handler::class);
    }
}

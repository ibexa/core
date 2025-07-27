<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\ApiLoader;

use Ibexa\Bundle\Core\ApiLoader\Exception\InvalidRepositoryException;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Container\ApiLoader\RepositoryConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\Base\Container\ApiLoader\RepositoryConfigurationProvider
 *
 * @phpstan-import-type TRepositoryListConfiguration from \Ibexa\Core\Base\Container\ApiLoader\RepositoryConfigurationProvider
 */
final class RepositoryConfigurationProviderTest extends BaseRepositoryConfigurationProviderTestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testGetRepositoryConfigSpecifiedRepository(): void
    {
        $configResolver = $this->getConfigResolverMock();
        // providing normalized configuration, expected at this point
        // see \Ibexa\Bundle\Core\DependencyInjection\Configuration::addRepositoriesSection for more details
        $provider = new RepositoryConfigurationProvider($configResolver, self::REPOSITORIES_CONFIG);

        $configResolver
            ->method('getParameter')
            ->with('repository')
            ->willReturn(self::MAIN_REPOSITORY_ALIAS);

        self::assertSame(
            ['alias' => self::MAIN_REPOSITORY_ALIAS] + self::MAIN_REPOSITORY_CONFIG,
            $provider->getRepositoryConfig()
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testGetRepositoryConfigNotSpecifiedRepository(): void
    {
        $configResolver = $this->getConfigResolverMock();
        $provider = new RepositoryConfigurationProvider($configResolver, self::REPOSITORIES_CONFIG);

        $configResolver
            ->method('getParameter')
            ->with('repository')
            ->willReturn(null);

        self::assertSame(
            ['alias' => self::MAIN_REPOSITORY_ALIAS] + self::MAIN_REPOSITORY_CONFIG,
            $provider->getRepositoryConfig()
        );
    }

    /**
     * @dataProvider providerForRepositories
     *
     * @phpstan-param TRepositoryListConfiguration $repositories
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testGetRepositoryConfigUndefinedRepository(array $repositories): void
    {
        $this->expectException(InvalidRepositoryException::class);

        $configResolver = $this->getConfigResolverMock();

        $configResolver
            ->method('getParameter')
            ->with('repository')
            ->willReturn('undefined_repository');

        $provider = new RepositoryConfigurationProvider($configResolver, $repositories);
        self::assertSame([], $provider->getRepositoryConfig());
    }

    /**
     * @dataProvider providerForRepositories
     *
     * @phpstan-param TRepositoryListConfiguration $repositories
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testGetDefaultRepositoryAlias(array $repositories): void
    {
        $configResolver = $this->getConfigResolverMock();

        $provider = new RepositoryConfigurationProvider($configResolver, $repositories);
        $provider->getRepositoryConfig();

        self::assertSame(self::MAIN_REPOSITORY_ALIAS, $provider->getDefaultRepositoryAlias());
    }

    /**
     * @dataProvider providerForRepositories
     *
     * @phpstan-param TRepositoryListConfiguration $repositories
     */
    public function testGetCurrentRepositoryAlias(array $repositories): void
    {
        $configResolver = $this->getConfigResolverMock();

        $provider = new RepositoryConfigurationProvider($configResolver, $repositories);
        $provider->getRepositoryConfig();

        self::assertSame(self::MAIN_REPOSITORY_ALIAS, $provider->getCurrentRepositoryAlias());
    }

    /**
     * @phpstan-return list<list<TRepositoryListConfiguration>> $repositories
     */
    public function providerForRepositories(): array
    {
        return [
            [self::REPOSITORIES_CONFIG],
        ];
    }

    protected function getConfigResolverMock(): ConfigResolverInterface & MockObject
    {
        return $this->createMock(ConfigResolverInterface::class);
    }
}

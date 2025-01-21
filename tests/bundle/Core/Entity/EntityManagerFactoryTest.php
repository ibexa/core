<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Ibexa\Bundle\Core\Entity\EntityManagerFactory;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @covers \Ibexa\Bundle\Core\Entity\EntityManagerFactory
 */
final class EntityManagerFactoryTest extends TestCase
{
    private const string DEFAULT_ENTITY_MANAGER = 'doctrine.orm.ibexa_default_entity_manager';
    private const string INVALID_ENTITY_MANAGER = 'doctrine.orm.ibexa_invalid_entity_manager';
    private const string DEFAULT_CONNECTION = 'default';
    private const array ENTITY_MANAGERS = [
        'ibexa_default' => self::DEFAULT_ENTITY_MANAGER,
        'ibexa_invalid' => self::INVALID_ENTITY_MANAGER,
    ];

    private RepositoryConfigurationProviderInterface & MockObject $repositoryConfigurationProvider;

    private EntityManagerInterface & MockObject $entityManager;

    /** @phpstan-var \Symfony\Component\DependencyInjection\ServiceLocator<\Doctrine\ORM\EntityManagerInterface> & \PHPUnit\Framework\MockObject\MockObject */
    private ServiceLocator & MockObject $serviceLocator;

    public function setUp(): void
    {
        $this->repositoryConfigurationProvider = $this->getRepositoryConfigurationProvider();
        $this->entityManager = $this->getEntityManager();
        $this->serviceLocator = $this->getServiceLocator();
    }

    public function testGetEntityManager(): void
    {
        $this->serviceLocator
            ->method('has')
            ->with(self::DEFAULT_ENTITY_MANAGER)
            ->willReturn(true);
        $this->serviceLocator
            ->method('get')
            ->with(self::DEFAULT_ENTITY_MANAGER)
            ->willReturn($this->getEntityManager());

        $this->repositoryConfigurationProvider
            ->method('getRepositoryConfig')
            ->willReturn([
                'alias' => 'my_repository',
                'storage' => [
                    'connection' => 'default',
                ],
            ]);

        $entityManagerFactory = new EntityManagerFactory(
            $this->repositoryConfigurationProvider,
            $this->serviceLocator,
            self::DEFAULT_CONNECTION,
            self::ENTITY_MANAGERS
        );

        self::assertEquals($this->getEntityManager(), $entityManagerFactory->getEntityManager());
    }

    public function testGetEntityManagerWillUseDefaultConnection(): void
    {
        $serviceLocator = $this->getServiceLocator();
        $serviceLocator
            ->method('has')
            ->with(self::DEFAULT_ENTITY_MANAGER)
            ->willReturn(true);
        $serviceLocator
            ->method('get')
            ->with(self::DEFAULT_ENTITY_MANAGER)
            ->willReturn($this->entityManager);

        $this->repositoryConfigurationProvider
            ->method('getRepositoryConfig')
            ->willReturn([
                'storage' => [],
            ]);

        $entityManagerFactory = new EntityManagerFactory(
            $this->repositoryConfigurationProvider,
            $serviceLocator,
            self::DEFAULT_CONNECTION,
            self::ENTITY_MANAGERS
        );

        self::assertEquals($this->entityManager, $entityManagerFactory->getEntityManager());
    }

    public function testGetEntityManagerInvalid(): void
    {
        $serviceLocator = $this->getServiceLocator();

        $serviceLocator
            ->method('has')
            ->with(self::INVALID_ENTITY_MANAGER)
            ->willReturn(false);

        $this->repositoryConfigurationProvider
            ->method('getRepositoryConfig')
            ->willReturn([
                'alias' => 'invalid',
                'storage' => [
                    'connection' => 'invalid',
                ],
            ]);

        $entityManagerFactory = new EntityManagerFactory(
            $this->repositoryConfigurationProvider,
            $serviceLocator,
            'default',
            [
                'default' => 'doctrine.orm.default_entity_manager',
            ]
        );

        $this->expectException(InvalidArgumentException::class);

        $entityManagerFactory->getEntityManager();
    }

    protected function getRepositoryConfigurationProvider(): RepositoryConfigurationProviderInterface & MockObject
    {
        return $this->createMock(RepositoryConfigurationProviderInterface::class);
    }

    /**
     * @phpstan-return \Symfony\Component\DependencyInjection\ServiceLocator<\Doctrine\ORM\EntityManagerInterface> & \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getServiceLocator(): ServiceLocator & MockObject
    {
        return $this->createMock(ServiceLocator::class);
    }

    protected function getEntityManager(): EntityManagerInterface & MockObject
    {
        return $this->createMock(EntityManagerInterface::class);
    }
}

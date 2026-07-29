<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Doctrine;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Ibexa\Bundle\Core\Entity\EntityManagerFactory;
use Ibexa\Contracts\Core\MVC\EventSubscriber\ConfigScopeChangeSubscriber;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\Persistence\Doctrine\SiteAccessAwareEntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Contracts\Service\ResetInterface;

final class SiteAccessAwareEntityManagerTest extends TestCase
{
    private EntityManagerFactory&MockObject $entityManagerFactory;

    private SiteAccessAwareEntityManager $siteAccessAwareEntityManager;

    protected function setUp(): void
    {
        $this->entityManagerFactory = $this->createMock(EntityManagerFactory::class);
        $this->siteAccessAwareEntityManager = new SiteAccessAwareEntityManager($this->entityManagerFactory);
    }

    public function testImplementsExpectedInterfaces(): void
    {
        $implementedInterfaces = class_implements($this->siteAccessAwareEntityManager);

        self::assertContains(EntityManagerInterface::class, $implementedInterfaces);
        self::assertContains(ConfigScopeChangeSubscriber::class, $implementedInterfaces);
        self::assertContains(ResetInterface::class, $implementedInterfaces);
    }

    public function testFindDelegatesToWrappedEntityManager(): void
    {
        $entity = new stdClass();
        $wrapped = $this->createMock(EntityManagerInterface::class);
        $wrapped->expects(self::once())
            ->method('find')
            ->with(stdClass::class, 1, LockMode::OPTIMISTIC, 2)
            ->willReturn($entity);
        $this->entityManagerFactory->method('getEntityManager')->willReturn($wrapped);

        $result = $this->siteAccessAwareEntityManager->find(stdClass::class, 1, LockMode::OPTIMISTIC, 2);

        self::assertSame($entity, $result);
    }

    public function testRefreshDelegatesToWrappedEntityManager(): void
    {
        $entity = new stdClass();
        $wrapped = $this->createMock(EntityManagerInterface::class);
        $wrapped->expects(self::once())
            ->method('refresh')
            ->with($entity, LockMode::PESSIMISTIC_WRITE);
        $this->entityManagerFactory->method('getEntityManager')->willReturn($wrapped);

        $this->siteAccessAwareEntityManager->refresh($entity, LockMode::PESSIMISTIC_WRITE);
    }

    public function testLockDelegatesToWrappedEntityManager(): void
    {
        $entity = new stdClass();
        $wrapped = $this->createMock(EntityManagerInterface::class);
        $wrapped->expects(self::once())
            ->method('lock')
            ->with($entity, LockMode::OPTIMISTIC, 3);
        $this->entityManagerFactory->method('getEntityManager')->willReturn($wrapped);

        $this->siteAccessAwareEntityManager->lock($entity, LockMode::OPTIMISTIC, 3);
    }

    public function testGetRepositoryDelegatesToWrappedEntityManager(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $wrapped = $this->createMock(EntityManagerInterface::class);
        $wrapped->expects(self::once())
            ->method('getRepository')
            ->with(stdClass::class)
            ->willReturn($repository);
        $this->entityManagerFactory->method('getEntityManager')->willReturn($wrapped);

        $result = $this->siteAccessAwareEntityManager->getRepository(stdClass::class);

        self::assertSame($repository, $result);
    }

    public function testGetClassMetadataDelegatesToWrappedEntityManager(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $wrapped = $this->createMock(EntityManagerInterface::class);
        $wrapped->expects(self::once())
            ->method('getClassMetadata')
            ->with(stdClass::class)
            ->willReturn($metadata);
        $this->entityManagerFactory->method('getEntityManager')->willReturn($wrapped);

        $result = $this->siteAccessAwareEntityManager->getClassMetadata(stdClass::class);

        self::assertSame($metadata, $result);
    }

    public function testGetMetadataFactoryDelegatesToWrappedEntityManager(): void
    {
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $wrapped = $this->createMock(EntityManagerInterface::class);
        $wrapped->expects(self::once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $this->entityManagerFactory->method('getEntityManager')->willReturn($wrapped);

        $result = $this->siteAccessAwareEntityManager->getMetadataFactory();

        self::assertSame($metadataFactory, $result);
    }

    public function testWrappedEntityManagerIsResolvedOnlyOnce(): void
    {
        $wrapped = $this->createMock(EntityManagerInterface::class);
        $this->entityManagerFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($wrapped);

        $this->siteAccessAwareEntityManager->isOpen();
        $this->siteAccessAwareEntityManager->isOpen();
        $this->siteAccessAwareEntityManager->isOpen();
    }

    public function testResetForcesReResolutionOfWrappedEntityManager(): void
    {
        $first = $this->createMock(EntityManagerInterface::class);
        $second = $this->createMock(EntityManagerInterface::class);
        $this->entityManagerFactory->expects(self::exactly(2))
            ->method('getEntityManager')
            ->willReturnOnConsecutiveCalls($first, $second);

        $this->siteAccessAwareEntityManager->isOpen();
        $this->siteAccessAwareEntityManager->reset();
        $this->siteAccessAwareEntityManager->isOpen();
    }

    public function testConfigScopeChangeForcesReResolutionOfWrappedEntityManager(): void
    {
        $first = $this->createMock(EntityManagerInterface::class);
        $second = $this->createMock(EntityManagerInterface::class);
        $this->entityManagerFactory->expects(self::exactly(2))
            ->method('getEntityManager')
            ->willReturnOnConsecutiveCalls($first, $second);

        $this->siteAccessAwareEntityManager->isOpen();
        $this->siteAccessAwareEntityManager->onConfigScopeChange(
            new ScopeChangeEvent(new SiteAccess('test'))
        );
        $this->siteAccessAwareEntityManager->isOpen();
    }
}

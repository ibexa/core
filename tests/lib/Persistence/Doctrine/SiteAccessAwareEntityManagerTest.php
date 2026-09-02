<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Doctrine;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Ibexa\Bundle\Core\Entity\EntityManagerFactory;
use Ibexa\Core\Persistence\Doctrine\SiteAccessAwareEntityManager;
use PHPUnit\Framework\TestCase;
use stdClass;

final class SiteAccessAwareEntityManagerTest extends TestCase
{
    /** @var \Doctrine\ORM\EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private EntityManagerInterface $wrappedEntityManager;

    private SiteAccessAwareEntityManager $entityManager;

    protected function setUp(): void
    {
        $this->wrappedEntityManager = $this->createMock(EntityManagerInterface::class);

        $entityManagerFactory = $this->createMock(EntityManagerFactory::class);
        $entityManagerFactory
            ->method('getEntityManager')
            ->willReturn($this->wrappedEntityManager);

        $this->entityManager = new SiteAccessAwareEntityManager($entityManagerFactory);
    }

    public function testFindForwardsLockModeAndLockVersion(): void
    {
        $entity = new stdClass();

        $this->wrappedEntityManager
            ->expects(self::once())
            ->method('find')
            ->with(stdClass::class, 1, LockMode::PESSIMISTIC_WRITE, 2)
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->entityManager->find(stdClass::class, 1, LockMode::PESSIMISTIC_WRITE, 2)
        );
    }

    public function testFindWithoutLockArgumentsForwardsNullDefaults(): void
    {
        $entity = new stdClass();

        $this->wrappedEntityManager
            ->expects(self::once())
            ->method('find')
            ->with(stdClass::class, 1, null, null)
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->entityManager->find(stdClass::class, 1)
        );
    }
}

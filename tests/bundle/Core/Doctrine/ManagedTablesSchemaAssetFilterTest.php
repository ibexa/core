<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Doctrine;

use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Ibexa\Bundle\Core\Doctrine\ManagedTablesSchemaAssetFilter;
use PHPUnit\Framework\TestCase;

final class ManagedTablesSchemaAssetFilterTest extends TestCase
{
    /**
     * @param string[] $entityTableNames
     */
    private function createFilter(array $entityTableNames): ManagedTablesSchemaAssetFilter
    {
        $classMetadataList = array_map(
            function (string $tableName): ClassMetadata {
                $classMetadata = $this->createMock(ClassMetadata::class);
                $classMetadata->method('getTableName')->willReturn($tableName);

                return $classMetadata;
            },
            $entityTableNames
        );

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->method('getAllMetadata')->willReturn($classMetadataList);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getMetadataFactory')->willReturn($metadataFactory);

        $nonEntityManager = $this->createMock(ObjectManager::class);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagers')->willReturn([$entityManager, $nonEntityManager]);

        return new ManagedTablesSchemaAssetFilter($managerRegistry);
    }

    public function testAllowsTablesBackedByARegisteredEntity(): void
    {
        $filter = $this->createFilter(['ibexa_taxonomy_entry', 'ibexa_payment_token']);

        self::assertTrue($filter('ibexa_taxonomy_entry'));
        self::assertTrue($filter('ibexa_payment_token'));
    }

    public function testProtectsTablesNotBackedByAnyRegisteredEntity(): void
    {
        $filter = $this->createFilter(['ibexa_taxonomy_entry']);

        self::assertFalse($filter('ibexa_content_language'));
        self::assertFalse($filter('ibexa_migrations'));
    }

    public function testAcceptsAnAbstractAssetInstance(): void
    {
        $filter = $this->createFilter(['ibexa_taxonomy_entry']);

        self::assertTrue($filter(new Table('ibexa_taxonomy_entry')));
        self::assertFalse($filter(new Table('ibexa_content_language')));
    }

    public function testOnlyQueriesTheManagerRegistryOnce(): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())->method('getTableName')->willReturn('ibexa_taxonomy_entry');

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects(self::once())->method('getAllMetadata')->willReturn([$classMetadata]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getMetadataFactory')->willReturn($metadataFactory);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::once())->method('getManagers')->willReturn([$entityManager]);

        $filter = new ManagedTablesSchemaAssetFilter($managerRegistry);

        $filter('ibexa_taxonomy_entry');
        $filter('ibexa_content_language');
    }
}

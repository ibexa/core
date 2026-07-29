<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Ibexa\Bundle\Core\Doctrine\ManagedTablesSchemaAssetFilter;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;
use PHPUnit\Framework\TestCase;

final class ManagedTablesSchemaAssetFilterTest extends TestCase
{
    /**
     * @param string[] $entityTableNames
     * @param string[] $legacySchemaTableNames
     */
    private function createFilter(array $entityTableNames, array $legacySchemaTableNames = []): ManagedTablesSchemaAssetFilter
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

        $legacySchema = new Schema();
        foreach ($legacySchemaTableNames as $tableName) {
            $legacySchema->createTable($tableName);
        }

        $schemaBuilder = $this->createMock(SchemaBuilderInterface::class);
        $schemaBuilder->method('buildSchema')->willReturn($legacySchema);

        return new ManagedTablesSchemaAssetFilter($managerRegistry, $schemaBuilder);
    }

    public function testAllowsTablesBackedByARegisteredEntity(): void
    {
        $filter = $this->createFilter(['ibexa_taxonomy_entry', 'ibexa_payment_token']);

        self::assertTrue($filter('ibexa_taxonomy_entry'));
        self::assertTrue($filter('ibexa_payment_token'));
    }

    public function testAllowsTablesDeclaredInTheLegacySchema(): void
    {
        $filter = $this->createFilter([], ['ibexa_content_language', 'ibexa_migrations']);

        self::assertTrue($filter('ibexa_content_language'));
        self::assertTrue($filter('ibexa_migrations'));
    }

    public function testProtectsTablesNotBackedByAnEntityOrDeclaredInTheLegacySchema(): void
    {
        $filter = $this->createFilter(['ibexa_taxonomy_entry'], ['ibexa_content_language']);

        self::assertFalse($filter('some_unrelated_leftover_table'));
    }

    public function testAcceptsAnAbstractAssetInstance(): void
    {
        $filter = $this->createFilter(['ibexa_taxonomy_entry'], ['ibexa_content_language']);

        self::assertTrue($filter(new Table('ibexa_taxonomy_entry')));
        self::assertTrue($filter(new Table('ibexa_content_language')));
        self::assertFalse($filter(new Table('some_unrelated_leftover_table')));
    }

    public function testOnlyBuildsTheManagedTableListsOnce(): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getTableName')->willReturn('ibexa_taxonomy_entry');

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects(self::once())->method('getAllMetadata')->willReturn([$classMetadata]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getMetadataFactory')->willReturn($metadataFactory);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::once())->method('getManagers')->willReturn([$entityManager]);

        $schemaBuilder = $this->createMock(SchemaBuilderInterface::class);
        $schemaBuilder->expects(self::once())->method('buildSchema')->willReturn(new Schema());

        $filter = new ManagedTablesSchemaAssetFilter($managerRegistry, $schemaBuilder);

        $filter('ibexa_taxonomy_entry');
        $filter('some_unrelated_leftover_table');
    }
}

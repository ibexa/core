<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Event\Subscriber;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolEvents;
use Ibexa\Bundle\RepositoryInstaller\Event\Subscriber\OrmEntitiesSchemaSubscriber;
use Ibexa\Contracts\DoctrineSchema\Event\SchemaBuilderEvent;
use Ibexa\Contracts\DoctrineSchema\SchemaBuilderEvents;
use Ibexa\Contracts\DoctrineSchema\SchemaImporterInterface;
use Ibexa\DoctrineSchema\Builder\SchemaBuilder;
use Ibexa\Tests\Bundle\RepositoryInstaller\Event\Subscriber\Fixtures\Entity\Category;
use Ibexa\Tests\Bundle\RepositoryInstaller\Event\Subscriber\Fixtures\Entity\Item;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Uses a real (SQLite, in-memory) entity manager over XML-mapped fixture entities, since the
 * subscriber's whole job is to turn real ORM metadata into DBAL schema objects.
 */
#[CoversClass(OrmEntitiesSchemaSubscriber::class)]
final class OrmEntitiesSchemaSubscriberTest extends TestCase
{
    private const ENTITY_CLASSES = [Category::class, Item::class];

    private EventManager $eventManager;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $configuration = new Configuration();
        $configuration->setMetadataDriverImpl(new SimplifiedXmlDriver(
            [__DIR__ . '/Fixtures/orm' => 'Ibexa\Tests\Bundle\RepositoryInstaller\Event\Subscriber\Fixtures\Entity'],
            isXsdValidationEnabled: true,
        ));
        $configuration->setProxyDir(sys_get_temp_dir());
        $configuration->setProxyNamespace('Ibexa\Tests\Bundle\RepositoryInstaller\Event\Subscriber\Proxies');
        // symfony/var-exporter 8 no longer ships the LazyGhost proxies ORM falls back to
        $configuration->enableNativeLazyObjects(true);

        $this->eventManager = new EventManager();
        $this->entityManager = new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            $configuration,
            $this->eventManager,
        );
    }

    /**
     * Compared, rather than by their SQL, as a table added to the schema lists the index backing a
     * foreign key after the other ones.
     */
    #[DataProvider('providePlatforms')]
    public function testAddsTablesOfListedEntitiesAsSchemaToolGeneratesThem(AbstractPlatform $platform): void
    {
        $schema = $this->buildSchema();

        $diff = (new Comparator($platform))->compareSchemas($this->getSchemaToolSchema(), $schema);

        self::assertSame([], $platform->getAlterSchemaSQL($diff));
    }

    /**
     * @return iterable<string, array{AbstractPlatform}>
     */
    public static function providePlatforms(): iterable
    {
        yield 'MySQL' => [new MySQLPlatform()];
        yield 'PostgreSQL' => [new PostgreSQLPlatform()];
        // The SQLite platform class was renamed between DBAL 3 and 4 - let DBAL pick it.
        yield 'SQLite' => [DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true])->getDatabasePlatform()];
    }

    public function testAddsSequencesOfListedEntitiesAsSchemaToolGeneratesThem(): void
    {
        $schema = $this->buildSchema();

        self::assertSame(
            $this->getCreateSequencesSql($this->getSchemaToolSchema()),
            $this->getCreateSequencesSql($schema),
        );
        self::assertNotEmpty($this->getCreateSequencesSql($schema));
    }

    public function testIgnoresTablesAndSequencesOtherListenersAddToTheOrmSchema(): void
    {
        $this->eventManager->addEventListener(ToolEvents::postGenerateSchema, new class() {
            public function postGenerateSchema(GenerateSchemaEventArgs $args): void
            {
                $args->getSchema()->createTable('test_orm_foreign')->addColumn('id', 'integer');
                $args->getSchema()->createSequence('test_orm_foreign_id_seq');
            }
        });

        $schema = $this->buildSchema();

        self::assertTrue($schema->hasTable('test_orm_item'));
        self::assertFalse($schema->hasTable('test_orm_foreign'));
        self::assertTrue($schema->hasSequence('test_orm_item_id_seq'));
        self::assertFalse($schema->hasSequence('test_orm_foreign_id_seq'));
    }

    public function testLeavesTablesAlreadyInTheSchemaUntouched(): void
    {
        $schema = $this->buildSchema(
            Table::editor()
                ->setUnquotedName('test_orm_category')
                ->setColumns(Column::editor()->setUnquotedName('legacy_id')->setTypeName('integer')->create())
                ->create()
        );

        $columnNames = array_map(
            static fn (Column $column): string => $column->getName(),
            array_values($schema->getTable('test_orm_category')->getColumns()),
        );

        self::assertSame(['legacy_id'], $columnNames);
        self::assertTrue($schema->hasTable('test_orm_item'));
    }

    private function getSchemaToolSchema(): Schema
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();

        return (new SchemaTool($this->entityManager))->getSchemaFromMetadata(array_map(
            static fn (string $class): ClassMetadata => $metadataFactory->getMetadataFor($class),
            self::ENTITY_CLASSES,
        ));
    }

    /**
     * Sequences are compared on PostgreSQL only, as neither MySQL nor SQLite supports them.
     *
     * @return list<string>
     */
    private function getCreateSequencesSql(Schema $schema): array
    {
        $platform = new PostgreSQLPlatform();

        return array_map(
            static fn (Sequence $sequence): string => $platform->getCreateSequenceSQL($sequence),
            $schema->getSequences(),
        );
    }

    /**
     * Builds the schema with the subscriber, after another one added the given tables.
     */
    private function buildSchema(Table ...$existingTables): Schema
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            SchemaBuilderEvents::BUILD_SCHEMA,
            static function (SchemaBuilderEvent $event) use ($existingTables): void {
                foreach ($existingTables as $table) {
                    $event->addTable($table);
                }
            },
            300,
        );
        $eventDispatcher->addSubscriber(new OrmEntitiesSchemaSubscriber($this->entityManager, self::ENTITY_CLASSES));

        return (new SchemaBuilder(
            $eventDispatcher,
            self::createStub(SchemaImporterInterface::class),
        ))->buildSchema();
    }
}

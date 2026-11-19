<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\AbortMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AbstractAddObjectStateForeignKeyMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddObjectStateGroupForeignKeyMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddObjectStateGroupLanguageGroupForeignKeyMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddObjectStateLanguageStateForeignKeyMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddObjectStateLinkContentForeignKeyMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddObjectStateLinkStateForeignKeyMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\MigrationRunContext;
use Ibexa\Contracts\DoctrineSchema\Database\DefaultTableOptions;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use Ibexa\Tests\Core\Repository\LegacySchemaImporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;

#[CoversClass(AbstractAddObjectStateForeignKeyMigration::class)]
#[CoversClass(AddObjectStateLinkContentForeignKeyMigration::class)]
final class ObjectStateForeignKeysMigrationTest extends TestCase
{
    private const int LANGUAGE_ID = 2;
    private const int GROUP_ID = 2;
    private const int NOT_LOCKED_STATE_ID = 1;
    private const int LOCKED_STATE_ID = 2;
    private const int DELETED_CONTENT_ID = 99;

    /** @var array<non-empty-string, array{non-empty-string, non-empty-string, non-empty-string, 'RESTRICT'|'CASCADE'}> */
    private const array FOREIGN_KEYS = [
        'ibexa_object_state_group_fk' => ['ibexa_object_state', 'group_id', 'ibexa_object_state_group', 'RESTRICT'],
        'ibexa_object_state_group_language_group_fk' => ['ibexa_object_state_group_language', 'contentobject_state_group_id', 'ibexa_object_state_group', 'CASCADE'],
        'ibexa_object_state_language_state_fk' => ['ibexa_object_state_language', 'contentobject_state_id', 'ibexa_object_state', 'CASCADE'],
        'ibexa_object_state_link_state_fk' => ['ibexa_object_state_link', 'contentobject_state_id', 'ibexa_object_state', 'RESTRICT'],
        'ibexa_object_state_link_content_fk' => ['ibexa_object_state_link', 'contentobject_id', 'ibexa_content', 'CASCADE'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $schemaImporter = new LegacySchemaImporter($this->getDatabaseConnection(), DefaultTableOptions::AS_ARRAY);
        $schemaImporter->importSchema(__DIR__ . '/_fixtures/pre_object_state_foreign_keys_schema.yaml');

        $this->seedData();
    }

    public function testForeignKeysAreAdded(): void
    {
        $connection = $this->getDatabaseConnection();
        $connection->executeStatement('PRAGMA foreign_keys = ON');
        $this->runMigrations();

        $schemaManager = $connection->createSchemaManager();
        foreach (self::FOREIGN_KEYS as $foreignKey => [$table, $column, $referencedTable, $onDelete]) {
            self::assertTrue($schemaManager->introspectTableByUnquotedName($table)->hasForeignKey($foreignKey), $foreignKey);
            self::assertContains(
                [$column, $referencedTable, 'id', $onDelete, 'CASCADE'],
                array_map(
                    static fn (array $key): array => [$key['from'], $key['table'], $key['to'], $key['on_delete'], $key['on_update']],
                    $connection->fetchAllAssociative("PRAGMA foreign_key_list({$table})")
                ),
                $foreignKey
            );
        }
        self::assertSame([], $connection->fetchAllAssociative('PRAGMA foreign_key_check'));
        self::assertSame(2, (int)$connection->fetchOne('SELECT COUNT(*) FROM ibexa_object_state'));
        self::assertSame(2, (int)$connection->fetchOne('SELECT COUNT(*) FROM ibexa_object_state_language'));
        self::assertSame(1, (int)$connection->fetchOne('SELECT COUNT(*) FROM ibexa_object_state_group_language'));
        self::assertEquals(
            [['contentobject_id' => 1, 'contentobject_state_id' => self::NOT_LOCKED_STATE_ID]],
            $connection->fetchAllAssociative('SELECT contentobject_id, contentobject_state_id FROM ibexa_object_state_link')
        );

        foreach ($this->createMigrations() as $rerun) {
            $rerun->up(new Schema());
            self::assertSame([], $rerun->getSql(), $rerun->getDescription());
        }
    }

    public function testForeignKeysCascadeToTranslationsAndLinksButKeepStatesInUse(): void
    {
        $connection = $this->getDatabaseConnection();
        $connection->executeStatement('PRAGMA foreign_keys = ON');
        $this->runMigrations();

        $connection->delete('ibexa_object_state', ['id' => self::LOCKED_STATE_ID]);
        self::assertSame(1, (int)$connection->fetchOne('SELECT COUNT(*) FROM ibexa_object_state_language'));

        $connection->delete('ibexa_content', ['id' => 1]);
        self::assertSame(0, (int)$connection->fetchOne('SELECT COUNT(*) FROM ibexa_object_state_link'));

        $connection->insert('ibexa_content', ['id' => 2, 'initial_language_id' => self::LANGUAGE_ID]);
        $connection->insert('ibexa_object_state_link', ['contentobject_id' => 2, 'contentobject_state_id' => self::NOT_LOCKED_STATE_ID]);

        $this->expectException(ForeignKeyConstraintViolationException::class);
        $connection->delete('ibexa_object_state', ['id' => self::NOT_LOCKED_STATE_ID]);
    }

    /**
     * @param class-string<AbstractAddObjectStateForeignKeyMigration> $migrationClass
     * @param non-empty-string $table
     * @param array<non-empty-string, int|string> $orphanedRow
     */
    #[DataProvider('provideOrphanedRows')]
    public function testMigrationAbortsOnOrphanedRows(
        string $migrationClass,
        string $table,
        array $orphanedRow,
        string $expectedMessage
    ): void {
        $connection = $this->getDatabaseConnection();
        $connection->executeStatement('PRAGMA foreign_keys = OFF');
        $connection->insert($table, $orphanedRow);

        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage($expectedMessage);

        (new $migrationClass($connection, new NullLogger(), new MigrationRunContext()))->up(new Schema());
    }

    /**
     * @return iterable<string, array{class-string<AbstractAddObjectStateForeignKeyMigration>, non-empty-string, array<non-empty-string, int|string>, string}>
     */
    public static function provideOrphanedRows(): iterable
    {
        yield 'state of a missing group' => [
            AddObjectStateGroupForeignKeyMigration::class,
            'ibexa_object_state',
            ['id' => 3, 'group_id' => 7, 'default_language_id' => self::LANGUAGE_ID, 'identifier' => 'orphaned'],
            '"ibexa_object_state.group_id" holds 1 id(s) missing from "ibexa_object_state_group"',
        ];

        yield 'translation of a missing group' => [
            AddObjectStateGroupLanguageGroupForeignKeyMigration::class,
            'ibexa_object_state_group_language',
            ['contentobject_state_group_id' => 7, 'real_language_id' => self::LANGUAGE_ID, 'language_id' => self::LANGUAGE_ID, 'description' => '', 'name' => 'Orphaned'],
            '"ibexa_object_state_group_language.contentobject_state_group_id" holds 1 id(s) missing from "ibexa_object_state_group"',
        ];

        yield 'translation of a missing state' => [
            AddObjectStateLanguageStateForeignKeyMigration::class,
            'ibexa_object_state_language',
            ['contentobject_state_id' => 7, 'language_id' => self::LANGUAGE_ID, 'description' => '', 'name' => 'Orphaned'],
            '"ibexa_object_state_language.contentobject_state_id" holds 1 id(s) missing from "ibexa_object_state"',
        ];

        yield 'link to a missing state' => [
            AddObjectStateLinkStateForeignKeyMigration::class,
            'ibexa_object_state_link',
            ['contentobject_id' => 1, 'contentobject_state_id' => 7],
            '"ibexa_object_state_link.contentobject_state_id" holds 1 id(s) missing from "ibexa_object_state"',
        ];
    }

    public function testForeignKeysKeepTheRowsReferencingTheRebuiltTablesWhileSqliteEnforcesForeignKeys(): void
    {
        $connection = $this->getDatabaseConnection();
        $connection->executeStatement(
            'CREATE TABLE state_reference (state_id INTEGER NOT NULL, FOREIGN KEY (state_id) REFERENCES ibexa_object_state (id) ON DELETE CASCADE)'
        );
        $connection->executeStatement('INSERT INTO state_reference (state_id) SELECT id FROM ibexa_object_state');
        $connection->executeStatement('PRAGMA foreign_keys = ON');

        foreach ($this->createMigrations() as $migration) {
            self::assertFalse($migration->isTransactional(), $migration->getDescription());
        }
        $this->runMigrations();

        self::assertSame(2, (int)$connection->fetchOne('SELECT COUNT(*) FROM state_reference'));
        self::assertSame(1, (int)$connection->fetchOne('PRAGMA foreign_keys'));
        self::assertSame([], $connection->fetchAllAssociative('PRAGMA foreign_key_check'));
    }

    private function seedData(): void
    {
        $connection = $this->getDatabaseConnection();
        $connection->insert('ibexa_content_language', ['id' => self::LANGUAGE_ID, 'locale' => 'eng-GB', 'name' => 'English (United Kingdom)']);
        $connection->insert('ibexa_object_state_group', ['id' => self::GROUP_ID, 'default_language_id' => self::LANGUAGE_ID, 'identifier' => 'ibexa_lock']);
        $connection->insert('ibexa_object_state_group_language', ['contentobject_state_group_id' => self::GROUP_ID, 'real_language_id' => self::LANGUAGE_ID, 'language_id' => self::LANGUAGE_ID, 'description' => '', 'name' => 'Lock']);
        foreach ([self::NOT_LOCKED_STATE_ID => 'not_locked', self::LOCKED_STATE_ID => 'locked'] as $stateId => $identifier) {
            $connection->insert('ibexa_object_state', ['id' => $stateId, 'group_id' => self::GROUP_ID, 'default_language_id' => self::LANGUAGE_ID, 'identifier' => $identifier, 'priority' => $stateId - 1]);
            $connection->insert('ibexa_object_state_language', ['contentobject_state_id' => $stateId, 'language_id' => self::LANGUAGE_ID, 'description' => '', 'name' => $identifier]);
        }
        $connection->insert('ibexa_content', ['id' => 1, 'initial_language_id' => self::LANGUAGE_ID]);
        $connection->insert('ibexa_object_state_link', ['contentobject_id' => 1, 'contentobject_state_id' => self::NOT_LOCKED_STATE_ID]);
        $connection->insert('ibexa_object_state_link', ['contentobject_id' => self::DELETED_CONTENT_ID, 'contentobject_state_id' => self::NOT_LOCKED_STATE_ID]);
    }

    /**
     * @return list<AbstractAddObjectStateForeignKeyMigration>
     */
    private function createMigrations(): array
    {
        $connection = $this->getDatabaseConnection();

        return [
            new AddObjectStateGroupForeignKeyMigration($connection, new NullLogger(), new MigrationRunContext()),
            new AddObjectStateGroupLanguageGroupForeignKeyMigration($connection, new NullLogger(), new MigrationRunContext()),
            new AddObjectStateLanguageStateForeignKeyMigration($connection, new NullLogger(), new MigrationRunContext()),
            new AddObjectStateLinkStateForeignKeyMigration($connection, new NullLogger(), new MigrationRunContext()),
            new AddObjectStateLinkContentForeignKeyMigration($connection, new NullLogger(), new MigrationRunContext()),
        ];
    }

    private function runMigrations(): void
    {
        $connection = $this->getDatabaseConnection();
        foreach ($this->createMigrations() as $migration) {
            $migration->up(new Schema());
            foreach ($migration->getSql() as $query) {
                $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
            }
        }
    }
}

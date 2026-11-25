<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\RepositoryInstaller\Migration;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Exception\AbortMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\RenumberLanguageIdsMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\MigrationRunContext;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

#[CoversClass(RenumberLanguageIdsMigration::class)]
final class RenumberLanguageIdsMigrationTest extends TestCase
{
    private const string TABLE = 'ibexa_test_language_renumbering';

    private const string REFERENCED_TABLE = 'ibexa_test_language_renumbering_referenced';

    private const string CACHED_LANGUAGE_KEY = 'ibx-la-4';

    private TagAwareAdapterInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new TagAwareAdapter(new ArrayAdapter());

        $connection = $this->getDatabaseConnection();
        if ($connection->getDatabasePlatform() instanceof SQLitePlatform) {
            $connection->executeStatement('PRAGMA foreign_keys = ON');
        }

        foreach ([2 => 'eng-US', 4 => 'ger-DE', 8 => 'eng-GB'] as $id => $locale) {
            $connection->insert('ibexa_content_language', ['id' => $id, 'disabled' => 0, 'locale' => $locale, 'name' => $locale]);
        }
    }

    protected function tearDown(): void
    {
        $schemaManager = $this->getDatabaseConnection()->createSchemaManager();
        foreach ([self::TABLE, self::REFERENCED_TABLE] as $table) {
            if ($schemaManager->tablesExist([$table])) {
                $schemaManager->dropTable($table);
            }
        }

        parent::tearDown();
    }

    public function testRenumberingCascadesToEveryLanguageIdColumn(): void
    {
        $connection = $this->getDatabaseConnection();
        $this->createTable('language_id', ReferentialAction::CASCADE);
        foreach ([1 => 2, 2 => 4, 3 => 8] as $id => $languageId) {
            $connection->insert(self::TABLE, ['id' => $id, 'language_id' => $languageId]);
        }
        $connection->insert('ibexa_object_state_group', ['id' => 1, 'default_language_id' => 8, 'identifier' => 'group']);
        foreach ([4, 8] as $languageId) {
            $connection->insert(
                'ibexa_object_state_group_language',
                ['contentobject_state_group_id' => 1, 'real_language_id' => $languageId, 'language_id' => $languageId, 'description' => '', 'name' => "group {$languageId}"]
            );
        }
        $connection->insert('ibexa_search_object_word_link', ['id' => 1, 'language_id' => 8]);

        $this->executeMigration($this->createMigration());

        self::assertSame(
            ['eng-US' => 2, 'ger-DE' => 3, 'eng-GB' => 4],
            array_map('intval', $connection->fetchAllKeyValue('SELECT locale, id FROM ibexa_content_language ORDER BY id'))
        );
        self::assertSame(
            [1 => 2, 2 => 3, 3 => 4],
            array_map('intval', $connection->fetchAllKeyValue('SELECT id, language_id FROM ' . self::TABLE . ' ORDER BY id'))
        );
        self::assertSame(4, (int)$connection->fetchOne('SELECT default_language_id FROM ibexa_object_state_group WHERE id = 1'));
        self::assertSame(
            [['group 4', 3, 3], ['group 8', 4, 4]],
            array_map(
                static fn (array $row): array => [$row[0], (int)$row[1], (int)$row[2]],
                $connection->fetchAllNumeric('SELECT name, real_language_id, language_id FROM ibexa_object_state_group_language ORDER BY name')
            )
        );
        self::assertSame(4, (int)$connection->fetchOne('SELECT language_id FROM ibexa_search_object_word_link WHERE id = 1'));
    }

    /**
     * @param non-empty-string $column
     */
    #[DataProvider('provideColumnsLeftBehind')]
    public function testRenumberingAbortsOnAColumnLeftBehind(
        string $column,
        ?ReferentialAction $onUpdate
    ): void {
        $this->createTable($column, $onUpdate);

        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage(self::TABLE . '.' . $column);

        $this->createMigration()->up(new Schema());
    }

    /**
     * @return iterable<string, array{non-empty-string, ReferentialAction|null}>
     */
    public static function provideColumnsLeftBehind(): iterable
    {
        yield 'language id column without a foreign key' => ['language_id', null];
        yield 'language id column with a foreign key that restricts updates' => ['language_id', ReferentialAction::RESTRICT];
        yield 'differently named column with a foreign key that restricts updates' => ['locale_ref', ReferentialAction::RESTRICT];
    }

    public function testRenumberingLeavesALanguageIdColumnReferencingAnotherTableAlone(): void
    {
        $connection = $this->getDatabaseConnection();
        $connection->createSchemaManager()->createTable(
            Table::editor()
                ->setUnquotedName(self::REFERENCED_TABLE)
                ->setColumns(Column::editor()->setUnquotedName('id')->setTypeName(Types::BIGINT)->create())
                ->setPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create())
                ->create()
        );
        $this->createTable('language_id', ReferentialAction::RESTRICT, self::REFERENCED_TABLE);
        $connection->insert(self::REFERENCED_TABLE, ['id' => 8]);
        $connection->insert(self::TABLE, ['id' => 1, 'language_id' => 8]);

        $this->executeMigration($this->createMigration());

        self::assertSame(4, (int)$connection->fetchOne("SELECT id FROM ibexa_content_language WHERE locale = 'eng-GB'"));
        self::assertSame(8, (int)$connection->fetchOne('SELECT language_id FROM ' . self::TABLE . ' WHERE id = 1'));
    }

    public function testRenumberingClearsThePersistenceCache(): void
    {
        $this->cacheLanguage();

        $this->executeMigration($this->createMigration());

        self::assertFalse($this->cache->getItem(self::CACHED_LANGUAGE_KEY)->isHit());
    }

    public function testDryRunLeavesThePersistenceCacheAlone(): void
    {
        $this->cacheLanguage();
        $migrationRunContext = new MigrationRunContext();
        $migrationRunContext->setDryRun(true);

        $migration = $this->createMigration($migrationRunContext);
        $migration->up(new Schema());
        $migration->postUp(new Schema());

        self::assertNotSame([], $migration->getSql());
        self::assertTrue($this->cache->getItem(self::CACHED_LANGUAGE_KEY)->isHit());
    }

    public function testRenumberedLanguagesLeaveThePersistenceCacheAlone(): void
    {
        $this->executeMigration($this->createMigration());
        $this->cacheLanguage();

        $this->executeMigration($this->createMigration());

        self::assertTrue($this->cache->getItem(self::CACHED_LANGUAGE_KEY)->isHit());
    }

    /**
     * @param non-empty-string $column
     * @param non-empty-string $referencedTable
     */
    private function createTable(
        string $column,
        ?ReferentialAction $onUpdate,
        string $referencedTable = 'ibexa_content_language'
    ): void {
        $table = Table::editor()
            ->setUnquotedName(self::TABLE)
            ->setColumns(
                Column::editor()->setUnquotedName('id')->setTypeName(Types::INTEGER)->create(),
                Column::editor()->setUnquotedName($column)->setTypeName(Types::BIGINT)->create(),
            )
            ->setPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());

        if ($onUpdate !== null) {
            $table->addForeignKeyConstraint(
                ForeignKeyConstraint::editor()
                    ->setUnquotedName(self::TABLE . '_language_fk')
                    ->setUnquotedReferencingColumnNames($column)
                    ->setUnquotedReferencedTableName($referencedTable)
                    ->setUnquotedReferencedColumnNames('id')
                    ->setOnUpdateAction($onUpdate)
                    ->create()
            );
        }

        $this->getDatabaseConnection()->createSchemaManager()->createTable($table->create());
    }

    private function createMigration(?MigrationRunContext $migrationRunContext = null): RenumberLanguageIdsMigration
    {
        return new RenumberLanguageIdsMigration(
            $this->getDatabaseConnection(),
            new NullLogger(),
            $migrationRunContext ?? new MigrationRunContext(),
            $this->cache
        );
    }

    private function executeMigration(RenumberLanguageIdsMigration $migration): void
    {
        $migration->up(new Schema());

        $connection = $this->getDatabaseConnection();
        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }

        $migration->postUp(new Schema());
    }

    private function cacheLanguage(): void
    {
        $this->cache->save($this->cache->getItem(self::CACHED_LANGUAGE_KEY)->set('ger-DE'));
    }
}

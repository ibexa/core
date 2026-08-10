<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\AbortMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddContentAlwaysAvailableColumnsMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddLanguageTranslationTablesMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddSearchObjectWordLinkLanguageIdColumnsMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddUrlAliasAlwaysAvailableColumnMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\BackfillLanguageTranslationsMigration;
use Ibexa\Bundle\RepositoryInstaller\Migration\DropLanguageBitmaskColumnsMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\DoctrineSchema\Filter\SchemaAssetsFilterBypass;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use Ibexa\Tests\Core\Repository\LegacySchemaImporter;
use Psr\Log\NullLogger;

/**
 * End-to-end verification (Step 8 of the language bitmask migration) that the full migration
 * sequence correctly migrates an existing pre-6.0 install: seeds a database with the schema and
 * data shape a real pre-6.0 install would have (legacy "language_mask"/"lang_mask" columns,
 * power-of-two language ids), runs every migration in the sequence in order exactly as
 * `doctrine:migrations:migrate` would, and asserts the final relational data matches what the mask
 * data originally encoded.
 *
 * @covers \Ibexa\Bundle\RepositoryInstaller\Migration\AddContentAlwaysAvailableColumnsMigration
 * @covers \Ibexa\Bundle\RepositoryInstaller\Migration\AddLanguageTranslationTablesMigration
 * @covers \Ibexa\Bundle\RepositoryInstaller\Migration\BackfillLanguageTranslationsMigration
 * @covers \Ibexa\Bundle\RepositoryInstaller\Migration\AddSearchObjectWordLinkLanguageIdColumnsMigration
 * @covers \Ibexa\Bundle\RepositoryInstaller\Migration\AddUrlAliasAlwaysAvailableColumnMigration
 * @covers \Ibexa\Bundle\RepositoryInstaller\Migration\DropLanguageBitmaskColumnsMigration
 */
final class LanguageBitmaskUpgradeSequenceTest extends TestCase
{
    private const ENG_US = 2;
    private const GER_DE = 4;
    private const ENG_GB = 8;

    protected function setUp(): void
    {
        parent::setUp();

        $schemaImporter = new LegacySchemaImporter($this->getDatabaseConnection(), new SchemaAssetsFilterBypass());
        $schemaImporter->importSchema(
            __DIR__ . '/_fixtures/pre_language_bitmask_migration_schema.yaml'
        );
    }

    public function testFullSequenceMigratesExistingDataCorrectly(): void
    {
        $connection = $this->getDatabaseConnection();
        $this->seedPreMigrationData($connection);

        $this->runMigration(new AddContentAlwaysAvailableColumnsMigration($connection, new NullLogger()));
        $this->runMigration(new AddLanguageTranslationTablesMigration($connection, new NullLogger()));
        $this->runMigration(new BackfillLanguageTranslationsMigration($connection, new NullLogger()));
        $this->runMigration(new AddSearchObjectWordLinkLanguageIdColumnsMigration($connection, new NullLogger()));
        $this->runMigration(new AddUrlAliasAlwaysAvailableColumnMigration($connection, new NullLogger()));
        $this->runMigration(new DropLanguageBitmaskColumnsMigration($connection, new NullLogger()));

        $schemaManager = $connection->createSchemaManager();
        self::assertFalse($schemaManager->introspectTable('ibexa_content')->hasColumn('language_mask'));
        self::assertFalse($schemaManager->introspectTable('ibexa_content_version')->hasColumn('language_mask'));
        self::assertFalse($schemaManager->introspectTable('ibexa_url_alias_ml')->hasColumn('lang_mask'));
        self::assertFalse($schemaManager->introspectTable('ibexa_search_object_word_link')->hasColumn('language_mask'));
        self::assertFalse($schemaManager->introspectTable('ibexa_object_state')->hasColumn('language_mask'));
        self::assertFalse($schemaManager->introspectTable('ibexa_object_state_group')->hasColumn('language_mask'));
        self::assertFalse($schemaManager->introspectTable('ibexa_content_type')->hasColumn('language_mask'));

        // Content 1: eng-US + eng-GB, not always available (mask 10 = 2|8)
        self::assertEquals(0, $connection->fetchOne('SELECT always_available FROM ibexa_content WHERE id = 1'));
        self::assertEqualsCanonicalizing(
            [self::ENG_US, self::ENG_GB],
            $this->fetchLanguageIds($connection, 'ibexa_content_translation', 'content_id', 1)
        );
        self::assertEqualsCanonicalizing(
            [self::ENG_US, self::ENG_GB],
            $this->fetchLanguageIds($connection, 'ibexa_content_version_translation', 'content_version_id', 1)
        );

        // Content 2: ger-DE only, always available (mask 5 = 4|1)
        self::assertEquals(1, $connection->fetchOne('SELECT always_available FROM ibexa_content WHERE id = 2'));
        self::assertEqualsCanonicalizing(
            [self::GER_DE],
            $this->fetchLanguageIds($connection, 'ibexa_content_translation', 'content_id', 2)
        );
        self::assertEqualsCanonicalizing(
            [self::GER_DE],
            $this->fetchLanguageIds($connection, 'ibexa_content_version_translation', 'content_version_id', 2)
        );

        // URL alias: eng-US + eng-GB, always available (mask 11 = 2|8|1)
        self::assertEquals(
            1,
            $connection->fetchOne(
                "SELECT is_always_available FROM ibexa_url_alias_ml WHERE parent = 0 AND text_md5 = 'hash1'"
            )
        );
        self::assertEqualsCanonicalizing(
            [self::ENG_US, self::ENG_GB],
            array_map(
                'intval',
                $connection->fetchFirstColumn(
                    "SELECT language_id FROM ibexa_url_alias_ml_translation WHERE parent = 0 AND text_md5 = 'hash1'"
                )
            )
        );

        // Search word link: eng-GB, always available (mask 9 = 8|1)
        $wordLinkRow = $connection->fetchAssociative(
            'SELECT language_id, is_main_and_always_available FROM ibexa_search_object_word_link WHERE id = 1'
        );
        self::assertEquals(self::ENG_GB, $wordLinkRow['language_id']);
        self::assertEquals(1, $wordLinkRow['is_main_and_always_available']);
    }

    public function testDropMigrationAbortsIfBackfillWasSkipped(): void
    {
        $connection = $this->getDatabaseConnection();
        $this->seedPreMigrationData($connection);

        $this->runMigration(new AddContentAlwaysAvailableColumnsMigration($connection, new NullLogger()));
        $this->runMigration(new AddLanguageTranslationTablesMigration($connection, new NullLogger()));
        // Deliberately skip BackfillLanguageTranslationsMigration, simulating an interrupted or
        // manually-mismanaged upgrade.
        $this->runMigration(new AddSearchObjectWordLinkLanguageIdColumnsMigration($connection, new NullLogger()));
        $this->runMigration(new AddUrlAliasAlwaysAvailableColumnMigration($connection, new NullLogger()));

        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessageMatches('/backfill-translations/');

        $this->runMigration(new DropLanguageBitmaskColumnsMigration($connection, new NullLogger()));
    }

    private function seedPreMigrationData(Connection $connection): void
    {
        foreach (
            [
                [self::ENG_US, 'eng-US', 'English (American)'],
                [self::GER_DE, 'ger-DE', 'German'],
                [self::ENG_GB, 'eng-GB', 'English (United Kingdom)'],
            ] as [$id, $locale, $name]
        ) {
            $connection->insert(
                'ibexa_content_language',
                ['id' => $id, 'locale' => $locale, 'name' => $name, 'disabled' => 0]
            );
        }

        // Content 1: eng-US(2) + eng-GB(8), not always available -> mask 10
        $connection->insert('ibexa_content', [
            'id' => 1,
            'content_type_id' => 1,
            'current_version' => 1,
            'initial_language_id' => self::ENG_US,
            'language_mask' => self::ENG_US | self::ENG_GB,
            'name' => 'Foo',
            'owner_id' => 14,
            'remote_id' => 'foo',
        ]);
        $connection->insert('ibexa_content_version', [
            'id' => 1,
            'contentobject_id' => 1,
            'version' => 1,
            'initial_language_id' => self::ENG_US,
            'language_mask' => self::ENG_US | self::ENG_GB,
        ]);

        // Content 2: ger-DE(4), always available -> mask 5
        $connection->insert('ibexa_content', [
            'id' => 2,
            'content_type_id' => 1,
            'current_version' => 1,
            'initial_language_id' => self::GER_DE,
            'language_mask' => self::GER_DE | 1,
            'name' => 'Bar',
            'owner_id' => 14,
            'remote_id' => 'bar',
        ]);
        $connection->insert('ibexa_content_version', [
            'id' => 2,
            'contentobject_id' => 2,
            'version' => 1,
            'initial_language_id' => self::GER_DE,
            'language_mask' => self::GER_DE | 1,
        ]);

        // URL alias: eng-US + eng-GB, always available -> mask 11
        $connection->insert(
            'ibexa_url_alias_ml',
            [
                'parent' => 0,
                'text_md5' => 'hash1',
                'id' => 1,
                'text' => 'foo',
                'action' => 'eznode:1',
                'action_type' => 'eznode',
                'lang_mask' => self::ENG_US | self::ENG_GB | 1,
            ],
            ['lang_mask' => ParameterType::INTEGER]
        );

        // Search word link: eng-GB, always available -> mask 9
        $connection->insert('ibexa_search_object_word_link', [
            'id' => 1,
            'contentobject_id' => 1,
            'word_id' => 1,
            'identifier' => 'foo',
            'language_mask' => self::ENG_GB | 1,
        ]);
    }

    /**
     * @return int[]
     */
    private function fetchLanguageIds(Connection $connection, string $table, string $idColumn, int $id): array
    {
        return array_map(
            'intval',
            $connection->fetchFirstColumn(
                "SELECT language_id FROM {$table} WHERE {$idColumn} = :id",
                ['id' => $id],
                ['id' => ParameterType::INTEGER]
            )
        );
    }

    /**
     * Runs a migration exactly as {@see \Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner}
     * does: call up() to populate its queued SQL, then execute each queued statement in order.
     */
    private function runMigration(AbstractSqlMigration $migration): void
    {
        $migration->up(new Schema());

        $connection = $this->getDatabaseConnection();
        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}

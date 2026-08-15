<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Command;

use Doctrine\DBAL\ParameterType;
use Ibexa\Bundle\Core\Command\BackfillLanguageTranslationsCommand;
use Ibexa\Bundle\Core\Command\VerifyLanguageTranslationsCommand;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Ibexa\Bundle\Core\Command\BackfillLanguageTranslationsCommand
 * @covers \Ibexa\Bundle\Core\Command\VerifyLanguageTranslationsCommand
 */
final class BackfillLanguageTranslationsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(
            __DIR__ . '/../../../lib/Persistence/Legacy/Content/_fixtures/languages.php'
        );

        $connection = $this->getDatabaseConnection();

        // These commands hardcode "ibexa_content_language" (the name it still has at the point in
        // the real migration sequence where they're used, before NarrowLanguageIdColumnTypesMigration
        // renames it to "ibexa_language") - rename it back after the fixture above (which inserts via
        // the Gateway::CONTENT_LANGUAGE_TABLE constant, i.e. under the current name) has populated it.
        $connection->executeStatement('ALTER TABLE ibexa_language RENAME TO ibexa_content_language');

        // These commands exist specifically for installs upgrading from before the language
        // bitmask columns were dropped - simulate that pre-drop schema state here, since the
        // current schema.yaml (and therefore this test's own bootstrapped schema) no longer has
        // them.
        $connection->executeStatement('ALTER TABLE ibexa_content ADD COLUMN language_mask INTEGER DEFAULT 0 NOT NULL');
        $connection->executeStatement('ALTER TABLE ibexa_content_version ADD COLUMN language_mask INTEGER DEFAULT 0 NOT NULL');
        $connection->executeStatement('ALTER TABLE ibexa_url_alias_ml ADD COLUMN lang_mask INTEGER DEFAULT 0 NOT NULL');

        // content id 1: eng-US only, not always available -> mask 2
        // content id 2: eng-US + eng-GB, always available -> mask 7
        $connection->insert('ibexa_content', [
            'id' => 1,
            'content_type_id' => 1,
            'current_version' => 1,
            'initial_language_id' => 2,
            'language_mask' => 2,
            'always_available' => 0,
            'name' => 'Foo',
            'owner_id' => 14,
            'remote_id' => 'foo',
        ]);
        $connection->insert('ibexa_content', [
            'id' => 2,
            'content_type_id' => 1,
            'current_version' => 1,
            'initial_language_id' => 2,
            'language_mask' => 7,
            'always_available' => 1,
            'name' => 'Bar',
            'owner_id' => 14,
            'remote_id' => 'bar',
        ]);

        $connection->insert('ibexa_content_version', [
            'id' => 1,
            'contentobject_id' => 1,
            'version' => 1,
            'initial_language_id' => 2,
            'language_mask' => 2,
        ]);
        $connection->insert('ibexa_content_version', [
            'id' => 2,
            'contentobject_id' => 2,
            'version' => 1,
            'initial_language_id' => 2,
            'language_mask' => 7,
        ]);

        $connection->insert('ibexa_url_alias_ml', [
            'parent' => 0,
            'text_md5' => md5('foo'),
            'id' => 1,
            'text' => 'foo',
            'action' => 'eznode:1',
            'action_type' => 'eznode',
            'lang_mask' => 7,
        ], ['lang_mask' => ParameterType::INTEGER]);
    }

    protected function tearDown(): void
    {
        // The underlying SQLite connection is reused across tests (see
        // DatabaseConnectionFactory's static connection pool) - restore the name the next test's
        // schema.yaml-based setUp() expects to find.
        $this->getDatabaseConnection()->executeStatement('ALTER TABLE ibexa_content_language RENAME TO ibexa_language');

        parent::tearDown();
    }

    public function testBackfillPopulatesTranslationTablesFromMasks(): void
    {
        $exitCode = (new CommandTester(new BackfillLanguageTranslationsCommand($this->getDatabaseConnection())))
            ->execute(['--table' => 'all'])
        ;

        self::assertSame(0, $exitCode);

        self::assertEqualsCanonicalizing(
            [[1, 2], [2, 2], [2, 4]],
            $this->fetchPairs('ibexa_content_translation', 'content_id')
        );
        self::assertEqualsCanonicalizing(
            [[1, 2], [2, 2], [2, 4]],
            $this->fetchPairs('ibexa_content_version_translation', 'content_version_id')
        );
        self::assertEqualsCanonicalizing(
            [[2], [4]],
            array_map(
                static fn (array $row): array => [(int)$row['language_id']],
                $this->getDatabaseConnection()->fetchAllAssociative(
                    'SELECT language_id FROM ibexa_url_alias_ml_translation'
                )
            )
        );
    }

    public function testBackfillIsIdempotent(): void
    {
        $command = new BackfillLanguageTranslationsCommand($this->getDatabaseConnection());
        (new CommandTester($command))->execute(['--table' => 'content']);
        (new CommandTester($command))->execute(['--table' => 'content']);

        self::assertEqualsCanonicalizing(
            [[1, 2], [2, 2], [2, 4]],
            $this->fetchPairs('ibexa_content_translation', 'content_id')
        );
    }

    public function testBackfillDryRunDoesNotWrite(): void
    {
        $command = new BackfillLanguageTranslationsCommand($this->getDatabaseConnection());
        (new CommandTester($command))->execute(['--table' => 'content', '--dry-run' => true]);

        self::assertSame([], $this->fetchPairs('ibexa_content_translation', 'content_id'));
    }

    public function testBackfillDryRunReportsNothingRemainingAfterRealBackfill(): void
    {
        $connection = $this->getDatabaseConnection();
        (new CommandTester(new BackfillLanguageTranslationsCommand($connection)))
            ->execute(['--table' => 'content']);

        // A dry-run preview after a real backfill must report that nothing is left to insert - not
        // recount every mask-derived pair as if none of them already existed.
        $tester = new CommandTester(new BackfillLanguageTranslationsCommand($connection));
        $tester->execute(['--table' => 'content', '--dry-run' => true]);

        self::assertStringContainsString('0 row(s) would be inserted', $tester->getDisplay());
        // The real rows from the earlier backfill must not have been touched by the dry-run.
        self::assertEqualsCanonicalizing(
            [[1, 2], [2, 2], [2, 4]],
            $this->fetchPairs('ibexa_content_translation', 'content_id')
        );
    }

    public function testVerifyReportsCleanAfterBackfill(): void
    {
        (new CommandTester(new BackfillLanguageTranslationsCommand($this->getDatabaseConnection())))
            ->execute(['--table' => 'all']);

        $tester = new CommandTester(new VerifyLanguageTranslationsCommand($this->getDatabaseConnection()));
        $exitCode = $tester->execute(['--table' => 'all']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('in sync', $tester->getDisplay());
    }

    public function testVerifyDetectsMissingAndOrphanedRowsAndFixesThem(): void
    {
        $connection = $this->getDatabaseConnection();

        // Missing: content 1's translation was never backfilled.
        $connection->insert('ibexa_content_translation', ['content_id' => 2, 'language_id' => 2]);
        $connection->insert('ibexa_content_translation', ['content_id' => 2, 'language_id' => 4]);
        // Orphaned: language 4 is not part of content 1's mask (2).
        $connection->insert('ibexa_content_translation', ['content_id' => 1, 'language_id' => 4]);

        $tester = new CommandTester(new VerifyLanguageTranslationsCommand($connection));
        $exitCode = $tester->execute(['--table' => 'content']);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('1 missing, 1 orphaned', $tester->getDisplay());

        $fixTester = new CommandTester(new VerifyLanguageTranslationsCommand($connection));
        $fixExitCode = $fixTester->execute(['--table' => 'content', '--fix' => true]);

        self::assertSame(0, $fixExitCode);
        self::assertEqualsCanonicalizing(
            [[1, 2], [2, 2], [2, 4]],
            $this->fetchPairs('ibexa_content_translation', 'content_id')
        );
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    private function fetchPairs(string $table, string $idColumn): array
    {
        $rows = $this->getDatabaseConnection()->fetchAllAssociative(
            "SELECT {$idColumn}, language_id FROM {$table}"
        );

        return array_map(
            static fn (array $row): array => [(int)$row[$idColumn], (int)$row['language_id']],
            $rows
        );
    }
}

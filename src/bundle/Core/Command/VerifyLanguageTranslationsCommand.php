<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException as APIInvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verifies "always_available" and the language translation tables against the legacy language bitmask columns.
 */
#[AsCommand(
    name: 'ibexa:languages:verify-translations',
    description: 'Verifies "always_available" and the language translation tables against the legacy language bitmask columns',
    help: <<<'EOT'
        The command <info>%command.name%</info> compares "ibexa_content_translation",
        "ibexa_content_version_translation" and "ibexa_url_alias_ml_translation" against the
        "language_mask"/"lang_mask" columns they were backfilled from, reporting:
          - <comment>missing</comment>: a language bit set in the mask with no corresponding translation row
          - <comment>orphaned</comment>: a translation row whose bit is no longer set in the mask
          - <comment>wrong always-available flag</comment>: an "always_available" value that doesn't match bit 0 of the mask

        Exits non-zero if any drift is found and <info>--fix</info> was not passed. Run this after
        <info>ibexa:languages:backfill-translations</info>, and again before relying on the translation
        tables for anything - a clean report here is the precondition for every later migration step.
        EOT,
)]
final class VerifyLanguageTranslationsCommand
{
    private const string LANGUAGE_TABLE = 'ibexa_content_language';

    private const int BATCH_SIZE = 5000;

    private const string TABLE_CONTENT = 'content';
    private const string TABLE_CONTENT_VERSION = 'content_version';
    private const string TABLE_URL_ALIAS = 'url_alias';
    private const string TABLE_ALL = 'all';

    /**
     * Table => [translation table, column its orphaned rows are deleted in batches by].
     *
     * @var array<string, array{string, string}>
     */
    private const array TRANSLATION_TABLES = [
        self::TABLE_CONTENT => ['ibexa_content_translation', 'content_id'],
        self::TABLE_CONTENT_VERSION => ['ibexa_content_version_translation', 'content_version_id'],
        self::TABLE_URL_ALIAS => ['ibexa_url_alias_ml_translation', 'parent'],
    ];

    private const array VALID_TABLES = [
        self::TABLE_CONTENT,
        self::TABLE_CONTENT_VERSION,
        self::TABLE_URL_ALIAS,
    ];

    /**
     * Table => [source table carrying the legacy mask, mask column, always-available column].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const array SOURCE_TABLES = [
        self::TABLE_CONTENT => ['ibexa_content', 'language_mask', 'always_available'],
        self::TABLE_CONTENT_VERSION => ['ibexa_content_version', 'language_mask', 'always_available'],
        self::TABLE_URL_ALIAS => ['ibexa_url_alias_ml', 'lang_mask', 'always_available'],
    ];

    /**
     * @return array{missing: string, orphaned: string, fixMissing: string, fixOrphaned: string}
     */
    private function buildQueries(
        string $table,
        string $languageTable
    ): array {
        return match ($table) {
            self::TABLE_CONTENT => [
                'missing' => <<<SQL
                    SELECT COUNT(*) FROM ibexa_content c
                    JOIN {$languageTable} l ON (c.language_mask & l.id) = l.id
                    WHERE NOT EXISTS (
                        SELECT 1 FROM ibexa_content_translation ct
                        WHERE ct.content_id = c.id AND ct.language_id = l.id
                    )
                    SQL,
                'orphaned' => <<<'SQL'
                    SELECT COUNT(*) FROM ibexa_content_translation ct
                    WHERE NOT EXISTS (
                        SELECT 1 FROM ibexa_content c
                        WHERE c.id = ct.content_id AND (c.language_mask & ct.language_id) = ct.language_id
                    )
                    SQL,
                'fixMissing' => <<<SQL
                    INSERT INTO ibexa_content_translation (content_id, language_id)
                    SELECT c.id, l.id FROM ibexa_content c
                    JOIN {$languageTable} l ON (c.language_mask & l.id) = l.id
                    WHERE c.id BETWEEN :from AND :to
                    AND NOT EXISTS (
                        SELECT 1 FROM ibexa_content_translation ct
                        WHERE ct.content_id = c.id AND ct.language_id = l.id
                    )
                    SQL,
                // No alias on the DELETE target: SQLite's DELETE FROM does not accept one.
                'fixOrphaned' => <<<'SQL'
                    DELETE FROM ibexa_content_translation
                    WHERE ibexa_content_translation.content_id BETWEEN :from AND :to
                    AND NOT EXISTS (
                        SELECT 1 FROM ibexa_content c
                        WHERE c.id = ibexa_content_translation.content_id
                            AND (c.language_mask & ibexa_content_translation.language_id) = ibexa_content_translation.language_id
                    )
                    SQL,
            ],
            self::TABLE_CONTENT_VERSION => [
                'missing' => <<<SQL
                    SELECT COUNT(*) FROM ibexa_content_version v
                    JOIN {$languageTable} l ON (v.language_mask & l.id) = l.id
                    WHERE NOT EXISTS (
                        SELECT 1 FROM ibexa_content_version_translation vt
                        WHERE vt.content_version_id = v.id AND vt.language_id = l.id
                    )
                    SQL,
                'orphaned' => <<<'SQL'
                    SELECT COUNT(*) FROM ibexa_content_version_translation vt
                    WHERE NOT EXISTS (
                        SELECT 1 FROM ibexa_content_version v
                        WHERE v.id = vt.content_version_id AND (v.language_mask & vt.language_id) = vt.language_id
                    )
                    SQL,
                'fixMissing' => <<<SQL
                    INSERT INTO ibexa_content_version_translation (content_version_id, language_id)
                    SELECT v.id, l.id FROM ibexa_content_version v
                    JOIN {$languageTable} l ON (v.language_mask & l.id) = l.id
                    WHERE v.id BETWEEN :from AND :to
                    AND NOT EXISTS (
                        SELECT 1 FROM ibexa_content_version_translation vt
                        WHERE vt.content_version_id = v.id AND vt.language_id = l.id
                    )
                    SQL,
                // No alias on the DELETE target: SQLite's DELETE FROM does not accept one.
                'fixOrphaned' => <<<'SQL'
                    DELETE FROM ibexa_content_version_translation
                    WHERE ibexa_content_version_translation.content_version_id BETWEEN :from AND :to
                    AND NOT EXISTS (
                        SELECT 1 FROM ibexa_content_version v
                        WHERE v.id = ibexa_content_version_translation.content_version_id
                            AND (v.language_mask & ibexa_content_version_translation.language_id) = ibexa_content_version_translation.language_id
                    )
                    SQL,
            ],
            self::TABLE_URL_ALIAS => [
                'missing' => <<<SQL
                    SELECT COUNT(*) FROM ibexa_url_alias_ml u
                    JOIN {$languageTable} l ON (u.lang_mask & l.id) = l.id
                    WHERE NOT EXISTS (
                        SELECT 1 FROM ibexa_url_alias_ml_translation ut
                        WHERE ut.parent = u.parent AND ut.text_md5 = u.text_md5 AND ut.language_id = l.id
                    )
                    SQL,
                'orphaned' => <<<'SQL'
                    SELECT COUNT(*) FROM ibexa_url_alias_ml_translation ut
                    WHERE NOT EXISTS (
                        SELECT 1 FROM ibexa_url_alias_ml u
                        WHERE u.parent = ut.parent AND u.text_md5 = ut.text_md5
                            AND (u.lang_mask & ut.language_id) = ut.language_id
                    )
                    SQL,
                'fixMissing' => <<<SQL
                    INSERT INTO ibexa_url_alias_ml_translation (parent, text_md5, language_id)
                    SELECT u.parent, u.text_md5, l.id FROM ibexa_url_alias_ml u
                    JOIN {$languageTable} l ON (u.lang_mask & l.id) = l.id
                    WHERE u.id BETWEEN :from AND :to
                    AND NOT EXISTS (
                        SELECT 1 FROM ibexa_url_alias_ml_translation ut
                        WHERE ut.parent = u.parent AND ut.text_md5 = u.text_md5 AND ut.language_id = l.id
                    )
                    SQL,
                // No alias on the DELETE target: SQLite's DELETE FROM does not accept one.
                'fixOrphaned' => <<<'SQL'
                    DELETE FROM ibexa_url_alias_ml_translation
                    WHERE ibexa_url_alias_ml_translation.parent BETWEEN :from AND :to
                    AND NOT EXISTS (
                        SELECT 1 FROM ibexa_url_alias_ml u
                        WHERE u.parent = ibexa_url_alias_ml_translation.parent
                            AND u.text_md5 = ibexa_url_alias_ml_translation.text_md5
                            AND (u.lang_mask & ibexa_url_alias_ml_translation.language_id) = ibexa_url_alias_ml_translation.language_id
                    )
                    SQL,
            ],
            default => throw new InvalidArgumentException('table', "unknown table \"{$table}\"."),
        };
    }

    public function __construct(private readonly Connection $connection) {}

    /**
     * @throws APIInvalidArgumentException
     * @throws DBALException
     */
    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Which table to verify: one of "content", "content_version", "url_alias", or "all" for all of them.', shortcut: 't')]
        string $table = self::TABLE_ALL,
        #[Option(description: 'Insert missing rows and delete orphaned rows to bring the translation table back in sync.')]
        bool $fix = false,
    ): int {
        $tables = $table === self::TABLE_ALL ? self::VALID_TABLES : [$table];
        foreach ($tables as $singleTable) {
            if (!isset(self::SOURCE_TABLES[$singleTable])) {
                throw new InvalidArgumentException(
                    'table',
                    sprintf(
                        '"%s" is not one of "%s" or "%s".',
                        $singleTable,
                        implode('", "', self::VALID_TABLES),
                        self::TABLE_ALL
                    )
                );
            }
        }

        $clean = true;
        foreach ($tables as $singleTable) {
            $clean = $this->verifyTable($singleTable, $fix, $output) && $clean;
        }

        if ($clean) {
            $output->writeln('<info>All checked translation tables are in sync with their language masks.</info>');

            return Command::SUCCESS;
        }

        if ($fix) {
            $output->writeln('<info>Drift found and fixed.</info>');

            return Command::SUCCESS;
        }

        $output->writeln('<error>Drift found. Re-run with --fix to correct it.</error>');

        return Command::FAILURE;
    }

    private function verifyTable(
        string $table,
        bool $fix,
        OutputInterface $output
    ): bool {
        [$sourceTable, $maskColumn, $alwaysAvailableColumn] = self::SOURCE_TABLES[$table];

        if (!$this->connection->createSchemaManager()->introspectTableByUnquotedName($sourceTable)->hasColumn($maskColumn)) {
            $output->writeln("<info>{$table}: {$sourceTable} has no \"{$maskColumn}\" column - already migrated, nothing to verify.</info>");

            return true;
        }

        $languageTable = self::LANGUAGE_TABLE;
        $queries = $this->buildQueries($table, $languageTable);

        $alwaysAvailableMismatch = "{$alwaysAvailableColumn} <> (({$maskColumn} & 1) = 1)";

        $missing = (int)$this->connection->fetchOne($queries['missing']);
        $orphaned = (int)$this->connection->fetchOne($queries['orphaned']);
        $mismatched = (int)$this->connection->fetchOne("SELECT COUNT(*) FROM {$sourceTable} WHERE {$alwaysAvailableMismatch}");

        if ($missing === 0 && $orphaned === 0 && $mismatched === 0) {
            $output->writeln("<info>{$table}: in sync.</info>");

            return true;
        }

        $output->writeln(sprintf(
            '<comment>%s: %d missing, %d orphaned row(s), %d wrong always-available flag(s).</comment>',
            $table,
            $missing,
            $orphaned,
            $mismatched
        ));

        if (!$fix) {
            return false;
        }

        if ($missing > 0) {
            $inserted = $this->executeInBatches($queries['fixMissing'], $sourceTable, 'id');
            $output->writeln("  inserted {$inserted} missing row(s).");
        }

        if ($orphaned > 0) {
            [$translationTable, $translationColumn] = self::TRANSLATION_TABLES[$table];
            $deleted = $this->executeInBatches($queries['fixOrphaned'], $translationTable, $translationColumn);
            $output->writeln("  deleted {$deleted} orphaned row(s).");
        }

        if ($mismatched > 0) {
            $updated = $this->executeInBatches(
                "UPDATE {$sourceTable} SET {$alwaysAvailableColumn} = (({$maskColumn} & 1) = 1) WHERE id BETWEEN :from AND :to AND {$alwaysAvailableMismatch}",
                $sourceTable,
                'id'
            );
            $output->writeln("  fixed {$updated} always-available flag(s).");
        }

        return true;
    }

    /**
     * Runs a statement once per batch of ids of the given column, binding the batch to ":from" and ":to".
     *
     * @throws DBALException
     */
    private function executeInBatches(
        string $sql,
        string $table,
        string $column
    ): int {
        $range = $this->connection->fetchAssociative("SELECT MIN({$column}) AS min_id, MAX({$column}) AS max_id FROM {$table}");
        if ($range === false || $range['min_id'] === null) {
            return 0;
        }

        $affected = 0;
        $maxId = (int)$range['max_id'];
        for ($from = (int)$range['min_id']; $from <= $maxId; $from += self::BATCH_SIZE) {
            $affected += (int)$this->connection->executeStatement(
                $sql,
                ['from' => $from, 'to' => min($from + self::BATCH_SIZE - 1, $maxId)],
                ['from' => ParameterType::INTEGER, 'to' => ParameterType::INTEGER]
            );
        }

        return $affected;
    }
}

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command;

use Doctrine\DBAL\Connection;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verifies that "ibexa_content_translation", "ibexa_content_version_translation" and
 * "ibexa_url_alias_ml_translation" agree with the legacy "language_mask"/"lang_mask" bitmask
 * columns they were backfilled from (step 2 of the language bitmask migration).
 *
 * A standard upgrade no longer needs to run this manually - {@see \Ibexa\Bundle\RepositoryInstaller\Migration\BackfillLanguageTranslationsMigration}
 * backfills as part of the regular migration sequence, and {@see \Ibexa\Bundle\RepositoryInstaller\Migration\DropLanguageBitmaskColumnsMigration}
 * refuses to drop the mask columns if any row wasn't backfilled. This command remains available
 * for manual verification/repair (via `--fix`) outside of a migration run.
 *
 * Checks both directions with plain NOT EXISTS correlated subqueries rather than EXCEPT/MINUS, so
 * the same SQL runs unchanged on MySQL, PostgreSQL and SQLite:
 *  - missing: a bit set in the mask with no corresponding translation row
 *  - orphaned: a translation row whose bit is no longer set in the mask (or the mask/language
 *    row it pointed to is gone)
 */
#[AsCommand(
    name: 'ibexa:languages:verify-translations',
    description: 'Verifies the language translation tables against the legacy language bitmask columns'
)]
final class VerifyLanguageTranslationsCommand extends Command
{
    private const TABLE_CONTENT = 'content';
    private const TABLE_CONTENT_VERSION = 'content_version';
    private const TABLE_URL_ALIAS = 'url_alias';
    private const TABLE_ALL = 'all';

    private const VALID_TABLES = [
        self::TABLE_CONTENT,
        self::TABLE_CONTENT_VERSION,
        self::TABLE_URL_ALIAS,
    ];

    /**
     * @var array<string, array{missing: string, orphaned: string, fixMissing: string, fixOrphaned: string}>
     */
    private const QUERIES = [
        self::TABLE_CONTENT => [
            'missing' => 'SELECT COUNT(*) FROM ibexa_content c
                JOIN ibexa_content_language l ON (c.language_mask & l.id) = l.id
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_content_translation ct
                    WHERE ct.content_id = c.id AND ct.language_id = l.id
                )',
            'orphaned' => 'SELECT COUNT(*) FROM ibexa_content_translation ct
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_content c
                    WHERE c.id = ct.content_id AND (c.language_mask & ct.language_id) = ct.language_id
                )',
            'fixMissing' => 'INSERT INTO ibexa_content_translation (content_id, language_id)
                SELECT c.id, l.id FROM ibexa_content c
                JOIN ibexa_content_language l ON (c.language_mask & l.id) = l.id
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_content_translation ct
                    WHERE ct.content_id = c.id AND ct.language_id = l.id
                )',
            // No alias on the DELETE target: SQLite's DELETE FROM does not accept one.
            'fixOrphaned' => 'DELETE FROM ibexa_content_translation
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_content c
                    WHERE c.id = ibexa_content_translation.content_id
                        AND (c.language_mask & ibexa_content_translation.language_id) = ibexa_content_translation.language_id
                )',
        ],
        self::TABLE_CONTENT_VERSION => [
            'missing' => 'SELECT COUNT(*) FROM ibexa_content_version v
                JOIN ibexa_content_language l ON (v.language_mask & l.id) = l.id
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_content_version_translation vt
                    WHERE vt.content_version_id = v.id AND vt.language_id = l.id
                )',
            'orphaned' => 'SELECT COUNT(*) FROM ibexa_content_version_translation vt
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_content_version v
                    WHERE v.id = vt.content_version_id AND (v.language_mask & vt.language_id) = vt.language_id
                )',
            'fixMissing' => 'INSERT INTO ibexa_content_version_translation (content_version_id, language_id)
                SELECT v.id, l.id FROM ibexa_content_version v
                JOIN ibexa_content_language l ON (v.language_mask & l.id) = l.id
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_content_version_translation vt
                    WHERE vt.content_version_id = v.id AND vt.language_id = l.id
                )',
            // No alias on the DELETE target: SQLite's DELETE FROM does not accept one.
            'fixOrphaned' => 'DELETE FROM ibexa_content_version_translation
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_content_version v
                    WHERE v.id = ibexa_content_version_translation.content_version_id
                        AND (v.language_mask & ibexa_content_version_translation.language_id) = ibexa_content_version_translation.language_id
                )',
        ],
        self::TABLE_URL_ALIAS => [
            'missing' => 'SELECT COUNT(*) FROM ibexa_url_alias_ml u
                JOIN ibexa_content_language l ON (u.lang_mask & l.id) = l.id
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_url_alias_ml_translation ut
                    WHERE ut.parent = u.parent AND ut.text_md5 = u.text_md5 AND ut.language_id = l.id
                )',
            'orphaned' => 'SELECT COUNT(*) FROM ibexa_url_alias_ml_translation ut
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_url_alias_ml u
                    WHERE u.parent = ut.parent AND u.text_md5 = ut.text_md5
                        AND (u.lang_mask & ut.language_id) = ut.language_id
                )',
            'fixMissing' => 'INSERT INTO ibexa_url_alias_ml_translation (parent, text_md5, language_id)
                SELECT u.parent, u.text_md5, l.id FROM ibexa_url_alias_ml u
                JOIN ibexa_content_language l ON (u.lang_mask & l.id) = l.id
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_url_alias_ml_translation ut
                    WHERE ut.parent = u.parent AND ut.text_md5 = u.text_md5 AND ut.language_id = l.id
                )',
            // No alias on the DELETE target: SQLite's DELETE FROM does not accept one.
            'fixOrphaned' => 'DELETE FROM ibexa_url_alias_ml_translation
                WHERE NOT EXISTS (
                    SELECT 1 FROM ibexa_url_alias_ml u
                    WHERE u.parent = ibexa_url_alias_ml_translation.parent
                        AND u.text_md5 = ibexa_url_alias_ml_translation.text_md5
                        AND (u.lang_mask & ibexa_url_alias_ml_translation.language_id) = ibexa_url_alias_ml_translation.language_id
                )',
        ],
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'table',
                't',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Which table to verify: one of "%s", or "%s" for all of them.',
                    implode('", "', self::VALID_TABLES),
                    self::TABLE_ALL
                ),
                self::TABLE_ALL
            )
            ->addOption(
                'fix',
                null,
                InputOption::VALUE_NONE,
                'Insert missing rows and delete orphaned rows to bring the translation table back in sync.'
            )
            ->setHelp(
                <<<EOT
The command <info>%command.name%</info> compares "ibexa_content_translation",
"ibexa_content_version_translation" and "ibexa_url_alias_ml_translation" against the
"language_mask"/"lang_mask" columns they were backfilled from, reporting:
  - <comment>missing</comment>: a language bit set in the mask with no corresponding translation row
  - <comment>orphaned</comment>: a translation row whose bit is no longer set in the mask

Exits non-zero if any drift is found and <info>--fix</info> was not passed. Run this after
<info>ibexa:languages:backfill-translations</info>, and again before relying on the translation
tables for anything - a clean report here is the precondition for every later migration step.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = $input->getOption('table');
        $fix = (bool)$input->getOption('fix');

        $tables = $table === self::TABLE_ALL ? self::VALID_TABLES : [$table];
        foreach ($tables as $singleTable) {
            if (!isset(self::QUERIES[$singleTable])) {
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

            return self::SUCCESS;
        }

        if ($fix) {
            $output->writeln('<info>Drift found and fixed.</info>');

            return self::SUCCESS;
        }

        $output->writeln('<error>Drift found. Re-run with --fix to correct it.</error>');

        return self::FAILURE;
    }

    private function verifyTable(string $table, bool $fix, OutputInterface $output): bool
    {
        $queries = self::QUERIES[$table];

        $missing = (int)$this->connection->fetchOne($queries['missing']);
        $orphaned = (int)$this->connection->fetchOne($queries['orphaned']);

        if ($missing === 0 && $orphaned === 0) {
            $output->writeln("<info>{$table}: in sync.</info>");

            return true;
        }

        $output->writeln(sprintf(
            '<comment>%s: %d missing, %d orphaned row(s).</comment>',
            $table,
            $missing,
            $orphaned
        ));

        if (!$fix) {
            return false;
        }

        if ($missing > 0) {
            $inserted = $this->connection->executeStatement($queries['fixMissing']);
            $output->writeln("  inserted {$inserted} missing row(s).");
        }

        if ($orphaned > 0) {
            $deleted = $this->connection->executeStatement($queries['fixOrphaned']);
            $output->writeln("  deleted {$deleted} orphaned row(s).");
        }

        return true;
    }
}

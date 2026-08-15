<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command;

use Doctrine\DBAL\Connection;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Doctrine\DatabasePlatformName;
use Ibexa\Core\Persistence\Doctrine\DatabasePlatformResolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Backfills "ibexa_content_translation", "ibexa_content_version_translation" and
 * "ibexa_url_alias_ml_translation" from the legacy "language_mask"/"lang_mask" bitmask columns
 * (step 2 of the language bitmask migration).
 *
 * A standard upgrade no longer needs to run this manually: {@see BackfillLanguageTranslationsMigration}
 * (in ibexa/core's RepositoryInstaller bundle) does the same chunked, idempotent backfill as part of
 * the regular `doctrine:migrations:migrate` sequence, before the migration that drops these columns.
 * This command remains available for a `--dry-run` preview of what a pending upgrade would do, or
 * for manual repair (together with `ibexa:languages:verify-translations`) outside of a migration run.
 *
 * The decomposition needs no PHP-side bit-walking or a recursive CTE: "ibexa_content_language"
 * already contains every valid bit value, so joining against it with a bitwise AND does the
 * decomposition in SQL directly.
 */
#[AsCommand(
    name: 'ibexa:languages:backfill-translations',
    description: 'Backfills the language translation tables from the legacy language bitmask columns'
)]
final class BackfillLanguageTranslationsCommand extends Command
{
    private const DEFAULT_BATCH_SIZE = 5000;

    private const TABLE_CONTENT = 'content';
    private const TABLE_CONTENT_VERSION = 'content_version';
    private const TABLE_URL_ALIAS = 'url_alias';
    private const TABLE_ALL = 'all';

    private const VALID_TABLES = [
        self::TABLE_CONTENT,
        self::TABLE_CONTENT_VERSION,
        self::TABLE_URL_ALIAS,
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
                    'Which table to backfill: one of "%s", or "%s" for all of them.',
                    implode('", "', self::VALID_TABLES),
                    self::TABLE_ALL
                ),
                self::TABLE_ALL
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of primary-key values processed per batch.',
                (string)self::DEFAULT_BATCH_SIZE
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Report how many rows would be inserted per batch without writing anything.'
            )
            ->setHelp(
                <<<EOT
The command <info>%command.name%</info> populates "ibexa_content_translation",
"ibexa_content_version_translation" and "ibexa_url_alias_ml_translation" from the existing
"language_mask"/"lang_mask" bitmask columns. It is idempotent - already-backfilled rows are
skipped - so it is safe to re-run, including after a partial/interrupted run.

Run <info>ibexa:languages:verify-translations</info> afterward to confirm the backfill is complete.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = $input->getOption('table');
        $batchSize = (int)$input->getOption('batch-size');
        $dryRun = (bool)$input->getOption('dry-run');

        if ($batchSize < 1) {
            throw new InvalidArgumentException('batch-size', 'must be a positive integer.');
        }

        $tables = $table === self::TABLE_ALL ? self::VALID_TABLES : [$table];
        foreach ($tables as $singleTable) {
            if (!in_array($singleTable, self::VALID_TABLES, true)) {
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

        foreach ($tables as $singleTable) {
            $this->backfillTable($singleTable, $batchSize, $dryRun, $output);
        }

        return self::SUCCESS;
    }

    private function backfillTable(string $table, int $batchSize, bool $dryRun, OutputInterface $output): void
    {
        [$sourceTable, $pkColumn, $insertSql] = match ($table) {
            self::TABLE_CONTENT => [
                'ibexa_content',
                'id',
                'INSERT %s INTO ibexa_content_translation (content_id, language_id)
                 SELECT c.id, l.id FROM ibexa_content c
                 JOIN ibexa_content_language l ON (c.language_mask & l.id) = l.id
                 WHERE c.id BETWEEN :from AND :to %s',
            ],
            self::TABLE_CONTENT_VERSION => [
                'ibexa_content_version',
                'id',
                'INSERT %s INTO ibexa_content_version_translation (content_version_id, language_id)
                 SELECT v.id, l.id FROM ibexa_content_version v
                 JOIN ibexa_content_language l ON (v.language_mask & l.id) = l.id
                 WHERE v.id BETWEEN :from AND :to %s',
            ],
            self::TABLE_URL_ALIAS => [
                'ibexa_url_alias_ml',
                'parent',
                'INSERT %s INTO ibexa_url_alias_ml_translation (parent, text_md5, language_id)
                 SELECT u.parent, u.text_md5, l.id FROM ibexa_url_alias_ml u
                 JOIN ibexa_content_language l ON (u.lang_mask & l.id) = l.id
                 WHERE u.parent BETWEEN :from AND :to %s',
            ],
            default => throw new InvalidArgumentException('table', "unknown table \"{$table}\"."),
        };

        // MIN()/MAX() rather than COUNT()-based emptiness + a hardcoded lower bound of 1: some of
        // these tables (e.g. "ibexa_url_alias_ml" for root-level aliases) legitimately use 0 as a
        // valid primary-key value, so neither "MAX() === 0" nor an assumed start of 1 is safe here.
        $range = $this->connection->fetchAssociative(
            "SELECT MIN({$pkColumn}) AS min_id, MAX({$pkColumn}) AS max_id FROM {$sourceTable}"
        );
        if ($range === false || $range['min_id'] === null) {
            $output->writeln("<info>{$sourceTable} is empty, nothing to backfill.</info>");

            return;
        }

        $minId = (int)$range['min_id'];
        $maxId = (int)$range['max_id'];

        $output->writeln("<info>Backfilling {$sourceTable} ({$pkColumn} {$minId}..{$maxId}, batch size {$batchSize})...</info>");

        $insertSql = sprintf($insertSql, $this->insertIgnoreKeyword(), $this->onConflictClause());
        $totalInserted = 0;

        for ($from = $minId; $from <= $maxId; $from += $batchSize) {
            $to = min($from + $batchSize - 1, $maxId);

            if ($dryRun) {
                $inserted = (int)$this->connection->fetchOne(
                    $this->buildDryRunCountSql($table),
                    ['from' => $from, 'to' => $to]
                );
            } else {
                $inserted = $this->connection->executeStatement($insertSql, ['from' => $from, 'to' => $to]);
            }

            $totalInserted += $inserted;
            $output->writeln(
                sprintf('  %d..%d: %d row(s)%s', $from, $to, $inserted, $dryRun ? ' (dry-run)' : ''),
                OutputInterface::VERBOSITY_VERBOSE
            );
        }

        $output->writeln(sprintf(
            '<info>%s%s: %d row(s) %s.</info>',
            $dryRun ? '[dry-run] ' : '',
            $sourceTable,
            $totalInserted,
            $dryRun ? 'would be inserted' : 'inserted'
        ));
    }

    /**
     * Mirrors the real INSERT's idempotency (via insertIgnoreKeyword()/onConflictClause()) with an
     * explicit "NOT EXISTS" against the target translation table: without it, re-running
     * `--dry-run` after a prior (partial or complete) backfill would count every mask-derived pair
     * again, including ones already present, and report them as "would be inserted" when a real run
     * would actually leave them untouched.
     */
    private function buildDryRunCountSql(string $table): string
    {
        return match ($table) {
            self::TABLE_CONTENT => 'SELECT COUNT(*) FROM ibexa_content c
                 JOIN ibexa_content_language l ON (c.language_mask & l.id) = l.id
                 WHERE c.id BETWEEN :from AND :to
                 AND NOT EXISTS (
                     SELECT 1 FROM ibexa_content_translation t
                     WHERE t.content_id = c.id AND t.language_id = l.id
                 )',
            self::TABLE_CONTENT_VERSION => 'SELECT COUNT(*) FROM ibexa_content_version v
                 JOIN ibexa_content_language l ON (v.language_mask & l.id) = l.id
                 WHERE v.id BETWEEN :from AND :to
                 AND NOT EXISTS (
                     SELECT 1 FROM ibexa_content_version_translation t
                     WHERE t.content_version_id = v.id AND t.language_id = l.id
                 )',
            self::TABLE_URL_ALIAS => 'SELECT COUNT(*) FROM ibexa_url_alias_ml u
                 JOIN ibexa_content_language l ON (u.lang_mask & l.id) = l.id
                 WHERE u.parent BETWEEN :from AND :to
                 AND NOT EXISTS (
                     SELECT 1 FROM ibexa_url_alias_ml_translation t
                     WHERE t.parent = u.parent AND t.text_md5 = u.text_md5 AND t.language_id = l.id
                 )',
            default => throw new InvalidArgumentException('table', "unknown table \"{$table}\"."),
        };
    }

    private function insertIgnoreKeyword(): string
    {
        return match (DatabasePlatformResolver::resolveName($this->connection->getDatabasePlatform())) {
            DatabasePlatformName::Mysql => 'IGNORE',
            DatabasePlatformName::Sqlite => 'OR IGNORE',
            DatabasePlatformName::Postgresql => '',
        };
    }

    /**
     * Appended after the SELECT to make the insert idempotent on platforms that don't support
     * "INSERT IGNORE" (MySQL is handled via insertIgnoreKeyword() instead, since its "ON DUPLICATE
     * KEY" clause needs different syntax for an INSERT ... SELECT).
     */
    private function onConflictClause(): string
    {
        return match (DatabasePlatformResolver::resolveName($this->connection->getDatabasePlatform())) {
            DatabasePlatformName::Postgresql => 'ON CONFLICT DO NOTHING',
            DatabasePlatformName::Sqlite => '', // OR IGNORE is part of the INSERT keyword, not a trailing clause
            DatabasePlatformName::Mysql => '',
        };
    }
}
